<?php

namespace Uspdev\ApiKey\Traits;

/** Delega a autorização da API Key às abilities do papel no proprietário. */
trait HasApiAbilities
{
    /** Retorna as abilities concedidas por um papel definido pelo proprietário. */
    abstract public function abilities(string $role): array;

    /** Determina se um papel concede uma ability, incluindo o curinga. */
    public function allowsApiAbility(string $role, string $ability): bool
    {
        $abilities = $this->abilities($role);

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /** Fornece o método de autorização citado na documentação principal do pacote. */
    public function authorize(string $role, string $ability): bool
    {
        return $this->allowsApiAbility($role, $ability);
    }
}
