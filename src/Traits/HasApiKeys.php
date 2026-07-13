<?php

namespace Uspdev\ApiKey\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Uspdev\ApiKey\Models\ApiKey;

/** Adiciona o relacionamento polimórfico de API Keys ao modelo proprietário. */
trait HasApiKeys
{
    /** Retorna todas as credenciais pertencentes ao modelo. */
    public function apiKeys(): MorphMany
    {
        return $this->morphMany(ApiKey::class, 'owner');
    }
}
