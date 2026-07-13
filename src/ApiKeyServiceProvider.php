<?php

namespace Uspdev\ApiKey;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Uspdev\ApiKey\Contracts\ApiKeyManager;
use Uspdev\ApiKey\Http\Middleware\AuthenticateApiKey;
use Uspdev\ApiKey\Services\ApiKeyService;

class ApiKeyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-key.php',
            'api-key'
        );

        $this->app->singleton(ApiKeyManager::class, ApiKeyService::class);
    }

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

