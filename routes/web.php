<?php

use Illuminate\Support\Facades\Route;
use Uspdev\ApiKeys\Http\Controllers\ApiKeyController;

Route::group([
    'prefix' => config('api-keys.prefix'),
    'middleware' => (array) config('api-keys.management.middleware', ['web', 'auth']),
], function (): void {
    if ((bool) config('api-keys.management.page.enabled', true)) {
        Route::get('/', [ApiKeyController::class, 'index'])
            ->name('api-keys.management.index');

        Route::get('{ownerAlias}', [ApiKeyController::class, 'owners'])
            ->where('ownerAlias', '[A-Za-z0-9_-]+')
            ->name('api-keys.management.owners');

        Route::get('{ownerAlias}/{owner}', [ApiKeyController::class, 'show'])
            ->where('ownerAlias', '[A-Za-z0-9_-]+')
            ->where('owner', '[^/]+')
            ->name('api-keys.management.show');
    }

    Route::post('{ownerAlias}/{owner}/keys', [ApiKeyController::class, 'store'])
        ->where('ownerAlias', '[A-Za-z0-9_-]+')
        ->where('owner', '[^/]+')
        ->name('api-keys.keys.store');

    Route::post('{ownerAlias}/{owner}/keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])
        ->where('ownerAlias', '[A-Za-z0-9_-]+')
        ->where('owner', '[^/]+')
        ->whereNumber('apiKey')
        ->name('api-keys.keys.revoke');

    Route::post('{ownerAlias}/{owner}/keys/{apiKey}/renew', [ApiKeyController::class, 'renew'])
        ->where('ownerAlias', '[A-Za-z0-9_-]+')
        ->where('owner', '[^/]+')
        ->whereNumber('apiKey')
        ->name('api-keys.keys.renew');
});
