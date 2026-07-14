<?php

return [
    // Versão do Bootstrap esperada pela aplicação hospedeira caso ela forneça
    // uma interface administrativa. O núcleo atual não depende desta opção;
    // ela é mantida para compatibilidade com integrações visuais futuras.
    'bootstrapVersion' => 4,

    // Mapeia aliases estáveis para os models que podem possuir API Keys.
    // A aplicação hospedeira deve publicar os aliases que utilizará.
    'owners' => [],

    // Prefixo das rotas administrativas fornecidas pelo package.
    'prefix' => 'api-keys',

    // Prefixo público que identifica o tipo da credencial. O valor "gpp" vem
    // dos exemplos de API key descritos em docs/api-key.md.
    'credential_prefix' => env('API_KEY_CREDENTIAL_PREFIX', 'gpp'),

    // Versão do formato público da credencial. A versão fica entre o prefixo
    // do package e o identificador público para permitir evolução futura.
    'credential_version' => env('API_KEY_CREDENTIAL_VERSION', 'v1'),

    // Quantidade de caracteres do identificador público armazenado na coluna
    // "prefix". A documentação mostra um prefixo público de seis caracteres,
    // que também possui índice único para localizar a chave.
    'public_prefix_length' => (int) env('API_KEY_PUBLIC_PREFIX_LENGTH', 6),

    // Quantidade de bytes aleatórios usados para gerar o segredo. A
    // documentação exige uma chave criptograficamente segura e determina que
    // apenas seu hash seja persistido; 32 bytes é o padrão inicial adotado.
    'secret_bytes' => (int) env('API_KEY_SECRET_BYTES', 32),

    'middleware' => [
        // Alias do middleware citado na documentação: Route::middleware('uspdevApiKey').
        'alias' => env('API_KEY_MIDDLEWARE_ALIAS', 'uspdevApiKey'),

        // Atributo da requisição onde o middleware disponibiliza a API key
        // autenticada, conforme request()->attributes->set('apiKey', ...).
        'request_attribute' => env('API_KEY_REQUEST_ATTRIBUTE', 'apiKey'),
    ],

    'query_parameter' => [
        // A documentação permite query string apenas excepcionalmente. O
        // padrão seguro é exigir o header Authorization: Bearer e deixar esta
        // alternativa desativada para evitar exposição da chave em URLs.
        'enabled' => (bool) env('API_KEY_QUERY_PARAMETER_ENABLED', false),

        // Nome do parâmetro previsto na documentação: ?api_key=...
        'name' => env('API_KEY_QUERY_PARAMETER_NAME', 'api_key'),
    ],

    // Protege as rotas da interface e delega a autorização do owner à aplicação.
    'management' => [
        'middleware' => ['web', 'auth'],
        'ability' => 'manageApiKeys',
    ],

    // Define os valores apresentados nos campos do componente Blade.
    // A aplicação hospedeira pode adicionar ou alterar os valores conforme sua estrutura
    // de papéis e propósitos. O núcleo do package não depende destes valores.
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

];
