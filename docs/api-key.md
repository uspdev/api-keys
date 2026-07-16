# Gerenciador de API Keys para Integrações e IA

Este componente provê uma infraestrutura robusta e segura para a gestão de chaves de API associadas a projetos. O sistema foi projetado para suportar tanto integrações de dados tradicionais quanto o consumo de contexto otimizado para Inteligência Artificial (LLMs).

## 🚀 Funcionalidades

*   **Gestão Centralizada:** Uma única tabela para autenticação de diversos tipos de integração.
*   **Segurança:** Armazenamento de chaves utilizando hash, garantindo que a chave original nunca fique exposta no banco de dados.
*   **Propósito:** Suporte para retornos estruturados (`integration`) ou contextos consolidados para IA (`ai`).
*   **Controle de Acesso:** Permissões baseadas em papéis (RBAC) herdadas diretamente do projeto.
*   **Rastreabilidade:** Monitoramento de contagem de acessos e data do último uso.

## 📊 Estrutura do Banco de Dados

A biblioteca utiliza a tabela `uspdev_api_keys` com a seguinte estrutura:

# Estrutura da Tabela `uspdev_api_keys`

| Campo          | Tipo                | Descrição                                                           |
| -------------- | ------------------- | ------------------------------------------------------------------- |
| `id`           | bigint              | Identificador único da chave.                                       |
| `owner_type`   | string              | Classe do modelo proprietário (relação polimórfica do Laravel).     |
| `owner_id`     | bigint              | Identificador do objeto proprietário.                               |
| `name`         | string              | Nome amigável da chave (ex.: *Power BI*, *NotebookLM*).             |
| `purpose`      | string              | Finalidade da chave (`integration`, `ai`, `webhook`, etc.).         |
| `role`         | string              | Papel associado (`viewer`, `collaborator`, `administrator`).        |
| `prefix`       | string              | Identificador público utilizado para localizar rapidamente a chave. |
| `secret_hash`  | string              | Hash do segredo (Argon2id/Bcrypt).                                  |
| `access_count` | bigint              | Quantidade de utilizações da chave.                                 |
| `last_used_at` | timestamp           | Data e hora da última utilização.                                   |
| `last_used_ip` | string              | Último endereço IP utilizado.                                       |
| `expires_at`   | timestamp, nullable | Data de expiração da chave.                                         |
| `revoked_at`   | timestamp, nullable | Data de revogação da chave.                                         |
| `created_by`   | bigint, nullable    | Usuário responsável pela criação da chave.                          |
| `created_at`   | timestamp           | Data de criação do registro.                                        |
| `updated_at`   | timestamp           | Data da última atualização do registro.                             |

## Índices

| Tipo        | Campos                   | Objetivo                                             |
| ----------- | ------------------------ | ---------------------------------------------------- |
| Primary Key | `id`                     | Identificação única da chave.                        |
| Index       | `owner_type`, `owner_id` | Busca eficiente das chaves pertencentes a um objeto. |
| Unique      | `prefix`                 | Garante unicidade do identificador público da chave. |
| Index       | `purpose`                | Filtragem por finalidade.                            |
| Index       | `role`                   | Filtragem por papel.                                 |
| Index       | `expires_at`             | Consulta de chaves expiradas.                        |
| Index       | `revoked_at`             | Consulta de chaves revogadas.                        |

## Exemplo de chave gerada

```text
gpp_4Y7KQ2.wKQ2zGd6KxF9u8rP1M5L8yR3N6...
```

Onde:

* `gpp` identifica o tipo de credencial.
* `4Y7KQ2` é o **prefixo público**, armazenado em texto e indexado.
* O trecho após o `.` é o **segredo**, armazenado apenas como hash (`secret_hash`).


## 🔑 Papéis e Permissões ENUM (`role`)

As permissões são integradas ao modelo de segurança do sistema:

*   **Viewer:** Acesso limitado a leitura de dados.
*   **Collaborator:** Permissão para leitura e escrita.
*   **Administrator:** Controle total sobre os recursos do projeto.

## 🤖 Comportamentos de Consumo ENUM (`purpose`)

O backend adapta a resposta com base no propósito da chave utilizada:

