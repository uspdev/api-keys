<?php

namespace Uspdev\ApiKeys\Services;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;
use Uspdev\ApiKeys\Dto\CreatedApiKeyDto;
use Uspdev\ApiKeys\Models\ApiKey;

/** Cria, autentica, renova e revoga credenciais de API Key. */
class ApiKeyService implements ApiKeyManager
{
    /**
     * Recebe os serviços usados para hash e leitura da configuração.
     *
     * @param Hasher $hasher Serviço de hash dos segredos.
     * @param ConfigRepository $config Repositório da configuração do package.
     */
    public function __construct(
        private readonly Hasher $hasher,
        private readonly ConfigRepository $config,
    ) {
    }

    /**
     * Persiste uma credencial com hash e retorna seu token único em texto puro.
     *
     * @param Model $owner Modelo proprietário da nova chave.
     * @param string $name Nome legível da credencial.
     * @param string $purpose Finalidade definida pela aplicação.
     * @param string $role Papel usado para resolver abilities.
     * @param DateTimeInterface|null $expiresAt Data futura de expiração ou `null`.
     * @param int|null $createdBy Identificador numérico do criador.
     * @return CreatedApiKeyDto Chave persistida e token de entrega única.
     *
     * @throws InvalidArgumentException Quando a expiração não estiver no futuro.
     * @throws RuntimeException Quando não for possível obter prefixo único após as tentativas.
     */
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
                sprintf('%s_%s.%s', $credentialPrefix, $publicPrefix, $secret),
            );
        }

        throw new RuntimeException('Unable to allocate a unique API key prefix.');
    }

    /**
     * Valida um token e registra metadados somente após validar seu hash.
     *
     * @param string $token Credencial completa enviada pelo cliente.
     * @param string|null $ipAddress Endereço IP que será registrado no uso válido.
     * @return ApiKey|null Chave ativa autenticada ou `null` para token recusado.
     */
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

    /**
     * Marca uma credencial como revogada e registra o responsável pela operação.
     *
     * @param ApiKey $apiKey Chave a revogar.
     * @param int|null $revokedBy Identificador numérico de quem revogou a chave.
     * @return void
     */
    public function revoke(ApiKey $apiKey, ?int $revokedBy = null): void
    {
        if (! $apiKey->isRevoked()) {
            $apiKey->forceFill([
                'revoked_at' => now(),
                'revoked_by' => $revokedBy,
            ])->save();
        }
    }

    /**
     * Cria uma nova credencial com metadados editáveis e revoga a anterior.
     *
     * @param ApiKey $apiKey Chave ativa que será revogada após a criação da substituta.
     * @param int|null $createdBy Identificador numérico de quem executou a renovação.
     * @param array{name?: string, purpose?: string, role?: string, expires_at?: DateTimeInterface|null}|null $attributes Valores que substituem os metadados atuais.
     * @return CreatedApiKeyDto Nova credencial persistida e token de entrega única.
     *
     * @throws InvalidArgumentException Quando a chave, o owner ou a expiração forem inválidos.
     */
    public function renew(
        ApiKey $apiKey,
        ?int $createdBy = null,
        ?array $attributes = null,
    ): CreatedApiKeyDto
    {
        return DB::transaction(function () use ($apiKey, $createdBy, $attributes): CreatedApiKeyDto {
            $currentApiKey = ApiKey::query()
                ->lockForUpdate()
                ->findOrFail($apiKey->getKey());

            if (! $currentApiKey->isActive()) {
                throw new InvalidArgumentException('Only active API keys can be renewed.');
            }

            $owner = $currentApiKey->owner;

            if (! $owner instanceof Model) {
                throw new InvalidArgumentException('The API key owner could not be resolved.');
            }

            $attributes ??= [];
            $name = array_key_exists('name', $attributes)
                ? (string) $attributes['name']
                : (string) $currentApiKey->name;
            $purpose = array_key_exists('purpose', $attributes)
                ? (string) $attributes['purpose']
                : (string) $currentApiKey->purpose;
            $role = array_key_exists('role', $attributes)
                ? (string) $attributes['role']
                : (string) $currentApiKey->role;
            $expiresAt = array_key_exists('expires_at', $attributes)
                ? $attributes['expires_at']
                : $currentApiKey->expires_at;

            if ($expiresAt !== null && ! $expiresAt instanceof DateTimeInterface) {
                throw new InvalidArgumentException('The expiration date must implement DateTimeInterface.');
            }

            $created = $this->create(
                $owner,
                $name,
                $purpose,
                $role,
                $expiresAt,
                $createdBy,
            );

            $this->revoke($currentApiKey, $createdBy);

            return $created;
        });
    }

    /**
     * Gera o identificador público e indexado usado para localizar a credencial.
     *
     * @return string Prefixo aleatório no tamanho configurado.
     */
    private function newPublicPrefix(): string
    {
        $length = max(1, (int) $this->config->get('api-keys.public_prefix_length', 6));

        return $this->randomString($length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
    }

    /**
     * Gera um segredo imprevisível e seguro para URL antes de aplicar o hash.
     *
     * @return string Segredo codificado para uso seguro em URLs.
     *
     * @throws \Random\RandomException Quando a fonte criptográfica não estiver disponível.
     */
    private function newSecret(): string
    {
        $bytes = max(32, (int) $this->config->get('api-keys.secret_bytes', 32));

        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    /**
     * Separa e valida o formato configurado da credencial em prefixo e segredo.
     *
     * @param string $token Credencial completa recebida.
     * @return array{0: string, 1: string}|null Prefixo público e segredo ou `null` quando malformado.
     */
    private function parseToken(string $token): ?array
    {
        $credentialPrefix = (string) $this->config->get(
            'api-keys.credential_prefix',
            'gpp'
        );
        $start = $credentialPrefix . '_';

        if (! str_starts_with($token, $start)) {
            return null;
        }

        $parts = explode('.', substr($token, strlen($start)));

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return [$parts[0], $parts[1]];
    }

    /**
     * Gera uma string criptograficamente aleatória a partir do alfabeto informado.
     *
     * @param int $length Quantidade de caracteres a gerar.
     * @param non-empty-string $alphabet Caracteres elegíveis para cada posição.
     * @return string Valor aleatório com o tamanho solicitado.
     *
     * @throws \Random\RandomException Quando a fonte criptográfica não estiver disponível.
     */
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
