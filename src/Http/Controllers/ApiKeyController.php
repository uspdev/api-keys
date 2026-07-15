<?php

namespace Uspdev\ApiKeys\Http\Controllers;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;
use Uspdev\ApiKeys\Http\Requests\StoreApiKeyRequest;
use Uspdev\ApiKeys\Models\ApiKey;

/** Processa as ações administrativas usadas pelo componente Blade de API Keys. */
class ApiKeyController
{
    /** Recebe chaves, criptografia e validação fornecidas pelo Laravel. */
    public function __construct(
        private readonly ApiKeyManager $apiKeys,
        private readonly Encrypter $encrypter,
    ) {
    }

    /** Exibe os tipos de owner registrados para a página administrativa. */
    public function index(): View
    {
        return view('api-keys::admin.index', [
            'ownerAlias' => null,
            'ownerTypes' => $this->registeredOwnerTypes(),
            'owners' => collect(),
        ]);
    }
    /** Lista os owners de um alias que o usuário atual pode administrar. */
    public function owners(Request $request, string $ownerAlias): View
    {
        $ownerClass = $this->resolveOwnerClass($ownerAlias);
        $owners = $ownerClass::query()
            ->get()
            ->filter(fn (Model $owner): bool => $this->canManage($request, $owner))
            ->map(fn (Model $owner): array => $this->ownerEntry($owner))
            ->values();

        return view('api-keys::admin.index', [
            'ownerAlias' => $ownerAlias,
            'ownerTypes' => $this->registeredOwnerTypes(),
            'owners' => $owners,
        ]);
    }

    /** Renderiza a página completa e reutiliza o mesmo gerenciador Blade incorporável. */
    public function show(Request $request, string $ownerAlias, string $owner): View
    {
        $ownerModel = $this->resolveOwner($ownerAlias, $owner);
        $this->authorizeManagement($request, $ownerModel);

        return view('api-keys::admin.show', [
            'owner' => $ownerModel,
            'ownerAlias' => $ownerAlias,
        ]);
    }

    /** Cria uma chave e disponibiliza seu token criptografado em flash session. */
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

    /** Revoga uma chave somente quando ela pertence ao owner informado na rota. */
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

    /** Renova uma chave ativa, entregando a nova credencial somente uma vez. */
    public function renew(Request $request, string $ownerAlias, string $owner, string $apiKey): RedirectResponse
    {
        $ownerModel = $this->resolveOwner($ownerAlias, $owner);
        $this->authorizeManagement($request, $ownerModel);
        $apiKeyModel = ApiKey::query()->findOrFail($apiKey);

        if (! $this->belongsToOwner($apiKeyModel, $ownerModel)) {
            abort(404);
        }

        $renewed = $this->apiKeys->renew($apiKeyModel, $this->resolveAuthenticatedUserId($request));

        return redirect()->back()->with('api-keys.created', [
            'owner_alias' => $ownerAlias,
            'owner_key' => (string) $ownerModel->getRouteKey(),
            'action' => 'renewed',
            'encrypted_token' => $this->encrypter->encrypt($renewed->plainTextToken(), false),
        ]);
    }

    /** Garante que o usuário logado pode administrar chaves deste owner. */
    private function authorizeManagement(Request $request, Model $owner): void
    {
        if (! $this->canManage($request, $owner)) {
            abort(403);
        }
    }

    /** Verifica a ability configurada sem expor owners não autorizados na listagem. */
    private function canManage(Request $request, Model $owner): bool
    {
        $user = $request->user();
        $ability = (string) config('api-keys.management.ability', 'manageApiKeys');

        if ($user === null) {
            abort(401);
        }

        return $ability !== '' && method_exists($user, 'can') && $user->can($ability, $owner);
    }

    /** Resolve o model a partir de um alias previamente registrado pela aplicação. */
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

    /** Resolve um alias seguro e exige que o model seja compatível com HasApiKeys. */
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

    /** Retorna somente aliases válidos para a tela inicial da administração. */
    private function registeredOwnerTypes(): array
    {
        $types = [];

        foreach ((array) config('api-keys.owners', []) as $alias => $ownerClass) {
            if (! is_string($alias) || ! is_string($ownerClass) || ! is_a($ownerClass, Model::class, true)) {
                continue;
            }

            $ownerPrototype = new $ownerClass();

            if (! method_exists($ownerPrototype, 'apiKeys')) {
                continue;
            }

            $types[] = [
                'alias' => $alias,
                'class' => $ownerClass,
                'label' => $this->ownerAliasLabel($alias),
            ];
        }

        return $types;
    }

    /** Prepara uma linha de owner sem assumir um atributo de negócio obrigatório. */
    private function ownerEntry(Model $owner): array
    {
        $label = $owner->getAttribute('name') ?? $owner->getAttribute('title');

        if (! is_scalar($label) || (string) $label === '') {
            $label = '#' . (string) $owner->getRouteKey();
        }

        return [
            'model' => $owner,
            'label' => (string) $label,
        ];
    }

    /** Converte aliases técnicos em títulos legíveis sem depender do model owner. */
    private function ownerAliasLabel(string $alias): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $alias));
    }

    /** Confere o tipo polimórfico e o identificador antes de uma ação destrutiva. */
    private function belongsToOwner(ApiKey $apiKey, Model $owner): bool
    {
        return $apiKey->owner_type === $owner->getMorphClass()
            && (string) $apiKey->owner_id === (string) $owner->getKey();
    }

    /** Converte a data do formulário para o fim do dia selecionado. */
    private function parseExpiration(mixed $value): ?DateTimeInterface
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $expiration = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $expiration === false ? null : $expiration->setTime(23, 59, 59);
    }

    /** Mantém apenas identificadores numéricos compatíveis com os campos de auditoria. */
    private function resolveAuthenticatedUserId(Request $request): ?int
    {
        $identifier = $request->user()?->getAuthIdentifier();

        if (! is_int($identifier) && ! (is_string($identifier) && ctype_digit($identifier))) {
            return null;
        }

        return (int) $identifier;
    }
}
