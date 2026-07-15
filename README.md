# API Key

Este componente provê uma infraestrutura robusta e segura para a gestão de chaves de API associadas a projetos. O sistema foi projetado para suportar tanto integrações de dados tradicionais quanto o consumo de contexto otimizado para Inteligência Artificial (LLMs).

## 🚀 Funcionalidades

- **Gestão Centralizada:** Uma única tabela para autenticação de diversos tipos de integração.
- **Segurança:** Armazenamento de chaves utilizando hash, garantindo que a chave original nunca fique exposta no banco de dados.
- **Propósito:** Suporte para retornos estruturados (`integration`) ou contextos consolidados para IA (`ai`).
- **Controle de Acesso:** Permissões baseadas em papéis (RBAC) herdadas diretamente do projeto.
- **Rastreabilidade:** Monitoramento de contagem de acessos e data do último uso.

## Installation

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

O package fornece a tabela, criação, exibição única do token e revogação. As rotas dessas ações usam o prefixo configurado em `api-keys.prefix` e os middlewares configurados em `api-keys.management`, seguindo o padrão dos packages USPdev.

As rotas administrativas usam `web` e `auth` por padrão e exigem a ability
`manageApiKeys` no usuário logado para o owner. A aplicação pode ajustar o
middleware e o nome da ability em `api-keys.management`.

### Página administrativa opcional

O package também fornece uma página completa que reutiliza o mesmo componente
Blade do gerenciador:

~~~text
/api-keys
/api-keys/project
/api-keys/project/15
~~~

O primeiro endereço lista os aliases configurados em `api-keys.owners`; o
segundo lista os owners autorizados para o alias; e o terceiro exibe a tela de
gerenciamento do owner. A página usa `layouts.app` por padrão, compatível com o
layout visual fornecido pelo `laravel-usp-theme`, e pode ser ajustada ou
desabilitada:

~~~php
'management' => [
    'page' => [
        'enabled' => true,
        'layout' => 'layouts.app',
    ],
],
~~~

O layout deve ser fornecido pela aplicação hospedeira. A autorização continua
sendo feita pela ability configurada em `api-keys.management.ability` para cada
owner.

### Integração com o laravel-usp-theme

Para adicionar automaticamente a página ao menu principal configurado em
`config/usp-theme.php`, habilite a integração no `config/api-keys.php` publicado:

~~~php
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
~~~

Quando `url` não for informado, o package utiliza o valor de
`api-keys.prefix`. O item é acrescentado ao menu existente em
`usp-theme.menu`; os demais itens não são alterados. A integração é
desabilitada por padrão e só funciona quando o theme disponibiliza essa
configuração.

O campo `can` é opcional e deve usar uma ability global da aplicação, como
`admin`. A ability `manageApiKeys` normalmente depende de um owner específico
e, por isso, não deve ser usada diretamente no item global da navbar.

## Contribuições

Contribuições são bem-vindas. Para contribuir:

1. Faça um fork do repositório;
2. Crie uma branch para sua alteração;
3. Implemente e teste as mudanças;
4. Envie seus commits para o fork;
5. Abra um Pull Request.

## Licença

Este pacote é distribuído sob a licença GPL-2.0-or-later.