1.  **`integration`**: Retorna dados JSON brutos e estruturados (ex: listas de tarefas e reuniões), ideal para sistemas externos e ERPs.
2.  **`ai`**: Retorna um contexto consolidado e humanizado, com resumos e omissão de campos técnicos irrelevantes, otimizando o consumo de tokens por LLMs.
3. Outras purposes futuras.

## Middleware

```php
Route::middleware('uspdevApiKeys')->group(function () {
});
```

O middleware autentica a credencial, verifica expiração e revogação, atualiza
os metadados de uso e injeta a instância de `ApiKey` no request:

```php
$apiKey = request()->attributes->get('apiKey');
```

Ele não recebe abilities como parâmetro e não autoriza operações de negócio.
A aplicação hospedeira deve consultar a ability necessária depois da
autenticação:

```php
abort_unless($apiKey->allows('tasks.create'), 403);
```

Assim, uma falha de autenticação retorna HTTP 401, enquanto uma chave válida
sem a ability exigida normalmente resulta em HTTP 403.

## 📡 Endpoints de Exemplo

### Autenticação
As chaves devem ser enviadas via Header ou, em casos excepcionais de ferramentas limitadas, via Query String:
*   **Header:** `Authorization: Bearer gpp_xxx`
*   **Query:** `?api_key=gpp_xxx`

### Rotas
Rotas pertencem à aplicação

*   **Dados Estruturados:** `GET /api/projects/{id}/tasks`
*   **Contexto de IA:** `GET /api/ai/project-context`

## ⚡ Performance e Cache
Cache pertence à aplicação se for o caso.

Para garantir baixa latência no consumo por IA, a biblioteca implementa um sistema de **cache agressivo** por projeto (`ai_context:project:{id}`). O cache é automaticamente invalidado sempre que houver alterações em:
*   Tarefas
*   Reuniões
*   Notas e Documentos

## 🛡️ Segurança ao Criar Chaves

Ao gerar uma nova chave, o sistema exibe o valor em texto puro **apenas uma vez**. O desenvolvedor deve certificar-se de salvar o token imediatamente, pois apenas o hash será armazenado no sistema.


# Interface de Gerenciamento de API Keys

A biblioteca fornece componentes de interface para criação, administração e revogação de API Keys diretamente pela aplicação hospedeira. A interface foi projetada para seguir o padrão visual dos componentes do ecossistema **USPdev** e integrar-se ao sistema de permissões do projeto.

## Aba **Integrações**

Cada objeto proprietário (projeto, formulário, workflow etc.) pode disponibilizar uma aba **Integrações**, responsável pelo gerenciamento das API Keys vinculadas.

```
Projeto
└── Configurações
    └── Integrações
```

A tela apresenta todas as chaves cadastradas para o objeto.

| Nome       | Tipo        | Papel        | Último acesso | Status |
| ---------- | ----------- | ------------ | ------------- | ------ |
| NotebookLM | AI          | Viewer       | Ontem         | Ativa  |
| Power BI   | Integration | Viewer       | Hoje          | Ativa  |
| ERP        | Integration | Collaborator | 2h atrás      | Ativa  |

Cada registro permite visualizar informações operacionais da chave, como:

* nome da integração;
* finalidade da chave;
* nível de permissão;
* data do último uso;
* situação (ativa, expirada ou revogada).

Opcionalmente, a aplicação pode disponibilizar ações como:

* visualizar detalhes;
* revogar chave;
* renovar chave;
* excluir registros revogados.

---

## Criação de uma API Key

A criação de novas chaves é realizada por meio de um modal simples.

```
Nova API Key

Nome
[ NotebookLM                ]

Tipo
( ) Integração
(*) IA

Papel
(*) Visualizador
( ) Colaborador
( ) Administrador

Expiração
[ Nunca ▼ ]

[ Cancelar ]
[ Criar chave ]
```

Os campos possuem os seguintes significados:

| Campo         | Descrição                                              |
| ------------- | ------------------------------------------------------ |
| **Nome**      | Identificação amigável da integração.                  |
| **Tipo**      | Define o comportamento da API (`integration` ou `ai`). |
| **Papel**     | Nível de acesso concedido pela chave.                  |
| **Expiração** | Data opcional para expiração automática da credencial. |

Após a confirmação, a biblioteca gera uma chave criptograficamente segura, armazena apenas seu hash e registra os metadados associados.

---

## Exibição da chave

Por motivos de segurança, a chave é apresentada **uma única vez** imediatamente após sua criação.

