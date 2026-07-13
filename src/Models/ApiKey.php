<?php

namespace Uspdev\ApiKey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Uspdev\ApiKey\Contracts\ApiKeyManager;

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

    /** Delega a verificação de ability ao proprietário desta credencial. */
    public function allows(string $ability): bool
    {
        $owner = $this->owner;

        if ($owner === null) {
            return false;
        }

        /** Prioriza a API de autorização da trait do proprietário quando disponível. */
        if (method_exists($owner, 'allowsApiAbility')) {
            return $owner->allowsApiAbility($this->role, $ability);
        }

        if (! method_exists($owner, 'abilities')) {
            return false;
        }

        /** Mantém compatibilidade com owners que implementam apenas abilities(). */
        $abilities = $owner->abilities($this->role);

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /** Autentica um token pelo serviço de chaves resolvido no container. */
    public static function authenticate(string $token): ?self
    {
        return app(ApiKeyManager::class)->authenticate($token);
    }
}
