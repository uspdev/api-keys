<?php

namespace Uspdev\ApiKeys\Contracts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Uspdev\ApiKeys\Dto\CreatedApiKeyDto;
use Uspdev\ApiKeys\Models\ApiKey;

/** Define as operações de ciclo de vida de chaves expostas pelo pacote. */
interface ApiKeyManager
{
    /**
     * Cria e persiste uma credencial vinculada ao owner informado.
     *
     * @param Model $owner Modelo proprietário compatível com a relação polimórfica.
     * @param string $name Nome legível da integração.
     * @param string $purpose Finalidade definida pela aplicação hospedeira.
     * @param string $role Papel usado para resolver as abilities da chave.
     * @param DateTimeInterface|null $expiresAt Data de expiração futura ou `null` para não expirar.
     * @param int|null $createdBy Identificador numérico de quem criou a chave.
     * @return CreatedApiKeyDto Registro persistido e token disponível somente para entrega imediata.
     *
     * @throws \InvalidArgumentException Quando a expiração não estiver no futuro.
     * @throws \RuntimeException Quando não for possível gerar um prefixo público único.
     */
    public function create(
        Model $owner,
        string $name,
        string $purpose,
        string $role,
        ?DateTimeInterface $expiresAt = null,
        ?int $createdBy = null,
    ): CreatedApiKeyDto;

    /**
     * Autentica um token e registra seus metadados de uso quando for válido.
     *
     * @param string $token Credencial completa recebida pelo cliente.
     * @param string|null $ipAddress Endereço IP do uso atual, quando disponível.
     * @return ApiKey|null Chave ativa autenticada ou `null` para token inválido, expirado ou revogado.
     */
    public function authenticate(string $token, ?string $ipAddress = null): ?ApiKey;

    /**
     * Revoga uma credencial ativa sem remover seu registro de auditoria.
     *
     * @param ApiKey $apiKey Chave que será revogada.
     * @param int|null $revokedBy Identificador numérico de quem executou a revogação.
     * @return void
     */
    public function revoke(ApiKey $apiKey, ?int $revokedBy = null): void;

    /**
     * Cria uma nova credencial com os metadados informados e revoga a anterior.
     *
     * @param ApiKey $apiKey Chave ativa que será substituída.
     * @param int|null $createdBy Identificador numérico de quem executou a renovação.
     * @param array{name?: string, purpose?: string, role?: string, expires_at?: DateTimeInterface|null}|null $attributes Valores que substituem os dados atuais.
     * @return CreatedApiKeyDto Nova chave persistida e seu token de entrega única.
     *
     * @throws \InvalidArgumentException Quando a chave não estiver ativa, o owner não for resolvido ou os atributos forem inválidos.
     */
    public function renew(
        ApiKey $apiKey,
        ?int $createdBy = null,
        ?array $attributes = null,
    ): CreatedApiKeyDto;
}
