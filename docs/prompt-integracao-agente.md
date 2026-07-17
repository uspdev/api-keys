# Contexto copiável para implementar `uspdev/api-keys`

Este documento serve como um **contexto técnico complementar** para uma IA ou
agente de código. Primeiro, escreva normalmente o que deseja implementar no
outro projeto. Depois, cole o conteúdo da seção **Texto para copiar**.

O seu pedido define **o que deve ser construído**. O texto deste documento
ensina ao agente **como usar corretamente este package para construir aquilo**.

## Como usar

Escreva primeiro um pedido específico, por exemplo:

> Implemente API Keys nos projetos. Quero uma aba de integrações na tela do
> projeto, com os papéis leitor e editor. Leitor pode consultar tarefas e
> editor também pode criá-las. Crie o endpoint de listagem e proteja-o com API
> Key. Somente administradores do projeto podem gerenciar as chaves.

Logo depois do seu pedido, cole integralmente o texto da próxima seção. Não é
necessário editar ou preencher campos dentro dele. O agente deve obter nomes de
models, rotas, regras e comportamentos a partir do seu pedido e do código do
projeto onde trabalhará.

---

## Texto para copiar

### Contexto técnico obrigatório sobre o package de API Keys

Use o pedido escrito antes deste contexto como fonte de verdade para definir o
que deve ser implementado e como a funcionalidade deve se comportar. Os
exemplos abaixo servem apenas para ensinar a integração com o package; não
substitua os models, papéis, abilities, endpoints ou regras solicitados por
exemplos genéricos.

Antes de alterar o projeto, leia integralmente as instruções locais, como
`AGENTS.md`, e inspecione seu `composer.json`, `composer.lock`, models,
policies, autenticação, rotas, controllers, views e padrões existentes. Faça a
integração seguindo a arquitetura já usada pela aplicação.

O package é `uspdev/api-keys`, para PHP 8.3+ e Laravel 12. Seu namespace é
`Uspdev\ApiKeys` e o provider
`Uspdev\ApiKeys\ApiKeyServiceProvider` possui autodiscovery. Confirme a versão
real instalada; se ela divergir deste contexto, preserve a API pública daquela
versão e relate a diferença.

Quando o código-fonte do package estiver disponível, consulte também:

- `docs/api-key.md`;
- `docs/uso/`;
- `config/api-keys.php`;
- `src/ApiKeyServiceProvider.php`;
- `src/Models/ApiKey.php`;
- `src/Http/Middleware/AuthenticateApiKey.php`;
- `src/Traits/HasApiKeys.php`;
- `src/Traits/HasApiAbilities.php`.

### O que o package já resolve

Não replique estas funcionalidades dentro da aplicação hospedeira. O package
já fornece:

- tabela e model para API Keys;
- relacionamento polimórfico entre a chave e qualquer model owner compatível;
- geração criptograficamente segura da credencial;
- prefixo público e segredo separado;
- armazenamento somente do hash do segredo;
- entrega do token completo apenas na criação ou renovação;
- autenticação por `Authorization: Bearer`;
- recusa de chave inexistente, inválida, expirada ou revogada;
- atualização de contagem, data e IP do último acesso;
- middleware com alias padrão `uspdevApiKeys`;
- criação, renovação e revogação;
- traits para relacionamento e abilities;
- componentes Blade e páginas de gerenciamento.

A credencial tem formato semelhante a:

```text
gpp_ABC123.segredo
```

O prefixo `ABC123` pode ser armazenado e exibido. O segredo existe em texto
puro somente durante sua entrega inicial. O banco guarda `secret_hash`, nunca
o segredo original.

### O que deve ser implementado na aplicação

Use o pedido do usuário para implementar as partes que pertencem ao projeto
hospedeiro:

- quais models podem possuir API Keys;
- quem pode visualizar e gerenciar as chaves de cada owner;
- quais papéis estarão disponíveis;
- quais abilities cada papel concede;
- quais rotas de negócio aceitarão API Key;
- controllers, services e responses dessas rotas;
- o significado e o comportamento de cada `purpose`;
- eventual produção de contexto para IA;
- cache e invalidação, caso tenham sido solicitados;
- posição da interface de gerenciamento no projeto;
- integração visual com o layout já existente.

