<?php

namespace Uspdev\ApiKey;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Uspdev\ApiKey\Contracts\ApiKeyManager;
use Uspdev\ApiKey\Http\Middleware\AuthenticateApiKey;
use Uspdev\ApiKey\Services\ApiKeyService;

/** Registra os serviços e recursos publicáveis do pacote no Laravel. */
class ApiKeyServiceProvider extends ServiceProvider
{
    /** Registra a configuração e a implementação do gerenciador de chaves. */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-key.php',
            'api-key'
        );

        $this->app->singleton(ApiKeyManager::class, ApiKeyService::class);
    }

    /** Registra o middleware e disponibiliza configuração e migrations para publicação. */
    public function boot(Router $router): void
    {
        $alias = (string) config('api-key.middleware.alias', 'uspdevApiKey');
        $router->aliasMiddleware($alias, AuthenticateApiKey::class);

        $this->publishes([
            __DIR__ . '/../config/api-key.php' => config_path('api-key.php'),
        ], 'api-key-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'api-key-migrations');
    }
}
