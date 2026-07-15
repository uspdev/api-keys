<?php

namespace Uspdev\ApiKeys\Contracts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Uspdev\ApiKeys\Dto\CreatedApiKeyDto;
use Uspdev\ApiKeys\Models\ApiKey;

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

    /** Revoga uma credencial e registra o responsável pela operação. */
    public function revoke(ApiKey $apiKey, ?int $revokedBy = null): void;

    /** Cria uma nova credencial equivalente e revoga a credencial anterior. */
    public function renew(ApiKey $apiKey, ?int $createdBy = null): CreatedApiKeyDto;
}
