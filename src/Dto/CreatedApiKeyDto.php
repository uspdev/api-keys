<?php

namespace Uspdev\ApiKeys\Dto;

use SensitiveParameter;
use Uspdev\ApiKeys\Models\ApiKey;

/** Transporta uma chave persistida e sua credencial única em texto puro. */
final class CreatedApiKeyDto
{
    /**
     * Cria o resultado sem persistir o token em texto puro.
     *
     * @param ApiKey $apiKey Registro da credencial recém-persistida.
     * @param string $plainTextToken Token sensível disponível somente para entrega imediata.
     */
    public function __construct(
        /** Registro de API Key persistido. */
        public readonly ApiKey $apiKey,
        #[SensitiveParameter]
        /** Token em texto puro, retido somente para entrega imediata. */
        private readonly string $plainTextToken,
    ) {
    }

    /**
     * Retorna o token que deve ser exibido ao solicitante uma única vez.
     *
     * @return string Token completo em texto puro.
     */
    public function plainTextToken(): string
    {
        return $this->plainTextToken;
    }

    /**
     * Oculta o token em texto puro da saída de depuração.
     *
     * @return array{apiKey: ApiKey, plainTextToken: '[REDACTED]'} Dados seguros para depuração.
     */
    public function __debugInfo(): array
    {
        return [
            'apiKey' => $this->apiKey,
            'plainTextToken' => '[REDACTED]',
        ];
    }
}
