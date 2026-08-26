# Contrato público de integração

Este documento descreve a superfície pública atualmente disponível no package
`uspdev/api-keys`. Use estas APIs para integrar uma aplicação; helpers usados
internamente pelos componentes Blade não fazem parte deste contrato.

## Owner de API Keys

Um model Eloquent que possuir chaves deve usar `HasApiKeys`:

```php
use Uspdev\ApiKeys\Traits\HasApiKeys;

class Project extends Model
{
    use HasApiKeys;
}
```

| Método | Entrada | Saída | Responsabilidade |
| --- | --- | --- | --- |
| `apiKeys()` | Nenhuma | `MorphMany` de `ApiKey` | Retorna as chaves pertencentes ao owner. |

Para autorizar rotas de negócio, o owner também deve usar `HasApiAbilities` e
implementar o método abaixo:

```php
use Uspdev\ApiKeys\Traits\HasApiAbilities;

/**
 * Retorna as abilities concedidas ao papel informado.
 *
 * @param string $role Papel armazenado na API Key.
 * @return list<string> Abilities permitidas; `*` concede acesso total.
 */
public function abilities(string $role): array;
```

O package entrega o `role` da chave ao owner, mas a aplicação define os papéis
e suas abilities. Um papel desconhecido deve retornar uma lista vazia.

## Modelo `ApiKey`

| Método | Entrada | Saída | Comportamento |
| --- | --- | --- | --- |
| `owner()` | Nenhuma | `MorphTo` | Resolve o model proprietário da chave. |
| `isRevoked()` | Nenhuma | `bool` | Informa se a chave foi revogada. |
| `isExpired()` | Nenhuma | `bool` | Informa se a data de expiração já passou. |
| `isActive()` | Nenhuma | `bool` | É verdadeiro somente para chaves não expiradas e não revogadas. |
| `allows(string $ability)` | Ability a consultar. | `bool` | Consulta `owner->abilities($role)` e aceita a ability exata ou `*`. |
| `ApiKey::authenticate(string $token)` | Token completo da credencial. | `?ApiKey` | Autentica pelo serviço registrado; retorna `null` quando inválido. |

`allows()` é a API pública para consultar a autorização de uma chave. A
aplicação não deve consultar `abilities()` diretamente ao processar uma
requisição.

## Serviço `ApiKeyManager`

Resolva o contrato pelo container quando precisar administrar chaves fora da
interface Blade:

```php
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

$apiKeys = app(ApiKeyManager::class);
```

```php
public function create(
    Model $owner,
    string $name,
    string $purpose,
    string $role,
    ?DateTimeInterface $expiresAt = null,
    ?int $createdBy = null,
): CreatedApiKeyDto;

public function authenticate(string $token, ?string $ipAddress = null): ?ApiKey;

public function revoke(ApiKey $apiKey, ?int $revokedBy = null): void;

public function renew(
    ApiKey $apiKey,
    ?int $createdBy = null,
    ?array $attributes = null,
): CreatedApiKeyDto;
```

| Método | Entradas | Saída e efeitos |
| --- | --- | --- |
| `create()` | Owner, nome, `purpose`, `role`, expiração opcional e identificador numérico do criador. | Persiste hash do segredo e retorna o registro com o token em texto puro. Lança `InvalidArgumentException` para expiração não futura e `RuntimeException` se não conseguir gerar prefixo único. |
| `authenticate()` | Token completo e IP opcional. | Retorna a chave ativa quando o formato e o hash forem válidos; atualiza `access_count`, `last_used_at` e `last_used_ip`. Retorna `null` para token inválido, expirado ou revogado. |
| `revoke()` | Chave e identificador numérico opcional de quem revogou. | Marca a chave como revogada; não exclui o registro. |
| `renew()` | Chave ativa, identificador de quem renovou e atributos opcionais. | Cria uma nova chave e revoga a anterior em transação. Lança `InvalidArgumentException` quando a chave não estiver ativa, o owner não puder ser resolvido ou `expires_at` for inválido. |

Em `renew()`, `$attributes` aceita somente `name`, `purpose`, `role` e
`expires_at` (`DateTimeInterface|null`). Os campos omitidos reutilizam os
valores da chave anterior.

## Resultado de criação

`create()` e `renew()` retornam `CreatedApiKeyDto`:

| Membro | Tipo | Finalidade |
| --- | --- | --- |
| `$apiKey` | `ApiKey` somente leitura | Registro persistido da nova credencial. |
| `plainTextToken()` | `string` | Retorna o token completo para entrega única ao solicitante. |

O token é sensível: não o persista, registre em logs, exponha em eventos ou
inclua em respostas posteriores. O banco armazena apenas `secret_hash`.

## Middleware de autenticação atual

O alias configurado em `api-keys.middleware.alias` é `uspdevApiKeys` por
padrão. Na versão atual, ele apenas autentica a credencial:

```php
Route::middleware('uspdevApiKeys')->get('/integrations/users', UserController::class);
```

O middleware aceita `Authorization: Bearer <token>` e, somente quando
habilitado em configuração, o token pela query string. Ele retorna HTTP 401
para credencial ausente ou inválida e disponibiliza a `ApiKey` no atributo de
request configurado por `api-keys.middleware.request_attribute` (`apiKey` por
padrão).

A etapa 3 do [plano de ajustes](../plano-ajustes-contrato-middleware.md)
alterará este contrato para exigir abilities diretamente na rota. Até que ela
seja implementada, a aplicação deve consultar `ApiKey::allows()` após a
autenticação.

## Responsabilidades da aplicação

- Registrar cada classe de owner em `api-keys.owners`.
- Definir `purpose`, papéis e abilities do domínio.
- Aplicar o middleware apenas às rotas de negócio que aceitam API Key.
- Comparar a chave com o owner solicitado pela rota quando houver recurso
  vinculado a um owner, evitando acesso horizontal entre owners distintos.
- Definir autorização do usuário autenticado para gerenciar as chaves na
  interface Blade.