O package não conhece regras de tarefas, projetos, formulários, documentos,
reuniões, workflows ou inteligência artificial. Ele também não cria
automaticamente endpoints de negócio nem muda a resposta conforme `purpose`.
Esses comportamentos devem ser implementados na aplicação somente quando o
pedido do usuário os exigir.

Se o pedido tratar apenas de gerenciamento de chaves, não invente endpoints de
negócio. Se pedir uma API, contexto de IA ou integração externa, implemente
essas funcionalidades no projeto e use o package apenas para autenticação e
autorização da credencial.

### 1. Instalação

Primeiro, verifique se o package já está instalado e se seus recursos já foram
publicados. Execute apenas o que ainda for necessário:

```bash
composer require uspdev/api-keys
php artisan vendor:publish --tag=api-keys-config
php artisan vendor:publish --tag=api-keys-migrations
php artisan migrate
```

As views devem ser publicadas somente se o pedido exigir customização do HTML
fornecido pelo package:

```bash
php artisan vendor:publish --tag=api-keys-views
```

Não altere arquivos em `vendor/` e não use `--force` sem antes verificar se a
aplicação possui arquivos publicados e customizados.

### 2. Configuração dos owners

No `config/api-keys.php`, registre cada model que poderá possuir chaves usando
um alias estável. Use os models citados no pedido ou descobertos no domínio da
aplicação.

Exemplo ilustrativo:

```php
'owners' => [
    'project' => App\Models\Project::class,
],
```

O alias aparece nas rotas administrativas e impede que classes arbitrárias
sejam informadas pela URL. Não crie aliases para models que não participarão da
funcionalidade solicitada.

Configure também os valores exibidos na interface. `purpose` e `role` são
strings, não regras implementadas automaticamente:

```php
'interface' => [
    'purposes' => [
        'integration' => 'Integração',
        'ai' => 'IA',
    ],
    'roles' => [
        'viewer' => 'Visualizador',
        'collaborator' => 'Colaborador',
        'administrator' => 'Administrador',
    ],
],
```

Adapte ou reduza esses valores conforme o pedido. Não mantenha opções que a
aplicação não entende apenas porque aparecem no exemplo.

Mantenha a autenticação por header Bearer como padrão. Só habilite API Key na
query string se o usuário pedir explicitamente ou se o cliente externo não
aceitar headers:

```dotenv
API_KEYS_QUERY_PARAMETER_ENABLED=true
```

Tokens em query string podem aparecer em histórico, logs, proxies e headers de
referência. Se esse modo não for necessário, mantenha-o desabilitado.

### 3. Integração com o model owner

O model que possuir chaves deve usar `HasApiKeys`:

```php
use Uspdev\ApiKeys\Traits\HasApiKeys;

class Project extends Model
{
    use HasApiKeys;
}
```

Isso disponibiliza:

```php
$project->apiKeys();
$project->apiKeys;
```

Se as chaves forem usadas para acessar rotas de negócio, use também
`HasApiAbilities` e implemente `abilities()` com as regras solicitadas:

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

Esse exemplo não define os papéis do projeto. Crie a matriz real a partir do
pedido do usuário e das operações existentes. Um papel desconhecido deve
sempre retornar `[]`. Use o wildcard `*` apenas quando acesso total tiver sido
realmente solicitado.

Não consulte `abilities()` diretamente no controller. A API pública de
autorização é:

```php
$apiKey->allows('tasks.read');
```

Se o projeto usa morph map, preserve sua configuração. A migration atual usa
`morphs('owner')`, com `owner_id` numérico. Antes de migrar, confirme que a
chave primária do owner é compatível. Se o owner usa UUID ou ULID, não altere a
migration silenciosamente: informe a incompatibilidade e proponha a adaptação
necessária.

### 4. Autorização da interface de gerenciamento

Gerenciar chaves é diferente de utilizar uma chave em uma API. A interface usa
o usuário autenticado da aplicação e, por padrão, verifica:

```php
$user->can('manageApiKeys', $owner);
```

