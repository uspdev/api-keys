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

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'api-keys');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->registerUspThemeMenu();

        $this->publishes([
            __DIR__ . '/../config/api-key.php' => config_path('api-key.php'),
        ], 'api-key-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'api-key-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/api-keys'),
        ], 'api-key-views');
    }

    /** Acrescenta a página ao menu do USP Theme sem substituir itens da aplicação. */
    private function registerUspThemeMenu(): void
    {
        if (
            ! (bool) config('api-key.theme.menu.enabled', false)
            || ! (bool) config('api-key.management.page.enabled', true)
            || ! config()->has('usp-theme.menu')
        ) {
            return;
        }

        $menu = config('usp-theme.menu');

        if (! is_array($menu)) {
            return;
        }

        $item = (array) config('api-key.theme.menu.item', []);
        $url = $item['url'] ?? null;

        if (! is_string($url) || $url === '') {
            $url = trim((string) config('api-key.prefix', 'api-keys'), '/');
        }

        if (! isset($item['text']) || ! is_string($item['text']) || $item['text'] === '') {
            $item['text'] = 'API Keys';
        }

        $item['url'] = $url;

        foreach ($menu as $menuItem) {
            if (is_array($menuItem) && ($menuItem['url'] ?? null) === $url) {
                return;
            }
        }

        config(['usp-theme.menu' => [...$menu, $item]]);
    }
}
