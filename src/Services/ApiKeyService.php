<?php

namespace Uspdev\ApiKeys\Services;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use InvalidArgumentException;
use RuntimeException;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;
use Uspdev\ApiKeys\Dto\CreatedApiKeyDto;
use Uspdev\ApiKeys\Models\ApiKey;

/** Cria, autentica e revoga credenciais de API Key. */
class ApiKeyService implements ApiKeyManager
{
    /** Recebe os serviços de hash e configuração usados pelo gerenciador. */
    public function __construct(
        private readonly Hasher $hasher,
        private readonly ConfigRepository $config,
    ) {
    }

    /** Persiste uma credencial com hash e retorna seu token único em texto puro. */
    public function create(
        Model $owner,
        string $name,
        string $purpose,
        string $role,
        ?DateTimeInterface $expiresAt = null,
        ?int $createdBy = null,
    ): CreatedApiKeyDto {
        if ($expiresAt !== null && $expiresAt->getTimestamp() <= now()->getTimestamp()) {
            throw new InvalidArgumentException('The expiration date must be in the future.');
        }

        $credentialPrefix = (string) $this->config->get(
            'api-keys.credential_prefix',
            'gpp'
        );
        $credentialVersion = (string) $this->config->get(
            'api-keys.credential_version',
            'v1'
        );

        /** Repete a alocação caso ocorra uma colisão de prefixo após a consulta. */
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $publicPrefix = $this->newPublicPrefix();

            if (ApiKey::query()->where('prefix', $publicPrefix)->exists()) {
                continue;
            }

            $secret = $this->newSecret();
            $apiKey = new ApiKey();
            $apiKey->forceFill([
                'name' => $name,
                'purpose' => $purpose,
                'role' => $role,
                'prefix' => $publicPrefix,
                'secret_hash' => $this->hasher->make($secret),
                'access_count' => 0,
                'expires_at' => $expiresAt,
                'created_by' => $createdBy,
            ]);
            $apiKey->owner()->associate($owner);

            /** O índice único do banco protege contra criações concorrentes. */
            try {
                $apiKey->save();
            } catch (UniqueConstraintViolationException) {
                continue;
            }

            return new CreatedApiKeyDto(
                $apiKey,
                sprintf('%s_%s_%s.%s', $credentialPrefix, $credentialVersion, $publicPrefix, $secret),
            );
        }

        throw new RuntimeException('Unable to allocate a unique API key prefix.');
    }

    /** Valida um token e registra metadados somente após validar seu hash. */
    public function authenticate(string $token, ?string $ipAddress = null): ?ApiKey
    {
        [$publicPrefix, $secret] = $this->parseToken($token) ?? [null, null];

        if ($publicPrefix === null || $secret === null) {
            return null;
        }

        $apiKey = ApiKey::query()->where('prefix', $publicPrefix)->first();

        /** Recusa credenciais malformadas, revogadas ou expiradas antes do hash. */
        if ($apiKey === null || ! $apiKey->isActive()) {
            return null;
        }

        if (! $this->hasher->check($secret, $apiKey->secret_hash)) {
            return null;
        }

        $apiKey->increment('access_count', 1, [
            'last_used_at' => now(),
            'last_used_ip' => $ipAddress,
        ]);

        return $apiKey;
    }

    /** Marca uma credencial como revogada sem alterar a data já registrada. */
    public function revoke(ApiKey $apiKey): void
    {
        if (! $apiKey->isRevoked()) {
            $apiKey->forceFill(['revoked_at' => now()])->save();
        }
    }

    /** Gera o identificador público e indexado usado para localizar a credencial. */
    private function newPublicPrefix(): string
    {
        $length = max(1, (int) $this->config->get('api-keys.public_prefix_length', 6));

        return $this->randomString($length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
    }

    /** Gera um segredo imprevisível e seguro para URL antes de aplicar o hash. */
    private function newSecret(): string
    {
        $bytes = max(32, (int) $this->config->get('api-keys.secret_bytes', 32));

        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    /** Separa e valida o formato configurado da credencial em prefixo e segredo. */
    private function parseToken(string $token): ?array
    {
        $credentialPrefix = (string) $this->config->get(
            'api-keys.credential_prefix',
            'gpp'
        );
        $credentialVersion = (string) $this->config->get(
            'api-keys.credential_version',
            'v1'
        );
        $start = $credentialPrefix . '_' . $credentialVersion . '_';

        if (! str_starts_with($token, $start)) {
            return null;
        }

        $parts = explode('.', substr($token, strlen($start)));

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return [$parts[0], $parts[1]];
    }

    /** Gera uma string criptograficamente aleatória a partir do alfabeto informado. */
    private function randomString(int $length, string $alphabet): string
    {
        $lastIndex = strlen($alphabet) - 1;
        $value = '';

        /** Sorteia cada caractere de forma independente para evitar sequências previsíveis. */
        for ($index = 0; $index < $length; $index++) {
            $value .= $alphabet[random_int(0, $lastIndex)];
        }

        return $value;
    }
}
