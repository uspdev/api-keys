<?php

namespace Uspdev\ApiKeys\Http\Controllers;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;
use Uspdev\ApiKeys\Http\Requests\StoreApiKeyRequest;
use Uspdev\ApiKeys\Models\ApiKey;

/** Processa as ações de gerenciamento usadas pelo componente Blade de API Keys. */
class ApiKeyController
{
    /**
     * Recebe os serviços usados pelas ações de gerenciamento.
     *
     * @param ApiKeyManager $apiKeys Serviço de criação, renovação e revogação.
     * @param Encrypter $encrypter Serviço que protege o token na flash session.
     */
    public function __construct(
        private readonly ApiKeyManager $apiKeys,
        private readonly Encrypter $encrypter,
    ) {
    }

    /**
     * Cria uma chave e disponibiliza seu token criptografado em flash session.
     *
     * @param StoreApiKeyRequest $request Dados validados do formulário.
     * @param string $ownerAlias Alias configurado para a classe do owner.
     * @param string $owner Chave de rota do owner.
     * @return RedirectResponse Redirecionamento de volta com token criptografado de entrega única.
     */
    public function store(StoreApiKeyRequest $request, string $ownerAlias, string $owner): RedirectResponse
    {
        $ownerModel = $this->resolveOwner($ownerAlias, $owner);
        $this->authorizeManagement($request, $ownerModel);

        $data = $request->validated();

        $expiresAt = $this->parseExpiration($data['expires_at'] ?? null);
        $createdBy = $this->resolveAuthenticatedUserId($request);
        $created = $this->apiKeys->create(
            $ownerModel,
            $data['name'],
            $data['purpose'],
            $data['role'],
            $expiresAt,
            $createdBy,
        );

        return redirect()->back()->with('api-keys.created', [
            'owner_alias' => $ownerAlias,
            'owner_key' => (string) $ownerModel->getRouteKey(),
            'action' => 'created',
            'encrypted_token' => $this->encrypter->encrypt($created->plainTextToken(), false),
        ]);
    }

    /**
     * Revoga uma chave somente quando ela pertence ao owner informado na rota.
     *
     * @param Request $request Requisição do usuário autenticado.
     * @param string $ownerAlias Alias configurado para a classe do owner.
     * @param string $owner Chave de rota do owner.
     * @param string $apiKey Identificador da chave a revogar.
     * @return RedirectResponse Redirecionamento de volta com mensagem de sucesso.
     */
    public function revoke(Request $request, string $ownerAlias, string $owner, string $apiKey): RedirectResponse
    {
        $ownerModel = $this->resolveOwner($ownerAlias, $owner);
        $this->authorizeManagement($request, $ownerModel);
        $apiKeyModel = ApiKey::query()->findOrFail($apiKey);

        if (! $this->belongsToOwner($apiKeyModel, $ownerModel)) {
            abort(404);
        }

        $this->apiKeys->revoke($apiKeyModel, $this->resolveAuthenticatedUserId($request));

        return redirect()->back()->with('api-keys.message', 'A API Key foi revogada.');
    }

    /**
     * Renova uma chave ativa, entregando a nova credencial somente uma vez.
     *
     * @param StoreApiKeyRequest $request Dados validados do formulário.
     * @param string $ownerAlias Alias configurado para a classe do owner.
     * @param string $owner Chave de rota do owner.
     * @param string $apiKey Identificador da chave que será renovada.
     * @return RedirectResponse Redirecionamento de volta com token criptografado de entrega única.
     */
    public function renew(
        StoreApiKeyRequest $request,
        string $ownerAlias,
        string $owner,
        string $apiKey,
    ): RedirectResponse
    {
        $ownerModel = $this->resolveOwner($ownerAlias, $owner);
        $this->authorizeManagement($request, $ownerModel);
        $apiKeyModel = ApiKey::query()->findOrFail($apiKey);

        if (! $this->belongsToOwner($apiKeyModel, $ownerModel)) {
            abort(404);
        }

        $data = $request->validated();
        $renewed = $this->apiKeys->renew(
            $apiKeyModel,
            $this->resolveAuthenticatedUserId($request),
            [
                'name' => $data['name'],
                'purpose' => $data['purpose'],
                'role' => $data['role'],
                'expires_at' => $this->parseExpiration($data['expires_at'] ?? null),
            ],
        );

        return redirect()->back()->with('api-keys.created', [
            'owner_alias' => $ownerAlias,
            'owner_key' => (string) $ownerModel->getRouteKey(),
            'action' => 'renewed',
            'encrypted_token' => $this->encrypter->encrypt($renewed->plainTextToken(), false),
        ]);
    }

