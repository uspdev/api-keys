<?php

/*
|--------------------------------------------------------------------------
| Blocos de configuração
|--------------------------------------------------------------------------
| Os blocos maiores ficam definidos antes do array principal para facilitar
| a localização e a edição das opções mais usadas pela aplicação.
*/

$middleware = [
    'web',
    'auth',
];

$managementPage = [
    // A página completa pode ser desabilitada quando a aplicação usa somente
    // o componente Blade <x-api-keys::manager />.
    'enabled' => (bool) env('API_KEYS_MANAGEMENT_PAGE_ENABLED', true),

    // Layout fornecido pela aplicação hospedeira.
    'layout' => env('API_KEYS_MANAGEMENT_LAYOUT', 'layouts.app'),
];

$themeMenuItem = [
    'text' => '<i class="fas fa-key"></i> API Keys',

    // Quando nulo, o provider utiliza o valor configurado em "prefix".
    'url' => null,
];

$purposes = [
    'integration' => 'Integração',
    'ai' => 'IA',
];

$roles = [
    'viewer' => 'Visualizador',
    'collaborator' => 'Colaborador',
    'administrator' => 'Administrador',
];

return [
    // Aliases estáveis e classes dos models que podem possuir API Keys.
    // Exemplo: 'project' => App\\Models\\Project::class,
    'owners' => [],

    // Prefixo das rotas de gerenciamento fornecidas pelo package.
    'prefix' => 'api-keys',

    // Formato público da credencial.
    // Exemplo: gpp_4Y7KQ2.segredo
    'credential_prefix' => env('API_KEYS_CREDENTIAL_PREFIX', 'gpp'),

    // Tamanho do identificador público e do segredo da credencial.
    'public_prefix_length' => (int) env('API_KEYS_PUBLIC_PREFIX_LENGTH', 6),
    'secret_bytes' => (int) env('API_KEYS_SECRET_BYTES', 32),

    // Autenticação das rotas de negócio protegidas por API Key.
    'middleware' => [
        // Alias usado pela aplicação: Route::middleware('uspdevApiKeys').
        'alias' => env('API_KEYS_MIDDLEWARE_ALIAS', 'uspdevApiKeys'),

        // Atributo onde o middleware disponibiliza a chave autenticada.
        'request_attribute' => env('API_KEYS_REQUEST_ATTRIBUTE', 'apiKey'),
    ],

    // Permite API Key na query string somente quando explicitamente habilitado.
    // O uso do header Authorization: Bearer é o padrão recomendado.
    'query_parameter' => [
        'enabled' => (bool) env('API_KEYS_QUERY_PARAMETER_ENABLED', false),
        'name' => env('API_KEYS_QUERY_PARAMETER_NAME', 'api_key'),
    ],

    // Rotas e autorização da interface de gerenciamento do package.
    'management' => [
        'middleware' => $middleware,
        'ability' => 'manageApiKeys',
        'page' => $managementPage,
    ],

    // Integração opcional com o menu principal do laravel-usp-theme.
    'theme' => [
        'menu' => [
            'enabled' => (bool) env('API_KEYS_USP_THEME_MENU_ENABLED', false),
            'item' => $themeMenuItem,
        ],
    ],

    // Valores exibidos nos campos de propósito e papel da interface Blade.
    // A aplicação hospedeira pode adicionar ou alterar os valores conforme
    // sua estrutura, sem que o núcleo do package dependa deles.
    'interface' => [
        'purposes' => $purposes,
        'roles' => $roles,
    ],
];
