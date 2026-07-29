# API Key

Este componente provê uma infraestrutura robusta e segura para a gestão de chaves de API associadas a projetos. O sistema foi projetado para suportar tanto integrações de dados tradicionais quanto o consumo de contexto otimizado para Inteligência Artificial (LLMs).

## 🚀 Funcionalidades

- **Gestão Centralizada:** Uma única tabela para autenticação de diversos tipos de integração.
- **Segurança:** Armazenamento de chaves utilizando hash, garantindo que a chave original nunca fique exposta no banco de dados.
- **Propósito:** Suporte para retornos estruturados (`integration`) ou contextos consolidados para IA (`ai`).
- **Controle de Acesso:** Permissões baseadas em papéis (RBAC) herdadas diretamente do projeto.
- **Rastreabilidade:** Monitoramento de contagem de acessos e data do último uso.

## Instalação

### 1. **Instale a biblioteca pelo Composer**

Execute o comando abaixo para instalar o pacote:

```bash
composer require uspdev/api-keys
```

### 2. **Publique a configuração e as migrations**

```bash
php artisan vendor:publish --tag=api-keys-config
php artisan vendor:publish --tag=api-keys-migrations
```

### 3. **Execute as migrations**

```bash
php artisan migrate
```

## Requirements

- PHP 8.3 ou superior;
- Laravel 12;

## Configuração

A biblioteca pode ser configurada editando o arquivo `config/api-keys.php`, publicado durante a instalação.

Consulte o [índice da documentação de uso](docs/uso/index.md) para exemplos de configuração, traits, rotas e views.

### Componente Blade

Registre os owners permitidos usando aliases estáveis na configuração:

```php
'owners' => [
    'project' => App\Models\Project::class,
],
```

O model deve utilizar `HasApiKeys`. Depois, incorpore o gerenciador na página desejada:

```blade
<x-api-keys::manager :owner="$project" />
```

O package fornece a tabela, criação, exibição única do token, revogação e renovação. As ações usam as rotas POST `api-keys.keys.store`, `api-keys.keys.revoke` e `api-keys.keys.renew`, sob o prefixo configurado em `api-keys.prefix`.

As rotas de gerenciamento usam `web` e `auth` por padrão e exigem a ability
`manageApiKeys` no usuário logado para o owner. A aplicação pode ajustar o
middleware e o nome da ability em `api-keys.management`.

Chaves ativas podem ser renovadas pela interface. A ação reabre o mesmo modal
usado na criação, preenchido com nome, `purpose`, `role` e expiração da chave
atual, para que esses dados possam ser ajustados. Ao confirmar, o package cria
a nova credencial com os valores informados, revoga a anterior na mesma
transação e exibe o novo token uma única vez. Registros revogados permanecem
visíveis, sem exclusão ou soft delete; `revoked_by` registra o identificador
numérico do usuário responsável.

### Página na aplicação hospedeira

O package não fornece rotas GET, layout, menu ou páginas completas. A aplicação
hospedeira deve criar a página, protegê-la e renderizar o componente para o
owner que ela decidiu expor. Também cabe à aplicação definir o item de menu.

Em uma aplicação que use o `laravel-usp-theme`, inclua explicitamente o bloco
DataTable na própria view:

~~~blade
@extends('laravel-usp-theme::master')

@include('laravel-usp-theme::blocos.datatable-simples')

@section('content')
  <x-api-keys::manager :owner="$project" owner-alias="project" />
@endsection
~~~

As submissões do componente usam diretamente as rotas POST registradas pelo
package; não recrie as rotas de criação, renovação ou revogação na aplicação.

## Contribuições

Contribuições são bem-vindas. Para contribuir:

1. Faça um fork do repositório;
2. Crie uma branch para sua alteração;
3. Implemente e teste as mudanças;
4. Envie seus commits para o fork;
5. Abra um Pull Request.

## Licença

Este pacote é distribuído sob a licença GPL-2.0-or-later.