    /**
     * Garante que o usuário logado pode gerenciar chaves deste owner.
     *
     * @param Request $request Requisição que contém o usuário autenticado.
     * @param Model $owner Owner que será administrado.
     * @return void
     */
    private function authorizeManagement(Request $request, Model $owner): void
    {
        if (! $this->canManage($request, $owner)) {
            abort(403);
        }
    }

    /**
     * Verifica a ability configurada para administrar o owner informado.
     *
     * @param Request $request Requisição que contém o usuário autenticado.
     * @param Model $owner Owner para o qual a ability será avaliada.
     * @return bool `true` quando o usuário pode administrar as chaves.
     */
    private function canManage(Request $request, Model $owner): bool
    {
        $user = $request->user();
        $ability = (string) config('api-keys.management.ability', 'manageApiKeys');

        if ($user === null) {
            abort(401);
        }

        return $ability !== '' && method_exists($user, 'can') && $user->can($ability, $owner);
    }

    /**
     * Resolve o model a partir de um alias previamente registrado pela aplicação.
     *
     * @param string $ownerAlias Alias configurado para a classe do owner.
     * @param string $owner Valor da chave de rota do owner.
     * @return Model Owner resolvido pela vinculação de rota.
     */
    private function resolveOwner(string $ownerAlias, string $owner): Model
    {
        $ownerClass = $this->resolveOwnerClass($ownerAlias);
        $ownerPrototype = new $ownerClass();

        $ownerModel = $ownerPrototype->resolveRouteBinding($owner);

        if (! $ownerModel instanceof Model) {
            abort(404);
        }

        return $ownerModel;
    }

    /**
     * Resolve um alias seguro e exige que o model seja compatível com HasApiKeys.
     *
     * @param string $ownerAlias Alias recebido pela rota.
     * @return class-string<Model> Classe do owner registrada na configuração.
     */
    private function resolveOwnerClass(string $ownerAlias): string
    {
        $owners = (array) config('api-keys.owners', []);
        $ownerClass = $owners[$ownerAlias] ?? null;

        if (! is_string($ownerClass) || ! is_a($ownerClass, Model::class, true)) {
            abort(404);
        }

        $ownerPrototype = new $ownerClass();

        if (! method_exists($ownerPrototype, 'apiKeys')) {
            abort(404);
        }

        return $ownerClass;
    }

    /**
     * Confere o tipo polimórfico e o identificador antes de uma ação destrutiva.
     *
     * @param ApiKey $apiKey Chave cuja posse será conferida.
     * @param Model $owner Owner esperado na rota.
     * @return bool `true` quando a chave pertence exatamente ao owner.
     */
    private function belongsToOwner(ApiKey $apiKey, Model $owner): bool
    {
        return $apiKey->owner_type === $owner->getMorphClass()
            && (string) $apiKey->owner_id === (string) $owner->getKey();
    }

    /**
     * Converte a data do formulário para o fim do dia selecionado.
     *
     * @param mixed $value Valor recebido no campo `expires_at`.
     * @return DateTimeInterface|null Data no fim do dia ou `null` para valor vazio ou inválido.
     */
    private function parseExpiration(mixed $value): ?DateTimeInterface
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $expiration = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $expiration === false ? null : $expiration->setTime(23, 59, 59);
    }

    /**
     * Mantém apenas identificadores numéricos compatíveis com os campos de auditoria.
     *
     * @param Request $request Requisição que contém o usuário autenticado.
     * @return int|null Identificador numérico do usuário ou `null` quando incompatível.
     */
    private function resolveAuthenticatedUserId(Request $request): ?int
    {
        $identifier = $request->user()?->getAuthIdentifier();

        if (! is_int($identifier) && ! (is_string($identifier) && ctype_digit($identifier))) {
            return null;
        }

        return (int) $identifier;
    }
}
