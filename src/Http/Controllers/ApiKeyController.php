<?php

namespace Uspdev\ApiKey\Http\Controllers;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Uspdev\ApiKey\Contracts\ApiKeyManager;
use Uspdev\ApiKey\Models\ApiKey;

/** Processa as ações administrativas usadas pelo componente Blade de API Keys. */
class ApiKeyController
{
    /** Recebe chaves, criptografia e validação fornecidas pelo Laravel. */
    public function __construct(
        private readonly ApiKeyManager $apiKeys,
        private readonly Encrypter $encrypter,
        private readonly ValidationFactory $validator,
    ) {
    }

    /** Cria uma chave e disponibiliza seu token criptografado em flash session. */
    public function store(Request $request, string $ownerAlias, string $owner): RedirectResponse
    {
        $ownerModel = $this->resolveOwner($ownerAlias, $owner);
        $this->authorizeManagement($request, $ownerModel);
        $purposes = array_keys((array) config('api-key.interface.purposes', []));
        $roles = array_keys((array) config('api-key.interface.roles', []));

        $data = $this->validator->make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', Rule::in($purposes)],
            'role' => ['required', Rule::in($roles)],
            'expires_at' => [
                'nullable',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $expiration = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
                    $errors = DateTimeImmutable::getLastErrors();

                    if (
                        $expiration === false
                        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
                    ) {
                        $fail('A data de expiração deve ser uma data válida.');
                    }
                },
            ],
        ])->validate();

        $expiresAt = $this->parseExpiration($data['expires_at'] ?? null);
        $createdBy = $this->resolveCreatorId($request);
        $created = $this->apiKeys->create(
            $ownerModel,
            $data['name'],
            $data['purpose'],
            $data['role'],
            $expiresAt,
            $createdBy,
        );

        return redirect()->back()->with('api-key.created', [
            'owner_alias' => $ownerAlias,
            'owner_key' => (string) $ownerModel->getRouteKey(),
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

        $this->apiKeys->revoke($apiKeyModel);

        return redirect()->back()->with('api-key.message', 'A API Key foi revogada.');
    }

    /** Garante que o usuário logado pode administrar chaves deste owner. */
    private function authorizeManagement(Request $request, Model $owner): void
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $ability = (string) config('api-key.management.ability', 'manageApiKeys');

        if ($ability === '' || ! method_exists($user, 'can') || ! $user->can($ability, $owner)) {
            abort(403);
        }
    }

    /** Resolve o model a partir de um alias previamente registrado pela aplicação. */
    private function resolveOwner(string $ownerAlias, string $owner): Model
    {
        $owners = (array) config('api-key.owners', []);
        $ownerClass = $owners[$ownerAlias] ?? null;

        if (! is_string($ownerClass) || ! is_a($ownerClass, Model::class, true)) {
            abort(404);
        }

        $ownerPrototype = new $ownerClass();

        if (! method_exists($ownerPrototype, 'apiKeys')) {
            abort(404);
        }

        $ownerModel = $ownerPrototype->resolveRouteBinding($owner);

        if (! $ownerModel instanceof Model) {
            abort(404);
        }

        return $ownerModel;
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

    /** Mantém apenas identificadores numéricos compatíveis com a coluna created_by. */
    private function resolveCreatorId(Request $request): ?int
    {
        $identifier = $request->user()?->getAuthIdentifier();

        if (! is_int($identifier) && ! (is_string($identifier) && ctype_digit($identifier))) {
            return null;
        }

        return (int) $identifier;
    }
}