```
API Key criada com sucesso

gpp_v1_8H4K...Y7P2

⚠️ Esta chave será exibida apenas uma vez.
```

Após o fechamento da janela, a chave não poderá mais ser recuperada, pois apenas seu hash permanece armazenado no banco de dados.

Caso a credencial seja perdida, deverá ser criada uma nova chave e a anterior poderá ser revogada.

---

## Informações adicionais

A interface também pode apresentar informações complementares para cada chave, como:

* data de criação;
* usuário responsável pela criação;
* data da última utilização;
* quantidade de acessos realizados;
* data de expiração;
* motivo da revogação (quando aplicável).

Essas informações auxiliam na auditoria, rastreabilidade e governança das integrações.

---

## Segurança

A interface segue as seguintes práticas de segurança:

* a API Key nunca é armazenada em texto puro;
* apenas o hash do segredo é persistido;
* a chave é exibida somente uma vez;
* chaves revogadas deixam de autenticar imediatamente;
* chaves expiradas são recusadas automaticamente;
* todas as utilizações podem ser auditadas por meio dos metadados de acesso.



# Integração com o Sistema de Autorização

A biblioteca **não implementa um sistema próprio de permissões**. Em vez disso, ela utiliza o modelo de autorização definido pelo objeto proprietário (projeto, formulário, workflow etc.).

Cada API Key possui apenas um **papel** (`role`), enquanto as permissões efetivas são fornecidas pelo objeto ao qual a chave está associada.

Essa abordagem evita duplicação de regras e garante que qualquer alteração nas permissões do sistema seja refletida automaticamente em todas as API Keys.

## Contrato de abilities do owner

O owner utiliza a trait `HasApiAbilities` e implementa `abilities()` para
declarar quais abilities cada papel concede. Esse método define o mapa de
permissões; ele não é o ponto de entrada recomendado para autorizar uma
requisição.

```php
use Uspdev\ApiKeys\Traits\HasApiAbilities;

class Project extends Model
{
    use HasApiAbilities;

    public function abilities(string $role): array
    {
        return match ($role) {
            'administrator' => [
                'tasks.create',
                'tasks.update',
                'tasks.delete',
                'meetings.create',
                'meetings.update',
                'settings.manage',
            ],

            'collaborator' => [
                'tasks.create',
                'tasks.update',
                'meetings.read',
            ],

            'viewer' => [
                'tasks.read',
                'meetings.read',
            ],

            default => [],
        };
    }
}
```

O wildcard `*` concede todas as abilities ao papel. Um papel desconhecido deve
retornar um array vazio.

## API pública de autorização

Depois que o middleware autenticar a credencial, a aplicação deve usar
`ApiKey::allows()` como API pública única para verificar uma operação:

```php
$apiKey = request()->attributes->get('apiKey');

abort_unless($apiKey->allows('tasks.create'), 403);
```

`ApiKey::allows()` utiliza o `role` armazenado na chave, solicita ao owner o
array retornado por `abilities()` e verifica a ability, incluindo o wildcard
`*`. A aplicação não precisa passar o papel manualmente nem consultar esse
array diretamente.

Não existem APIs alternativas como `allowsApiAbility()` ou `authorize()`. Essa
separação mantém uma responsabilidade clara para cada método:

```text
Owner::abilities($role)   define as abilities do papel
ApiKey::allows($ability)  verifica a ability da credencial autenticada
```

## Fluxo de autorização

Quando uma requisição autenticada é recebida, o fluxo fica dividido entre o
package e a aplicação hospedeira:

```text
Middleware uspdevApiKeys
    │
    ├── autentica a credencial
    └── injeta ApiKey no request
            │
            ▼
Aplicação hospedeira
    │
    └── $apiKey->allows('tasks.create')
            │
            ▼
API Key
    │
    ├── owner_type
    ├── owner_id
    └── role
            │
            ▼
Objeto proprietário (Project, Form, Workflow...)
            │
            ▼
abilities(role)
            │
            ▼
Permissões efetivas
            │
            ▼
Autorização da operação
```

Por exemplo, suponha uma API Key com:

```text
role = collaborator
```

Ao tentar criar uma tarefa, a aplicação consulta a própria credencial:

```php
$apiKey->allows('tasks.create');
```

Internamente, a credencial resolve o owner e consulta as abilities do papel
`collaborator`:

