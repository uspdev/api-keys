# Guia rápido

## Requisitos

- PHP 8.3 ou superior;
- Laravel 12;
- uma aplicação com sessão e autenticação configuradas para a interface administrativa;
- models Eloquent que possam possuir API Keys.

## Instalação

Instale o package:

```bash
composer require uspdev/api-keys
```

Publique a configuração, a migration e, opcionalmente, as views:

```bash
php artisan vendor:publish --tag=api-keys-config
php artisan vendor:publish --tag=api-keys-migrations
php artisan vendor:publish --tag=api-keys-views
```

Execute a migration:

```bash
php artisan migrate
```

## Configuração mínima

No `config/api-keys.php` publicado, registre os models que podem possuir
chaves:

```php
'owners' => [
    'project' => App\Models\Project::class,
],
```

O model precisa usar pelo menos `HasApiKeys`:

```php
use Uspdev\ApiKeys\Traits\HasApiAbilities;
use Uspdev\ApiKeys\Traits\HasApiKeys;

class Project extends Model
{
    use HasApiKeys;
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

Para detalhes sobre as traits e as rotas, consulte
[traits-e-rotas.md](traits-e-rotas.md).

## Interface de gerenciamento

A interface pode ser incorporada em qualquer página da aplicação:

```blade
<x-api-keys::manager :owner="$project" />
```

O usuário logado precisa ter a ability configurada em
`api-keys.management.ability`, que por padrão é `manageApiKeys`.

Também existe uma página administrativa pronta. Com o prefixo padrão, ela
fica disponível em:

```text
/api-keys
/api-keys/project
/api-keys/project/15
```

A página usa o layout configurado em `api-keys.management.page.layout`. A
aplicação hospedeira deve fornecer esse layout.

## Ciclo de vida administrativo

Renovar uma API Key significa criar uma nova credencial e revogar a anterior
em uma única operação. A nova chave mantém o nome, o `purpose`, o `role` e a
data de expiração da chave anterior; o token completo é exibido uma única vez,
como na criação normal.

A renovação só está disponível para chaves ativas. Para uma chave expirada,
crie uma nova chave com a validade desejada. A chave anterior permanece na
listagem com o status correspondente e nunca é excluída nem recebe soft delete.

`revoked_by` armazena o identificador numérico do usuário responsável pela
revogação. A renovação grava o mesmo identificador como `created_by` da nova
chave e `revoked_by` da chave anterior. Nenhum motivo de revogação é persistido.

## Rotas de negócio protegidas

O package registra o alias `uspdevApiKeys`, mas a aplicação deve aplicá-lo às
suas próprias rotas:

```php
Route::middleware('uspdevApiKeys')
    ->get('/api/projects/{project}/tasks', [TaskController::class, 'index']);
```

O cliente envia a credencial no header:

```http
Authorization: Bearer gpp_v1_ABC123.segredo
```

Após a autenticação, a API Key fica disponível em:

```php
$apiKey = request()->attributes->get('apiKey');
```

A aplicação deve verificar a ability necessária:

```php
abort_unless($apiKey->allows('tasks.read'), 403);
```

O middleware autentica a chave, mas não substitui o usuário da sessão e não
autoriza automaticamente as operações de negócio.