Implemente `manageApiKeys` na Policy do owner ou no Gate, seguindo o mecanismo
de autorização que o projeto já utiliza. A regra deve considerar a instância
do owner. Por exemplo, um usuário autorizado em um projeto não deve ganhar
acesso às chaves de todos os outros projetos.

O nome da ability pode ser alterado em:

```php
'management' => [
    'ability' => 'manageApiKeys',
],
```

As páginas completas fornecidas pelo package usam, por padrão:

```text
/api-keys
/api-keys/{ownerAlias}
/api-keys/{ownerAlias}/{owner}
```

Elas dependem do layout configurado em
`api-keys.management.page.layout`, com padrão `layouts.app`.

Quando o pedido determinar que o gerenciamento fique dentro da tela do owner,
incorpore o componente Blade na página indicada:

```blade
<x-api-keys::manager :owner="$project" />
```

Se necessário, informe o alias explicitamente:

```blade
<x-api-keys::manager
    :owner="$project"
    owner-alias="project"
/>
```

Autorize o usuário no controller ou na view **antes de renderizar o
componente**. O package autoriza as ações de criação, renovação e revogação,
mas a aplicação continua responsável pela autorização da página onde o
componente foi incorporado.

Se a aplicação usar somente o componente incorporado, a página completa pode
ser desabilitada:

```php
'management' => [
    'page' => [
        'enabled' => false,
    ],
],
```

Para muitos owners, prefira o componente dentro da página do próprio recurso.
A página geral do package carrega os owners e filtra em memória quais o usuário
pode gerenciar, o que pode não ser adequado para grandes volumes.

Os campos de auditoria `created_by` e `revoked_by` são numéricos. A interface
usa `getAuthIdentifier()` quando seu valor pode ser convertido para inteiro;
caso contrário, esses campos ficam nulos. Não crie um relacionamento com o
model de usuário sem que isso tenha sido solicitado e projetado.

### 5. Rotas de negócio autenticadas por API Key

As rotas de negócio pertencem à aplicação. Aplique o middleware do package às
rotas solicitadas:

```php
Route::middleware('uspdevApiKeys')
    ->prefix('api')
    ->group(function (): void {
        Route::get(
            'projects/{project}/tasks',
            [TaskController::class, 'index']
        );
    });
```

O middleware autentica a chave, mas não decide se ela pode executar a operação.
Depois da autenticação, a instância de `ApiKey` fica no atributo configurado do
request, cujo nome padrão é `apiKey`:

```php
/** @var \Uspdev\ApiKeys\Models\ApiKey $apiKey */
$apiKey = $request->attributes->get(
    config('api-keys.middleware.request_attribute', 'apiKey')
);
```

Em cada endpoint relacionado a um owner, faça obrigatoriamente duas
verificações:

1. a chave pertence ao mesmo owner acessado na rota;
2. a chave possui a ability necessária.

Exemplo:

```php
public function index(Request $request, Project $project): JsonResponse
{
    /** @var \Uspdev\ApiKeys\Models\ApiKey $apiKey */
    $apiKey = $request->attributes->get(
        config('api-keys.middleware.request_attribute', 'apiKey')
    );

    abort_unless($apiKey->owner?->is($project), 403);
    abort_unless($apiKey->allows('tasks.read'), 403);

    return response()->json(
        $project->tasks()->get()
    );
}
```

Adapte o controller, a consulta e a resposta ao pedido do usuário. O exemplo de
tarefas não deve ser copiado se outro domínio foi solicitado.

Não basta verificar somente `$apiKey->allows()`. Uma chave pertencente ao owner
A poderia ter a mesma ability existente no owner B. Sem comparar o owner, isso
pode permitir acesso horizontal indevido.

Quando o endpoint não recebe o owner pela rota, obtenha o recurso a partir de
`$apiKey->owner` e confirme que o tipo de model é o esperado antes de executar
a regra de negócio.

O resultado esperado para falhas é:

- HTTP 401: token ausente, malformado, inválido, expirado ou revogado;
- HTTP 403: token válido, mas owner incorreto ou ability insuficiente.

### 6. Implementação de `purpose`

O campo `purpose` não altera o comportamento sozinho. Se o pedido disser que
uma chave de integração deve retornar JSON estruturado ou que uma chave de IA
deve retornar contexto consolidado, implemente essa diferença na aplicação.

