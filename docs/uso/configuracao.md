# Configuração e variáveis de ambiente

O arquivo publicado pela instalação é:

```text
config/api-keys.php
```

As variáveis de ambiente são lidas somente nesse arquivo de configuração.
Não é necessário adicionar todas elas ao `.env`; os valores padrão podem ser
mantidos quando forem adequados.

## Owners

Registre os aliases permitidos pela interface administrativa:

```php
'owners' => [
    'project' => App\Models\Project::class,
    'form' => App\Models\Form::class,
],
```

O alias é usado nas URLs e impede que uma classe PHP arbitrária seja recebida
pela rota.

## Interface

Os valores de `purpose` e `role` são definidos pela aplicação hospedeira:

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

O package persiste esses valores como strings. A aplicação define o
significado deles e o mapeamento para suas permissions.

O ciclo administrativo não exige configuração adicional: a interface usa a
ability de `api-keys.management.ability` para criar, renovar e revogar. O
usuário autenticado deve fornecer um identificador numérico para que `created_by`
e `revoked_by` sejam registrados.

## Opções sem variável de ambiente

| Chave | Padrão | Uso |
| --- | --- | --- |
| `api-keys.prefix` | `api-keys` | Prefixo das rotas administrativas. |
| `api-keys.owners` | `[]` | Aliases e classes dos owners permitidos. |
| `api-keys.management.middleware` | `['web', 'auth']` | Middleware da interface administrativa. |
| `api-keys.management.ability` | `manageApiKeys` | Ability exigida para administrar um owner. |
| `api-keys.management.page.layout` | `layouts.app` | Layout da página administrativa. |
| `api-keys.interface.purposes` | Configurado no arquivo | Valores exibidos para `purpose`. |
| `api-keys.interface.roles` | Configurado no arquivo | Valores exibidos para `role`. |
| `api-keys.theme.menu.item` | Configurado no arquivo | Texto e URL do item de menu. |

## Variáveis de ambiente

| Variável | Padrão | Configuração |
| --- | --- | --- |
| `API_KEYS_MANAGEMENT_PAGE_ENABLED` | `true` | Habilita a página administrativa pronta. |
| `API_KEYS_MANAGEMENT_LAYOUT` | `layouts.app` | View de layout da página administrativa. |
| `API_KEYS_CREDENTIAL_PREFIX` | `gpp` | Prefixo público da credencial. |
| `API_KEYS_CREDENTIAL_VERSION` | `v1` | Versão do formato da credencial. |
| `API_KEYS_PUBLIC_PREFIX_LENGTH` | `6` | Tamanho do prefixo público. |
| `API_KEYS_SECRET_BYTES` | `32` | Quantidade mínima de bytes do segredo. |
| `API_KEYS_MIDDLEWARE_ALIAS` | `uspdevApiKeys` | Alias registrado para o middleware. |
| `API_KEYS_REQUEST_ATTRIBUTE` | `apiKey` | Atributo do request com a chave autenticada. |
| `API_KEYS_QUERY_PARAMETER_ENABLED` | `false` | Permite token via query string. |
| `API_KEYS_QUERY_PARAMETER_NAME` | `api_key` | Nome do parâmetro de query string. |
| `API_KEYS_USP_THEME_MENU_ENABLED` | `false` | Habilita a integração opcional com o USP Theme. |

## Exemplo de `.env`

```dotenv
API_KEYS_MANAGEMENT_PAGE_ENABLED=true
# Caso utilize o laravel-usp-theme
API_KEYS_MANAGEMENT_LAYOUT=laravel-usp-theme::master
API_KEYS_USP_THEME_MENU_ENABLED=true
API_KEYS_MIDDLEWARE_ALIAS=uspdevApiKeys
API_KEYS_REQUEST_ATTRIBUTE=apiKey
API_KEYS_QUERY_PARAMETER_ENABLED=false
```

Depois de alterar variáveis em uma aplicação com configuração cacheada,
execute:

```bash
php artisan config:clear
```

O uso de query string deve permanecer desabilitado quando o cliente puder
enviar o header `Authorization: Bearer`.
