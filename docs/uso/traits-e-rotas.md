# Traits, middleware e rotas

## Traits do owner

### `HasApiKeys`

Adiciona o relacionamento polimórfico entre o model e suas API Keys:

```php
use Uspdev\ApiKeys\Traits\HasApiKeys;

class Project extends Model
{
    use HasApiKeys;
}
```

Depois disso, a aplicação pode usar:

```php
$project->apiKeys();
$project->apiKeys;
```

### `HasApiAbilities`

Fornece a integração com o `role` armazenado na API Key. O model deve
implementar `abilities()`:

```php
use Uspdev\ApiKeys\Traits\HasApiAbilities;

class Project extends Model
{
    use HasApiAbilities;

    public function abilities(string $role): array
    {
        return match ($role) {
            'viewer' => ['tasks.read'],
            'collaborator' => ['tasks.read', 'tasks.create'],
            'administrator' => ['*'],
            default => [],
        };
    }
}
```

A aplicação declara a ability exigida ao aplicar o middleware:

```php
Route::middleware('uspdevApiKeys:tasks.create')
    ->post('/api/projects/{project}/tasks', [TaskController::class, 'store']);
```

O middleware usa `ApiKey::allows()` como API pública de autorização. O método
`abilities()` apenas define o mapa de permissões do owner para cada papel; a
aplicação não precisa consultá-lo diretamente nem informar novamente o papel
armazenado na chave.

O wildcard `*` concede todas as abilities. O package não conhece as regras
de negócio nem consulta diretamente as roles da aplicação ou da Senha Única;
o owner deve fazer esse mapeamento.

## Middleware

O Service Provider registra automaticamente o alias configurado em
`api-keys.middleware.alias`. O padrão é:

```text
uspdevApiKeys
```

A trait não aplica o middleware automaticamente. A aplicação hospedeira deve
usá-lo nas rotas de negócio que deseja proteger.

O middleware exige ao menos uma ability após o alias. Quando várias abilities
forem separadas por vírgula, elas são alternativas e basta a chave possuir uma:

```php
Route::middleware('uspdevApiKeys:users.read.self,users.read.any');
```

## Rotas de negócio da aplicação

As rotas de negócio pertencem à aplicação hospedeira:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::middleware('uspdevApiKeys:tasks.read')
    ->prefix('api')
    ->group(function (): void {
        Route::get('projects/{project}/tasks', [TaskController::class, 'index'])
            ->name('projects.tasks.index');
    });
```

O middleware:

1. valida se ao menos uma ability não vazia foi declarada;
2. lê `Authorization: Bearer ...`;
3. valida o formato e localiza a chave pelo prefixo público;
4. recusa chaves inexistentes, expiradas ou revogadas;
5. confere o segredo contra `secret_hash`;
6. atualiza os metadados de uso;
7. injeta o model no request usando o atributo configurado;
8. autoriza quando a chave possui qualquer ability declarada.

Falhas de autenticação retornam HTTP 401. Uma chave válida sem as abilities
exigidas retorna HTTP 403. Middleware sem ability ou com parâmetro vazio
retorna HTTP 500.

Quando a rota recebe um recurso vinculado a um owner, o controller ainda deve
compará-lo com `$apiKey->owner` para evitar acesso horizontal entre owners.

## Rotas de ação do componente

O package carrega automaticamente suas próprias rotas de gerenciamento:

| URL padrão | Nome da rota | Função |
| --- | --- | --- |
| `/api-keys/{ownerAlias}/{owner}/keys` | `api-keys.keys.store` | Cria uma API Key. |
| `/api-keys/{ownerAlias}/{owner}/keys/{apiKey}/revoke` | `api-keys.keys.revoke` | Revoga uma API Key. |
| `/api-keys/{ownerAlias}/{owner}/keys/{apiKey}/renew` | `api-keys.keys.renew` | Cria uma nova chave e revoga a anterior. |

Essas rotas usam o middleware configurado em `api-keys.management.middleware`
e a ability configurada em `api-keys.management.ability`.

O package não registra rota GET para a interface. A aplicação hospedeira cria
a própria página, aplica a autorização de acesso à página e inclui
`<x-api-keys::manager>` para o owner correspondente.

## Identificador do criador

Na interface de gerenciamento, `created_by` recebe o `codpes` da USP retornado
por `getAuthIdentifier()` pelo usuário autenticado. O package armazena esse
valor como metadado numérico e não cria relacionamento com o model de usuário.

Na revogação, `revoked_by` recebe o mesmo tipo de identificador. A renovação
usa a operação de criação e revogação dentro de uma transação: a nova chave
recebe `created_by` e a anterior recebe `revoked_by`. Registros revogados
continuam disponíveis para consulta e não são removidos.
