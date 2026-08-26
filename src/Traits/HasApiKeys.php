<?php

namespace Uspdev\ApiKeys\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Uspdev\ApiKeys\Models\ApiKey;

/** Adiciona o relacionamento polimórfico de API Keys ao modelo proprietário. */
trait HasApiKeys
{
    /**
     * Retorna todas as credenciais pertencentes ao modelo.
     *
     * @return MorphMany<ApiKey, $this> Relação polimórfica das chaves do owner.
     */
    public function apiKeys(): MorphMany
    {
        return $this->morphMany(ApiKey::class, 'owner');
    }
}
