<?php

namespace Uspdev\ApiKey\Dto;

use SensitiveParameter;
use Uspdev\ApiKey\Models\ApiKey;

/** Transporta uma chave persistida e sua credencial única em texto puro. */
final class CreatedApiKeyDto
{
    /** Cria o resultado sem persistir o token em texto puro. */
    public function __construct(
        /** Registro de API Key persistido. */
        public readonly ApiKey $apiKey,
        #[SensitiveParameter]
        /** Token em texto puro, retido somente para entrega imediata. */
        private readonly string $plainTextToken,
    ) {
    }

    /** Retorna o token que deve ser exibido ao solicitante uma única vez. */
    public function plainTextToken(): string
    {
        return $this->plainTextToken;
    }

    /** Oculta o token em texto puro da saída de depuração. */
    public function __debugInfo(): array
    {
        return [
            'apiKey' => $this->apiKey,
            'plainTextToken' => '[REDACTED]',
        ];
    }
}
