
## Objetivo do projeto

Este repositório contém um package Laravel para gerenciamento de API Keys vinculadas a modelos proprietários por relacionamento polimórfico.

O package deve fornecer uma solução reutilizável para:

- criar API Keys;
- exibir o token completo apenas uma vez;
- armazenar somente o hash do segredo;
- autenticar requisições;
- verificar expiração e revogação;
- registrar metadados de uso;
- relacionar as chaves a qualquer model Eloquent compatível;
- delegar a autorização para o objeto proprietário;
- disponibilizar views e componentes Blade prontos para gerenciamento.

A biblioteca não deve conhecer regras de negócio específicas de projetos, formulários, workflows, tarefas, reuniões, documentos ou contextos para LLM.

---

## Fonte de verdade

Antes de alterar o código, leia integralmente:

```text
docs/api-key.md
```

Esse arquivo é a principal fonte de requisitos funcionais e de domínio.

```text
docs/initial-decisions.md
```
Também devem ser considerados:

- `composer.json`;
- estrutura atual do repositório;
- migrations existentes;
- Service Provider;
- README;
- padrões já consolidados no código.

Não altere `docs/api-key.md` sem solicitação explícita.

Quando houver conflito entre este arquivo e `docs/api-key.md`, preserve o comportamento documentado e relate a divergência.

Quando a documentação estiver ambígua, não invente silenciosamente uma regra definitiva. Implemente apenas o que estiver claro e registre a pendência.

---

## Referência arquitetural

O package `uspdev/forms` é uma referência de organização e integração com Laravel, especialmente para:

- autoload PSR-4;
- autodiscovery do Service Provider;
- configuração publicável;
- migrations publicáveis;
- rotas carregadas automaticamente;
- views Blade;
- controllers;
- services;
- models;
- comandos Artisan;
- integração com o ecossistema USPdev.

Não copie entidades, regras, controllers, views ou fluxos específicos de formulários.

---

## Responsabilidades do package

O package é responsável por:

- model e migration de API Key;
- geração criptograficamente segura da credencial;
- separação entre prefixo público e segredo;
- hash seguro do segredo;
- autenticação da chave;
- verificação de expiração;
- verificação de revogação;
- relacionamento polimórfico com o owner;
- registro de `access_count`, `last_used_at` e `last_used_ip`;
- middleware de autenticação;
- traits de integração com models;
- serviços de criação, autenticação e revogação;
- views e componentes Blade de gerenciamento;
- controllers e rotas administrativas necessárias à interface;
- publicação de configuração e migrations;
- eventos úteis para integração, quando documentados.

---

## Responsabilidades da aplicação hospedeira

A aplicação que instalar o package é responsável por:

- definir quais models podem possuir API Keys;
- implementar as abilities de cada papel;
- definir as rotas de negócio protegidas;
- decidir o comportamento associado ao `purpose`;
- gerar respostas estruturadas ou contextos para IA;
- implementar cache e invalidação;
- definir quem pode acessar a interface de gerenciamento;
- definir quem pode criar, revogar ou administrar chaves;
- integrar as páginas ou componentes ao fluxo visual da aplicação.

A biblioteca não implementa um sistema próprio de permissões de negócio.

---

## Estrutura esperada

Preserve a estrutura atual do repositório. Quando novos arquivos forem necessários, siga preferencialmente:

