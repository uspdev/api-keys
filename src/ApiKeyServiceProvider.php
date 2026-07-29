<?php

namespace Uspdev\ApiKeys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;
use Uspdev\ApiKeys\Http\Middleware\AuthenticateApiKey;
use Uspdev\ApiKeys\Models\ApiKey;
use Uspdev\ApiKeys\Services\ApiKeyService;

/** Registra os serviços e recursos publicáveis do pacote no Laravel. */
class ApiKeyServiceProvider extends ServiceProvider
{
    /** Registra a configuração e a implementação do gerenciador de chaves. */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-keys.php',
            'api-keys'
        );

        $this->app->singleton(ApiKeyManager::class, ApiKeyService::class);
    }

    /** Registra o middleware e disponibiliza configuração e migrations para publicação. */
    public function boot(Router $router): void
    {
        $alias = (string) config('api-keys.middleware.alias', 'uspdevApiKeys');
        $router->aliasMiddleware($alias, AuthenticateApiKey::class);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'api-keys');
        $this->registerManagerViewComposer();
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->publishes([
            __DIR__ . '/../config/api-keys.php' => config_path('api-keys.php'),
        ], 'api-keys-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'api-keys-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/api-keys'),
        ], 'api-keys-views');
    }

    /** Entrega à view incorporável os dados preparados pelo model da API Key. */
    private function registerManagerViewComposer(): void
    {
        View::composer('api-keys::components.manager', function (ViewInstance $view): void {
            $viewData = $view->getData();
            $owner = $viewData['owner'] ?? null;
            $ownerAlias = $viewData['ownerAlias'] ?? null;

            if (! $owner instanceof Model) {
                return;
            }

            $view->with(ApiKey::managerViewData(
                $owner,
                is_string($ownerAlias) ? $ownerAlias : null,
            ));
        });
    }

}
