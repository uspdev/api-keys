<?php

namespace Uspdev\ApiKey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Uspdev\ApiKey\Contracts\ApiKeyManager;

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
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function allows(string $ability): bool
    {
        $owner = $this->owner;

        if ($owner === null || ! method_exists($owner, 'abilities')) {
            return false;
        }

        return in_array($ability, $owner->abilities($this->role), true);
    }

    public static function authenticate(string $token): ?self
    {
        return app(ApiKeyManager::class)->authenticate($token);
    }
}
