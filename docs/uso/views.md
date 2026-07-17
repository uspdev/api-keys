# Views e componentes Blade

## Componente principal

O componente recomendado para incorporar o gerenciador em uma página da
aplicação é:

```blade
<x-api-keys::manager :owner="$project" />
```

Quando houver mais de um model da mesma classe ou quando o alias não puder ser
resolvido automaticamente, informe o alias:

```blade
<x-api-keys::manager
    :owner="$project"
    owner-alias="project"
/>
```

O owner precisa estar registrado em `api-keys.owners` e usar `HasApiKeys`.

## O que o componente apresenta

O `manager` reúne os seguintes recursos:

- tabela das API Keys vinculadas ao owner;
- `purpose`, `role` e status;
- último uso e quantidade de acessos;
- detalhes operacionais;
- modal de criação;
- validação de expiração;
- modal para exibir o token completo uma única vez;
- ação de revogação;
- ação de renovação para chaves ativas.

Quando a aplicação ativa o bloco `datatable-simples` do `laravel-usp-theme`, a
tabela recebe busca, ordenação, responsividade e paginação de 10 registros no
navegador. Sem o bloco, ela permanece disponível como uma tabela HTML
responsiva, sem paginação automática.

## Componentes internos

| View | Responsabilidade |
| --- | --- |
| `api-keys::components/manager` | Coordena a interface e os modais. |
| `api-keys::components/key-table` | Lista chaves, status, metadados, renovação e revogação. |
| `api-keys::components/create-modal` | Formulário de criação. |
| `api-keys::components/details-modal` | Exibe os metadados da chave sem alterar o layout da tabela. |
| `api-keys::components/secret-modal` | Exibe o token temporário após a criação. |
| `api-keys::components/status-badge` | Renderiza ativa, expirada ou revogada. |

O token completo é criptografado na flash session e removido após o consumo.
Depois que a modal é fechada, ele não pode ser recuperado.

A renovação cria um novo token com os metadados da chave ativa, revoga a chave
anterior e exibe o novo token pela mesma modal de uso único. Chaves revogadas
e expiradas permanecem na tabela para consulta; o package não oferece exclusão
nem soft delete.

## Publicação e customização

Para copiar as views para a aplicação:

```bash
php artisan vendor:publish --tag=api-keys-views
```

O destino padrão é:

```text
resources/views/vendor/api-keys/
```

A aplicação pode ajustar o HTML, classes CSS e textos publicados sem alterar
o model ou o serviço de autenticação.

## Página de gerenciamento pronta

O package fornece páginas de gerenciamento opcionais que reutilizam o mesmo
componente:

| View | URL padrão | Função |
| --- | --- | --- |
| `api-keys::management/index` | `/api-keys` | Lista os aliases configurados. |
| `api-keys::management/index` | `/api-keys/{ownerAlias}` | Lista os owners autorizados. |
| `api-keys::management/show` | `/api-keys/{ownerAlias}/{owner}` | Gerencia as chaves de um owner. |

A página completa é habilitada por padrão e pode ser desabilitada:

```php
'management' => [
    'page' => [
        'enabled' => false,
    ],
],
```

## Layout da aplicação hospedeira

As páginas prontas usam o layout configurado em
`api-keys.management.page.layout`:

```php
'management' => [
    'page' => [
        'layout' => 'layouts.app',
    ],
],
```

O layout não é fornecido pelo package. A aplicação deve disponibilizar a
view e as seções usadas pelas páginas:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<html>
  <head>
    <title>@yield('title')</title>
  </head>
  <body>
    @yield('content')
  </body>
</html>
```

O package fornece apenas o conteúdo de gerenciamento e utiliza classes
compatíveis com o Bootstrap/theme da aplicação.

As páginas de gerenciamento prontas carregam o bloco automaticamente quando o
`laravel-usp-theme` está instalado. Ao incorporar apenas o componente
`<x-api-keys::manager>` em uma página da aplicação, inclua o bloco no layout:

```blade
@extends('laravel-usp-theme::master')

@include('laravel-usp-theme::blocos.datatable-simples')
```

A página de owners e a tabela de API Keys utilizam paginação local de 10
registros. Todos os registros continuam sendo carregados pelo Laravel; não há
paginação de consulta ao banco.
