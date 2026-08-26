<?php

namespace Uspdev\ApiKeys\Traits;

/** Define o contrato de abilities dos papéis no proprietário. */
trait HasApiAbilities
{
    /**
     * Retorna as abilities concedidas por um papel definido pelo proprietário.
     *
     * @param string $role Papel persistido na API Key.
     * @return list<string> Abilities concedidas; `*` libera qualquer ability.
     */
    abstract public function abilities(string $role): array;
}
