<?php

namespace Uspdev\ApiKeys\Models;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use Throwable;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

/** Representa uma credencial com hash vinculada polimorficamente ao proprietário. */
class ApiKey extends Model
{
    public const PURPOSE_INTEGRATION = 'integration';

    public const PURPOSE_AI = 'ai';

    public const ROLE_VIEWER = 'viewer';

    public const ROLE_COLLABORATOR = 'collaborator';

    public const ROLE_ADMINISTRATOR = 'administrator';

    protected $table = 'uspdev_api_keys';

    protected $fillable = [
        'name',
        'purpose',
        'role',
        'expires_at',
        'created_by',
        'revoked_by',
    ];

    protected $hidden = [
        'secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'access_count' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'created_by' => 'integer',
            'revoked_by' => 'integer',
        ];
    }

    /** Resolve o modelo polimórfico proprietário desta credencial. */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /** Determina se a credencial foi revogada explicitamente. */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /** Determina se a data de expiração configurada já passou. */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Determina se a credencial ainda pode ser autenticada. */
    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /** Verifica uma ability pela API pública de autorização da credencial. */
    public function allows(string $ability): bool
    {
        $owner = $this->owner;

        if ($owner === null) {
            return false;
        }

        if (! method_exists($owner, 'abilities')) {
            return false;
        }

        $abilities = $owner->abilities($this->role);

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /** Autentica um token pelo serviço de chaves resolvido no container. */
    public static function authenticate(string $token): ?self
    {
        return app(ApiKeyManager::class)->authenticate($token);
    }

    /** Retorna os dados necessários para renderizar o gerenciador Blade incorporável. */
    public static function managerData(Model $owner, ?string $ownerAlias = null): array
    {
        if (! method_exists($owner, 'apiKeys')) {
            throw new InvalidArgumentException(
                'The API key manager owner must use the HasApiKeys trait.'
            );
        }

        $ownerAlias = self::resolveManagerOwnerAlias($owner, $ownerAlias);
        $ownerRouteKey = (string) $owner->getRouteKey();

        return [
            'apiKeys' => $owner->apiKeys()->latest('created_at')->get(),
            'ownerAlias' => $ownerAlias,
            'ownerRouteKey' => $ownerRouteKey,
            'purposes' => (array) config('api-keys.interface.purposes', []),
            'roles' => (array) config('api-keys.interface.roles', []),
            'componentId' => 'api-keys-manager-' . substr(
                sha1($ownerAlias . '|' . $ownerRouteKey . '|' . spl_object_id($owner)),
                0,
                12,
            ),
            'storeUrl' => route('api-keys.keys.store', [
                'ownerAlias' => $ownerAlias,
                'owner' => $ownerRouteKey,
            ]),
        ];
    }

    /** Prepara todos os dados consumidos pelo componente Blade de gerenciamento. */
    public static function managerViewData(Model $owner, ?string $ownerAlias = null): array
    {
        $managerData = self::managerData($owner, $ownerAlias);
        $purposes = $managerData['purposes'];
        $roles = $managerData['roles'];
        $resolvedOwnerAlias = $managerData['ownerAlias'];

        $managerData['apiKeys'] = $managerData['apiKeys']->map(
            fn (self $apiKey): array => $apiKey->managerRowData(
                $owner,
                $resolvedOwnerAlias,
                $purposes,
                $roles,
            )
        );

        return [
            ...$managerData,
            ...self::createdApiKeyViewData($resolvedOwnerAlias, $managerData['ownerRouteKey']),
            'hasCreationErrors' => self::hasCreationErrors(),
        ];
    }

    /** Formata uma chave para a tabela e para o modal compartilhado de detalhes. */
    public function managerRowData(Model $owner, string $ownerAlias, array $purposes, array $roles): array
    {
        $status = $this->managerStatusData();
        $credential = (string) config('api-keys.credential_prefix', 'gpp') . '_' . $this->prefix;
        $purpose = $purposes[$this->purpose] ?? ucfirst($this->purpose);
        $role = $roles[$this->role] ?? ucfirst($this->role);
        $accessCount = number_format($this->access_count);

        return [
            'name' => $this->name,
            'credential' => $credential,
            'purpose' => $purpose,
            'role' => $role,
            'status' => $status,
            'last_used_title' => $this->last_used_at?->format('d/m/Y H:i:s'),
            'last_used_human' => $this->last_used_at?->diffForHumans(),
            'access_count' => $accessCount,
            'is_active' => $this->isActive(),
            'renew_url' => $this->managerRenewUrl($owner, $ownerAlias),
            'revoke_url' => $this->managerRevokeUrl($owner, $ownerAlias),
            'details' => [
                'name' => $this->name,
                'prefix' => $credential,
                'status' => $status['label'],
                'status_class' => $status['class'],
                'purpose' => $purpose,
                'role' => $role,
                'created_at' => $this->created_at?->format('d/m/Y H:i') ?? '—',
                'expires_at' => $this->expires_at?->format('d/m/Y H:i') ?? 'Nunca',
                'last_used_at' => $this->last_used_at?->format('d/m/Y H:i') ?? 'Nunca',
                'last_used_ip' => $this->last_used_ip ?? '—',
                'access_count' => $accessCount,
                'created_by' => $this->created_by,
                'revoked_at' => $this->revoked_at?->format('d/m/Y H:i'),
                'revoked_by' => $this->revoked_by,
            ],
        ];
    }

    /** Monta a URL de revogação de uma chave vinculada ao owner informado. */
    public function managerRevokeUrl(Model $owner, ?string $ownerAlias = null): string
    {
        $ownerAlias = self::resolveManagerOwnerAlias($owner, $ownerAlias);

        return route('api-keys.keys.revoke', [
            'ownerAlias' => $ownerAlias,
            'owner' => (string) $owner->getRouteKey(),
            'apiKey' => $this->getRouteKey(),
        ]);
    }

    /** Monta a URL de renovação de uma chave vinculada ao owner informado. */
    public function managerRenewUrl(Model $owner, ?string $ownerAlias = null): string
    {
        $ownerAlias = self::resolveManagerOwnerAlias($owner, $ownerAlias);

        return route('api-keys.keys.renew', [
            'ownerAlias' => $ownerAlias,
            'owner' => (string) $owner->getRouteKey(),
            'apiKey' => $this->getRouteKey(),
        ]);
    }

    /** Resolve e valida o alias configurado para a classe do owner. */
    private static function resolveManagerOwnerAlias(Model $owner, ?string $ownerAlias): string
    {
        $owners = (array) config('api-keys.owners', []);

        if ($ownerAlias !== null) {
            if (($owners[$ownerAlias] ?? null) !== $owner::class) {
                throw new InvalidArgumentException(
                    sprintf('Owner alias [%s] is not registered for [%s].', $ownerAlias, $owner::class)
                );
            }

            return $ownerAlias;
        }

        $resolvedAlias = array_search($owner::class, $owners, true);

        if ($resolvedAlias === false) {
            throw new InvalidArgumentException(
                sprintf('Owner class [%s] must be registered in the api-keys.owners configuration.', $owner::class)
            );
        }

        return (string) $resolvedAlias;
    }

    /** Retorna o rótulo e a classe visual correspondentes ao estado da chave. */
    private function managerStatusData(): array
    {
        if ($this->isRevoked()) {
            return ['label' => 'Revogada', 'class' => 'badge-danger'];
        }

        if ($this->isExpired()) {
            return ['label' => 'Expirada', 'class' => 'badge-warning'];
        }

        return ['label' => 'Ativa', 'class' => 'badge-success'];
    }

    /** Recupera e consome o token temporário pertencente ao gerenciador atual. */
    private static function createdApiKeyViewData(string $ownerAlias, string $ownerRouteKey): array
    {
        $result = [
            'createdApiKeyToken' => null,
            'createdApiKeyAction' => 'created',
            'createdApiKeyBelongsToManager' => false,
        ];
        $createdApiKey = session('api-keys.created');

        if (! is_array($createdApiKey)) {
            return $result;
        }

        $result['createdApiKeyAction'] = ($createdApiKey['action'] ?? null) === 'renewed'
            ? 'renewed'
            : 'created';
        $belongsToManager = ($createdApiKey['owner_alias'] ?? null) === $ownerAlias
            && (string) ($createdApiKey['owner_key'] ?? '') === $ownerRouteKey;
        $encryptedToken = $createdApiKey['encrypted_token'] ?? null;

        if (! $belongsToManager || ! is_string($encryptedToken)) {
            return $result;
        }

        try {
            $token = app(Encrypter::class)->decrypt($encryptedToken, false);
            $result['createdApiKeyToken'] = is_string($token) ? $token : null;
        } catch (Throwable) {
            $result['createdApiKeyToken'] = null;
        } finally {
            session()->forget('api-keys.created');
        }

        $result['createdApiKeyBelongsToManager'] = $result['createdApiKeyToken'] !== null;

        return $result;
    }

    /** Verifica se a validação contém algum campo do formulário de criação. */
    private static function hasCreationErrors(): bool
    {
        $errors = session('errors');

        if (! $errors instanceof ViewErrorBag) {
            return false;
        }

        foreach (['name', 'purpose', 'role', 'expires_at'] as $field) {
            if ($errors->has($field)) {
                return true;
            }
        }

        return false;
    }
}
