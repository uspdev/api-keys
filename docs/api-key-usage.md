# Uso das páginas e componentes Blade

Este guia explica como configurar os owners, incorporar o gerenciador de API
Keys em uma página da aplicação e utilizar a página administrativa opcional.


## Componente principal

A forma recomendada de incorporar a interface é usar o componente manager:

```blade
@extends('layouts.app')

@section('content')
    <h1>{{ $project->name }}</h1>

    <x-api-keys::manager :owner="$project" />
@endsection
```

O alias pode ser informado explicitamente quando necessário:

```blade
<x-api-keys::manager
    :owner="$project"
    owner-alias="project"
/>
```

### Props

| Prop | Obrigatória | Descrição |
| --- | --- | --- |
| `owner` | Sim | Instância do model que possui as API Keys. |
| `owner-alias` | Não | Alias registrado em `api-key.owners`. Se omitido, é resolvido pela classe do model. |

O componente renderiza a tabela, o modal de criação, o modal de exibição
única do token e os badges de status. Ele também utiliza as rotas de criação
e revogação fornecidas pelo package.

### Componentes internos

Os arquivos abaixo são usados pelo `manager` e podem ser publicados para
customização visual:

| Componente | Responsabilidade |
| --- | --- |
| `manager` | Coordena a interface completa e os modais. |
| `key-table` | Exibe as chaves, metadados, status e ação de revogação. |
| `create-modal` | Formulário de criação de uma API Key. |
| `secret-modal` | Exibe o token em texto puro somente após a criação. |
| `status-badge` | Exibe `Ativa`, `Expirada` ou `Revogada`. |


## Página administrativa opcional

A página completa é habilitada por padrão e reutiliza o mesmo componente
`<x-api-keys::manager>` usado nas integrações incorporáveis.

| URL padrão | Nome da rota | Função |
| --- | --- | --- |
| `/api-keys` | `api-key.admin.index` | Lista os aliases configurados. |
| `/api-keys/project` | `api-key.admin.owners` | Lista os owners autorizados do alias. |
| `/api-keys/project/15` | `api-key.admin.show` | Abre o gerenciador do owner. |

O prefixo da URL pode ser alterado em `api-key.prefix`. Por exemplo, com o
prefixo `integrations`, a última URL será `/integrations/project/15`.

A configuração da página fica em `api-key.management.page`:

```php
'management' => [
    'middleware' => ['web', 'auth'],
    'ability' => 'manageApiKeys',
    'page' => [
        'enabled' => true,
        'layout' => 'layouts.app',
    ],
],
```

A aplicação hospedeira deve fornecer o layout configurado. O layout padrão é
`layouts.app` e deve disponibilizar, pelo menos, as seções `title` e `content`
usadas pelas views administrativas.

Para manter somente os componentes incorporáveis e remover as rotas de
navegação da página completa:

```php
'page' => [
    'enabled' => false,
],
```

As rotas POST usadas pelo componente para criar e revogar chaves continuam
disponíveis enquanto o middleware de gerenciamento estiver configurado.

## Link no menu do laravel-usp-theme

O package pode acrescentar a página ao menu principal do `laravel-usp-theme`.
Essa integração é opt-in e preserva os itens que já existem em
`config/usp-theme.php`:

```php
// config/api-key.php
'theme' => [
    'menu' => [
        'enabled' => true,
        'item' => [
            'text' => '<i class="fas fa-key"></i> API Keys',
            'url' => 'api-keys',
            'can' => 'admin',
        ],
    ],
],
```

O item será adicionado a `usp-theme.menu` durante o boot do package. Se `url`
for `null`, o valor de `api-key.prefix` será usado. Para remover o link,
mantenha `enabled` como `false`.

Use `can` somente com uma ability global reconhecida pelo theme. Como
`manageApiKeys` depende do owner que está sendo administrado, a recomendação é
usar uma ability global, como `admin`, na navbar e manter a autorização por
owner nas páginas e ações do package.

## Autorização

A aplicação hospedeira deve definir a ability de gerenciamento para o usuário
autenticado. O package verifica essa ability com o owner em:

- listagem de owners;
- abertura da página de um owner;
- criação de uma API Key;
- revogação de uma API Key.

Exemplo conceitual de uma Policy ou Gate:

```php
public function manageApiKeys(User $user, Project $project): bool
{
    return $user->canManageProject($project);
}
```

O package não define as regras de negócio, papéis dos usuários ou quais
owners cada usuário pode administrar.
