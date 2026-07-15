## Resumo das decisões sobre o pacote de API Keys

### Objetivo

O pacote será uma biblioteca Laravel reutilizável para gerenciar API Keys associadas a qualquer modelo da aplicação hospedeira, como:

- projetos;
- formulários;
- workflows;
- outros modelos Eloquent.

Ele cuidará da infraestrutura genérica de autenticação e gerenciamento das credenciais, sem conhecer as regras específicas de negócio de cada aplicação.

---

## Responsabilidades do pacote

O pacote fornecerá:

- migration e model `ApiKey`;
- relacionamento polimórfico com o objeto proprietário;
- geração criptograficamente segura da chave;
- armazenamento somente do hash do segredo;
- exibição da chave completa apenas no momento da criação;
- autenticação por API Key;
- validação de expiração e revogação;
- atualização de informações de uso;
- revogação e gerenciamento das chaves;
- middleware Laravel;
- traits para integração com models;
- serviços reutilizáveis;
- controllers para as ações administrativas;
- componentes e partials Blade prontos;
- página administrativa completa opcional.

O pacote não será responsável pelas rotas de negócio da aplicação, como:

```text
/api/projects/{id}/tasks
/api/ai/project-context
```

Essas rotas continuam pertencendo à aplicação hospedeira.

---

## Responsabilidades da aplicação hospedeira

A aplicação que instalar o pacote será responsável por:

- definir quais models podem possuir API Keys;
- definir as permissões disponíveis;
- mapear os papéis para abilities;
- decidir quem pode criar e revogar chaves;
- criar as rotas de negócio;
- produzir respostas específicas para integração ou IA;
- implementar cache e invalidação;
- decidir como utilizar o campo `purpose`.

Portanto, o pacote apenas disponibiliza informações como:

```php
$apiKey->purpose;
$apiKey->role;
$apiKey->owner;
```

A aplicação decide como responder com base nelas.

O pacote não deve conhecer tarefas, reuniões, documentos ou contextos para LLM.

---

## Arquitetura em camadas

A implementação terá três níveis.

### 1. Núcleo independente de interface

Contém:

```text
Models
Migrations
Services
Middleware
Traits
Eventos
Configuração
```

Uma aplicação não poderá usar somente essa parte, sem utilizar as views do pacote.

### 2. Componentes Blade incorporáveis

Será a forma principal de integração da interface.

Exemplo:

```blade
<x-api-keys::manager :owner="$project" />
```

A aplicação escolhe em qual página ou aba deseja exibir o gerenciador.

O componente poderá apresentar:

- tabela de chaves;
- status da credencial;
- último uso;
- quantidade de acessos;
- botão de criação;
- modal de criação;
- modal de exibição única da chave;
- ação de revogação;
- detalhes operacionais.

Exemplo de uso:

```blade
@extends('layouts.app')

@section('content')
    <h1>{{ $project->name }}</h1>

    <x-api-keys::manager :owner="$project" />
@endsection
```

### 3. Página administrativa opcional

O pacote também poderá fornecer uma página completa para aplicações que não desejem montar sua própria interface.

Essa página reutilizará os mesmos componentes Blade, evitando duas implementações diferentes.

---

## Estrutura provável das views

```text
resources/views/
├── components/
│   ├── manager.blade.php
│   ├── key-table.blade.php
│   ├── create-modal.blade.php
│   ├── secret-modal.blade.php
│   └── status-badge.blade.php
├── admin/
│   ├── index.blade.php
│   └── show.blade.php
└── layouts/
```

A interface deve utilizar o padrão visual do ecossistema USPdev, o `laravel-usp-theme`.

---

## Traits dos models proprietários

Provavelmente serão utilizadas duas traits com responsabilidades diferentes.

### `HasApiKeys`

Responsável pelo relacionamento polimórfico:

```php
use Uspdev\ApiKeys\Traits\HasApiKeys;

class Project extends Model
{
    use HasApiKeys;
}
```

Permitirá operações como:

```php
$project->apiKeys();
$project->apiKeys;
```

### `HasApiAbilities`

Responsável por delegar a autorização ao objeto proprietário:

```php
use Uspdev\ApiKeys\Traits\HasApiAbilities;

class Project extends Model
{
    use HasApiKeys;
    use HasApiAbilities;

    public function abilities(string $role): array
    {
        return match ($role) {
            'viewer' => [
                'tasks.read',
                'meetings.read',
            ],

            'collaborator' => [
                'tasks.read',
                'tasks.create',
                'tasks.update',
            ],

            'administrator' => ['*'],

            default => [],
        };
    }
}
```

A API Key guarda apenas o papel:

```text
viewer
collaborator
administrator
```

As permissões efetivas continuam sendo definidas pelo owner.

---

## Autenticação e autorização

O middleware `uspdevApiKeys` é responsável por autenticar a
credencial, verificar sua validade e disponibilizar a instância de
`ApiKey` no request:

```php
$apiKey = request()->attributes->get('apiKey');
````

A biblioteca identifica o owner e o papel associado à chave:

```php
$owner = $apiKey->owner;
$role = $apiKey->role;
```

A autorização das operações de negócio permanece sob responsabilidade
da aplicação hospedeira. O owner fornece as abilities correspondentes
ao papel:

```php
if (! $owner->allowsApiAbility($role, 'tasks.create')) {
    abort(403);
}
```

A interface de gerenciamento das API Keys também deve respeitar o
sistema de autorização da aplicação hospedeira. O mecanismo concreto
para controlar quem pode criar, revogar ou administrar chaves ainda
precisa ser definido.


Portanto, o ponto realmente pendente não é a autorização da API Key. Essa está bem explicada. O que falta definir é **como o usuário logado será autorizado a utilizar as views de gerenciamento fornecidas pelo pacote**.



---

## Identificação segura dos owners

O pacote não deve aceitar uma classe PHP arbitrária pela URL ou request, como:

```text
owner_type=App\Models\Project
```

Os tipos permitidos devem ser registrados pela aplicação:

```php
// config/api-keys.php

'owners' => [
    'project' => App\Models\Project::class,
    'form' => App\Models\Form::class,
    'workflow' => App\Models\Workflow::class,
],
```

Assim, uma eventual página completa poderá usar uma rota segura:

```text
/api-keys/project/15
```

O alias `project` será convertido para a classe permitida pela configuração.

---

## Segurança da credencial

O formato definitivo ainda precisa ser padronizado, mas seguirá a ideia:

```text
gpp_v1_<prefixo>.<segredo>
```

Exemplo:

```text
gpp_v1_8H4KQ2M9.WKQ2zGd6KxF9u8rP1M...
```

O banco armazenará:

- prefixo público;
- hash do segredo;
- metadados da credencial.

O segredo completo:

- será mostrado apenas uma vez;
- não será armazenado em texto puro;
- não aparecerá em logs;
- não aparecerá em exceptions;
- não será recuperável posteriormente;
- não será armazenado no activity log.

Caso o usuário perca a chave, deverá criar outra e revogar a anterior.

---

## `purpose`

O campo `purpose` será um metadado da chave, por exemplo:

```text
integration
ai
webhook
```

Ele não fará o pacote alterar automaticamente a resposta.

Exemplo:

```php
if ($apiKey->purpose === 'ai') {
    // A aplicação gera o contexto otimizado.
}
```

A geração de contexto para IA, serialização dos dados e otimização de tokens continuarão pertencendo à aplicação.

## Valores de `role` e `purpose`

`role` e `purpose` são valores extensíveis definidos pela aplicação
hospedeira. No núcleo do pacote, ambos são persistidos como strings e o
serviço não impõe uma lista fixa de valores.

A interface administrativa utiliza `api-keys.interface.purposes` e
`api-keys.interface.roles` para definir os valores apresentados e validados.
A aplicação pode substituir essas listas pelos seus próprios enums, roles e
permissions, incluindo os valores fornecidos pela Senha Única. O pacote
apenas armazena e disponibiliza esses valores; a aplicação hospedeira define
seu significado e como aplicá-los na autorização e no comportamento associado
ao `purpose`.

## Exibição temporária do token

Após a criação, o token completo é criptografado com o encrypter da aplicação
e enviado em uma flash session. O componente só tenta descriptografá-lo quando
o alias e o identificador do owner armazenados na sessão correspondem ao
owner que está sendo renderizado. Depois do consumo, o valor é removido da
sessão.

Se o redirecionamento não renderizar o owner correspondente, o token não será
exibido em outro owner e a flash session expirará no ciclo seguinte. Por isso,
o token poderá ser perdido caso a página de destino não contenha o componente
correto, exigindo a criação de uma nova chave.

---

## Cache

O cache não será implementado pelo pacote de API Keys.

A aplicação hospedeira conhece seus próprios recursos e poderá utilizar algo como:

```text
ai_context:project:{id}
```

Também será responsabilidade da aplicação invalidar o cache quando tarefas, reuniões, documentos ou outras entidades forem alteradas.

---

## Estrutura inicial do pacote

Seguindo o padrão do ecossistema USPdev, a estrutura inicial do pacote será:

```text
config/
    api-keys.php

database/
    migrations/

resources/
    views/
        components/
        admin/

routes/
    web.php

src/
    ApiKeyServiceProvider.php

    Console/
        Commands/

    Http/
        Controllers/
        Middleware/

    Models/
        ApiKey.php

    Providers/
        EventServiceProvider.php

    Services/

    Traits/
        HasApiKeys.php
        HasApiAbilities.php

```

O Service Provider principal ficará diretamente em:

```text
src/ApiKeyServiceProvider.php
```

Providers auxiliares, como o responsável pelo menu do tema, ficarão em:

```text
src/Providers/
```

---

## Pontos ainda pendentes de definição

Ainda precisam ser fechados durante o desenvolvimento:

- namespace do package no plural: `Uspdev\\ApiKeys`; classes de entidade, como `ApiKey`, permanecem no singular conforme a convenção do Laravel
- formato exato e tamanho de cada trecho da chave;
- se o middleware aceitará abilities, como:

```php
Route::middleware('uspdev.api-keys:tasks.create');
```

- se `owner_id` aceitará apenas bigint ou também UUID/ULID;
- se a auditoria será somente agregada ou terá histórico por requisição;
- se autenticação por query string será suportada.

## Decisões fechadas sobre o ciclo de vida administrativo

- Renovar significa criar uma nova API Key e revogar a anterior em uma única
  operação transacional.
- A renovação copia nome, `purpose`, `role` e validade da chave anterior e só
  pode ser executada para uma chave ativa.
- Registros revogados permanecem disponíveis para visualização e não podem ser
  excluídos pelo package.
- O model não usa soft delete.
- A revogação persiste `revoked_at` e `revoked_by`, sem motivo de revogação.