Exemplo de decisão explícita no código da aplicação:

```php
return match ($apiKey->purpose) {
    'integration' => $this->structuredResponse($apiKey->owner),
    'ai' => $this->aiContextResponse($apiKey->owner),
    default => abort(403),
};
```

Não use esse exemplo se o pedido definir endpoints separados ou outro desenho.
O importante é não presumir que o package já produz respostas, resumos ou
contextos. Services, Resources, validações, cache e invalidação pertencem ao
projeto hospedeiro.

`purpose` também não concede permissão. Mesmo quando seu valor for `ai`, a
ability exigida pela operação deve continuar sendo verificada.

### 7. Criação programática, quando necessária

Normalmente as chaves serão criadas pela interface fornecida. Se o pedido
exigir criação por outro fluxo, reutilize o contrato do package:

```php
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

$created = app(ApiKeyManager::class)->create(
    owner: $project,
    name: 'Integração externa',
    purpose: 'integration',
    role: 'viewer',
);

$plainTextToken = $created->plainTextToken();
```

O token deve ser entregue imediatamente e uma única vez. Não duplique a lógica
de geração, hash ou persistência do package.

### 8. Segurança obrigatória

Durante toda a implementação:

- nunca persista o token completo em outra tabela;
- nunca tente recuperar o segredo a partir do hash;
- nunca registre token, URL com token ou `secret_hash` em logs, exceptions,
  activity logs, dumps ou ferramentas de monitoramento;
- nunca exponha `secret_hash` em JSON, Resources ou views;
- nunca coloque secrets reais em exemplos ou testes;
- se o token for perdido, crie outro e revogue o anterior;
- considere renovação como criação de uma nova chave e revogação da antiga;
- mantenha registros revogados e expirados para rastreabilidade;
- mantenha query string desabilitada sempre que Bearer header for possível;
- não instale integrações opcionais apenas por pertencerem ao ecossistema
  USPdev.

### 9. Como conduzir a implementação

Implemente apenas o escopo pedido pelo usuário, mas conclua todas as etapas
técnicas necessárias para que ele funcione de verdade. Não pare após instalar
o Composer package se o pedido também exigir interface, autorização ou
endpoints.

Quando uma informação estiver clara no pedido ou puder ser descoberta no
código, prossiga sem pedir confirmação. Quando uma decisão ausente mudar
materialmente a segurança ou a regra de negócio, explique a dúvida e não
invente silenciosamente uma política definitiva.

Preserve APIs públicas e padrões do projeto. Não altere o código do package
para acomodar uma regra exclusiva da aplicação. Não duplique model, tabela,
middleware ou serviço de credenciais no projeto hospedeiro.

### 10. Validação final

Ao terminar, verifique os itens aplicáveis ao pedido:

- package instalado e provider descoberto;
- configuração e migration publicadas sem sobrescrever customizações;
- migration aplicada uma única vez;
- aliases resolvem somente os owners configurados;
- owners usam as traits necessárias;
- roles correspondem às abilities solicitadas;
- papéis desconhecidos não recebem acesso;
- policy ou Gate protege o gerenciamento por owner;
- página ou componente aparece no local solicitado;
- token completo é exibido somente após criação ou renovação;
- rota de negócio usa o middleware de API Key;
- controller compara o owner e verifica a ability;
- token inválido retorna 401;
- owner incorreto e ability ausente retornam 403;
- chave expirada ou revogada não autentica;
- requisição válida atualiza os metadados de acesso;
- nenhum segredo aparece em persistência, logs ou respostas posteriores;
- comportamento de `purpose`, resposta e cache correspondem exatamente ao
  pedido do usuário.

Execute os formatadores, analisadores e testes já existentes e relevantes na
aplicação. Não declare sucesso sem informar os comandos e seus resultados.

Ao concluir, entregue:

- resumo da implementação;
- arquivos criados e alterados;
- configuração de owners, purposes, roles e abilities;
- interface e autorização adotadas;
- rotas protegidas e comportamento implementado;
- decisões e ambiguidades encontradas;
- comandos executados;
- resultado das validações;
- pendências reais;
- saída resumida de `git status`.

### Fim do contexto técnico
