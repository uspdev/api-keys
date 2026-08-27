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

`allows()` é a API pública usada pelo middleware para consultar a autorização
de uma chave. A aplicação não deve consultar `abilities()` diretamente. Ela
pode chamar `allows()` quando precisar de uma decisão adicional que não seja a
ability obrigatória já declarada na rota.

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

## Middleware de autenticação e autorização

O alias configurado em `api-keys.middleware.alias` é `uspdevApiKeys` por
padrão. Toda rota protegida deve declarar ao menos uma ability:

```php
Route::middleware('uspdevApiKeys:users.read.self,users.read.any')
    ->get('/integrations/users', UserController::class);
```

As abilities separadas por vírgula são alternativas: a chave é autorizada
quando possuir qualquer uma delas. O middleware aceita `Authorization: Bearer
<token>` e, somente quando habilitado em configuração, o token pela query
string. Ele atualiza os metadados de uso e disponibiliza a `ApiKey` no atributo
configurado por `api-keys.middleware.request_attribute` (`apiKey` por padrão).

Os resultados do middleware são:

- HTTP 401 para credencial ausente, malformada, inválida, expirada ou revogada;
- HTTP 403 para chave válida sem nenhuma das abilities declaradas;
- HTTP 500 quando nenhuma ability for informada ou houver parâmetro vazio.

O controller não precisa repetir `allows()` para uma ability já exigida pelo
middleware.

## Responsabilidades da aplicação

- Registrar cada classe de owner em `api-keys.owners`.
- Definir `purpose`, papéis e abilities do domínio.
- Aplicar o middleware com ao menos uma ability às rotas de negócio que
  aceitam API Key.
- Comparar a chave com o owner solicitado pela rota quando houver recurso
  vinculado a um owner, evitando acesso horizontal entre owners distintos.
- Definir autorização do usuário autenticado para gerenciar as chaves na
  interface Blade.