```php
[
    'tasks.create',
    'tasks.update',
    'meetings.read',
]
```

Como `tasks.create` está presente, `allows()` retorna `true`. Se a ability não
estiver presente, o método retorna `false` e a aplicação decide a resposta
adequada, normalmente HTTP 403.

## Vantagens

Essa arquitetura apresenta diversas vantagens:

* Não há duplicação de permissões nas API Keys.
* O objeto proprietário continua sendo a única fonte de verdade para autorização.
* Alterações na política de acesso são refletidas automaticamente em todas as chaves.
* A biblioteca permanece desacoplada do mecanismo de autorização da aplicação.
* O mesmo componente pode ser utilizado por diferentes tipos de objetos.

## Boas práticas

Recomenda-se utilizar papéis genéricos e consistentes em toda a aplicação:

| Papel           | Descrição                                   |
| --------------- | ------------------------------------------- |
| `viewer`        | Permissões de leitura.                      |
| `collaborator`  | Permissões de leitura e escrita.            |
| `administrator` | Controle total sobre os recursos do objeto. |

Cada aplicação é livre para mapear esses papéis para as permissões que desejar, mantendo a biblioteca totalmente independente da implementação de autorização.

## Exemplo completo

```php
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

public function store(Request $request): JsonResponse
{
    $apiKey = $request->attributes->get('apiKey');

    abort_unless($apiKey->allows('tasks.create'), Response::HTTP_FORBIDDEN);

    // A criação da tarefa pertence à aplicação hospedeira.

    return response()->json(['message' => 'Tarefa criada.'], Response::HTTP_CREATED);
}
```

Dessa forma, o package fornece o mecanismo genérico de consulta por
`ApiKey::allows()`, enquanto o owner continua sendo a fonte de verdade das
abilities e a aplicação hospedeira decide quais operações exigem cada ability.


# Exemplo de Consumo da API

A seguir é apresentado um exemplo de criação de uma tarefa utilizando uma API Key gerada pela biblioteca.

## Criando uma tarefa

### Requisição

```http
POST /api/projects/15/tasks HTTP/1.1
Host: projetos.exemplo.br
Authorization: Bearer gpp_v1_8H4KQ2.WKQ2zGd6KxF9u8rP1M...
Content-Type: application/json
```

```json
{
  "title": "Implementar autenticação OAuth",
  "description": "Integrar login utilizando Senha Única.",
  "status": "todo",
  "priority": "high",
  "assigned_to": 27,
  "due_date": "2026-08-15",
  "labels": [
    "backend",
    "oauth"
  ]
}
```

### Resposta

**HTTP 201 Created**

```json
{
  "id": 342,
  "title": "Implementar autenticação OAuth",
  "description": "Integrar login utilizando Senha Única.",
  "status": "todo",
  "priority": "high",
  "assigned_to": {
    "id": 27,
    "name": "Maria Silva"
  },
  "created_at": "2026-07-06T10:15:32Z",
  "updated_at": "2026-07-06T10:15:32Z",
  "_links": {
    "self": "/api/tasks/342",
    "project": "/api/projects/15"
  }
}
```

---

## Exemplo utilizando cURL

```bash
curl -X POST https://projetos.exemplo.br/api/projects/15/tasks \
  -H "Authorization: Bearer gpp_v1_8H4KQ2.WKQ2zGd6KxF9u8rP1M..." \
  -H "Content-Type: application/json" \
  -d '{
        "title":"Implementar autenticação OAuth",
        "description":"Integrar login utilizando Senha Única.",
        "priority":"high",
        "assigned_to":27,
        "due_date":"2026-08-15"
      }'
```

---

## Permissões

A API Key utilizada deve possuir permissão para criar tarefas. Isso pode ser controlado por meio do papel (`role`) ou de permissões granulares (`scopes`), conforme a política de autorização da aplicação.

Exemplo:

```json
{
  "purpose": "integration",
  "role": "collaborator",
}
```

Uma chave destinada ao consumo por Inteligência Artificial (`purpose = "ai"`) normalmente possui apenas permissões de leitura e acesso ao contexto consolidado, enquanto integrações com sistemas externos (ERP, Power BI, automações, etc.) podem receber permissões de escrita conforme necessário.

```json
{
  "purpose": "ai",
  "role": "viewer",
}
```




