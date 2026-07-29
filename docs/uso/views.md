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
| `api-keys::components/create-modal` | Formulário único de criação e renovação. |
| `api-keys::components/details-modal` | Exibe os metadados da chave sem alterar o layout da tabela. |
| `api-keys::components/secret-modal` | Exibe o token temporário após a criação. |
| `api-keys::components/status-badge` | Renderiza ativa, expirada ou revogada. |

O token completo é criptografado na flash session e removido após o consumo.
Depois que a modal é fechada, ele não pode ser recuperado.

A renovação usa a mesma instância do modal de criação, preenchida no navegador
com os metadados da chave selecionada. Depois da edição e confirmação, ela cria
um novo token com os valores informados, revoga a chave anterior e exibe o novo
token pela modal de uso único. O modal não é repetido para cada linha da tabela.
Chaves revogadas e expiradas permanecem na tabela para consulta; o package não
oferece exclusão nem soft delete.

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

## Página da aplicação hospedeira

O package fornece somente o componente e suas rotas POST. A aplicação
hospedeira deve criar a rota GET, definir o layout e posicionar o componente
na página que fizer sentido para seu fluxo.

Quando usar `laravel-usp-theme`, inclua explicitamente o bloco DataTable na
view da aplicação:

```blade
@extends('laravel-usp-theme::master')

@include('laravel-usp-theme::blocos.datatable-simples')

@section('content')
  <x-api-keys::manager :owner="$project" owner-alias="project" />
@endsection
```

O item de menu também pertence à configuração do tema da aplicação. As ações
de criar, renovar e revogar não precisam ser recriadas: o componente usa as
rotas POST do package.