```text
config/
    api-keys.php

database/
    migrations/

docs/
    api-key.md

resources/
    views/
        admin/
        components/

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

Não crie diretórios vazios apenas para reproduzir essa árvore.

O Service Provider principal deve ficar diretamente em `src/`, seguindo o padrão do package de referência.

Providers auxiliares podem ficar em `src/Providers/`.

---

## Identidade do package

Não renomeie o package, namespace, chave de configuração ou namespace de views sem verificar o estado atual do repositório.

Antes de criar classes, confirme:

- nome em `composer.json`;
- namespace PSR-4;
- classe registrada em `extra.laravel.providers`;
- nome do Service Provider;
- chave da configuração;
- namespace das views;
- prefixo das rotas;
- alias do middleware.

Todos esses elementos devem permanecer consistentes.

---

## Rotas

As rotas de gerenciamento pertencentes ao package podem ficar em `routes/web.php`.

As rotas de negócio da aplicação não pertencem ao package.

Não crie `routes/api.php` apenas porque o package gerencia API Keys.

Todas as rotas do package devem:

- ter prefixo configurável;
- ter nomes prefixados;
- usar middleware configurável;
- usar controllers;
- evitar closures;
- validar aliases de owner;
- respeitar a autorização da aplicação hospedeira.

---

## Configuração

Centralize opções em `config/api-keys.php` ou no nome já adotado pelo repositório.

Possíveis configurações:

- prefixo de rotas;
- middleware;
- alias do middleware;
- owners permitidos;
- formato e prefixo das credenciais;
- tamanho do prefixo;
- tamanho do segredo;
- query string;
- integração com menu;
- aparência;
- comportamento de auditoria.

Use `env()` somente dentro do arquivo de configuração.

Não adicione opções sem uso real.

---

## Ecossistema USPdev

Integrações com:

- `laravel-usp-theme`;
- `uspdev/replicado`;
- `spatie/laravel-activitylog`;

devem ser adicionadas somente quando estiverem exigidas pela documentação ou efetivamente utilizadas pelo código.

Não copie dependências do `uspdev/forms` automaticamente.

Critérios:

- `laravel-usp-theme`: interface e menu;
- `spatie/laravel-activitylog`: auditoria de operações administrativas;
- `uspdev/replicado`: somente quando houver consulta institucional.

Nunca registre secrets no activity log.

---

## Estilo de código

Siga as convenções do projeto e do Laravel.

Regras gerais:

- PHP 8.3+;
- Laravel 12;
- PSR-4;
- namespaces coerentes com os caminhos;
- métodos e propriedades tipados;
- retornos explícitos;
- imports organizados;
- nomes claros;
- early returns quando melhorarem a leitura;
- evitar comentários que apenas repetem o código;
- evitar abstrações sem uso;
- evitar arquivos vazios;
- evitar duplicação;
- manter compatibilidade com o package hospedeiro.
- Adicionar breve comentário em português de docblock para cada método, propriedade pública e estrutura lógica complexa - em métodos comuns do Laravel não é necessario, como em Models, Migrations, funções padroes (casts por exemplo) etc.

Não renomeie APIs públicas sem necessidade.

---

## Testes

Não implementamos testes

### Provider

- merge da configuração;
- carregamento das views;
- carregamento das rotas;
- registro do middleware;
- publicação de configuração;
- publicação de migrations.

### Model e traits

- relacionamento polimórfico;
- casts;
- status ativo;
- status expirado;
- status revogado;
- abilities por role;
- wildcard, caso suportado.

### Serviços

- criação da chave;
- retorno do token apenas no momento da criação;
- persistência somente do hash;
- colisão de prefixo;
- autenticação válida;
- token inválido;
- chave expirada;
- chave revogada.

### Middleware

- header Bearer válido;
- header ausente;
- token malformado;
- query string habilitada;
- query string desabilitada;
- injeção da ApiKey no request;
- atualização dos metadados de uso.

### Interface

- listagem;
- modal de criação;
- exibição única;
- revogação;
- autorização administrativa;
- owner inválido;
- alias não permitido.

Nunca use secrets reais nos testes.

---

## Ao concluir uma tarefa

Apresente:

- resumo das alterações;
- arquivos criados;
- arquivos alterados;
- decisões tomadas;
- divergências ou ambiguidades encontradas;
- comandos executados;
- resultados dos testes;
- pendências;
- saída resumida de `git status`.

Se alguma validação falhar, informe claramente. Não esconda erros nem declare sucesso sem evidência.
