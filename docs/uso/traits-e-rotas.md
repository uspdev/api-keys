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

A aplicação pode autorizar uma operação com:

```php
$apiKey = request()->attributes->get('apiKey');

abort_unless($apiKey->allows('tasks.create'), 403);
```

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

## Rotas de negócio da aplicação

As rotas de negócio pertencem à aplicação hospedeira:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::middleware('uspdevApiKeys')
    ->prefix('api')
    ->group(function (): void {
        Route::get('projects/{project}/tasks', [TaskController::class, 'index'])
            ->name('projects.tasks.index');
    });
```

O middleware:

1. lê `Authorization: Bearer ...`;
2. valida o formato e localiza a chave pelo prefixo público;
3. recusa chaves inexistentes, expiradas ou revogadas;
4. confere o segredo contra `secret_hash`;
5. atualiza os metadados de uso;
6. injeta o model no request usando o atributo configurado.

Falhas de autenticação retornam HTTP 401. A ausência de uma ability deve ser
tratada pela aplicação e normalmente retorna HTTP 403.

## Rotas administrativas do package

O package carrega automaticamente suas próprias rotas administrativas:

| URL padrão | Nome da rota | Função |
| --- | --- | --- |
| `/api-keys` | `api-keys.admin.index` | Lista aliases registrados. |
| `/api-keys/{ownerAlias}` | `api-keys.admin.owners` | Lista owners autorizados. |
| `/api-keys/{ownerAlias}/{owner}` | `api-keys.admin.show` | Exibe o gerenciador do owner. |
| `/api-keys/{ownerAlias}/{owner}/keys` | `api-keys.keys.store` | Cria uma API Key. |
| `/api-keys/{ownerAlias}/{owner}/keys/{apiKey}/revoke` | `api-keys.keys.revoke` | Revoga uma API Key. |
| `/api-keys/{ownerAlias}/{owner}/keys/{apiKey}/renew` | `api-keys.keys.renew` | Cria uma nova chave e revoga a anterior. |

Essas rotas usam o middleware configurado em `api-keys.management.middleware`
e a ability configurada em `api-keys.management.ability`.

## Identificador do criador

Na interface administrativa, `created_by` recebe o `codpes` da USP retornado
por `getAuthIdentifier()` pelo usuário autenticado. O package armazena esse
valor como metadado numérico e não cria relacionamento com o model de usuário.

Na revogação, `revoked_by` recebe o mesmo tipo de identificador. A renovação
usa a operação de criação e revogação dentro de uma transação: a nova chave
recebe `created_by` e a anterior recebe `revoked_by`. Registros revogados
continuam disponíveis para consulta e não são removidos.
