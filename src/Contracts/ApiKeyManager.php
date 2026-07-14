<?php

namespace Uspdev\ApiKey\Contracts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Uspdev\ApiKey\Dto\CreatedApiKeyDto;
use Uspdev\ApiKey\Models\ApiKey;

/** Define as operações de ciclo de vida de chaves expostas pelo pacote. */
interface ApiKeyManager
{
    /** Cria uma credencial para o proprietário e retorna seu token único em texto puro. */
    public function create(
        Model $owner,
        string $name,
        string $purpose,
        string $role,
        ?DateTimeInterface $expiresAt = null,
        ?int $createdBy = null,
    ): CreatedApiKeyDto;

    /** Autentica um token e registra os metadados quando seu uso é válido. */
    public function authenticate(string $token, ?string $ipAddress = null): ?ApiKey;

    /** Revoga uma credencial para que ela não possa autenticar novamente. */
    public function revoke(ApiKey $apiKey): void;
}
