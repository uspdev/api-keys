<?php

use Illuminate\Support\Facades\Route;
use Uspdev\ApiKey\Http\Controllers\ApiKeyController;

Route::group([
    'prefix' => config('api-key.prefix'),
    'middleware' => (array) config('api-key.management.middleware', ['web', 'auth']),
], function (): void {
    if ((bool) config('api-key.management.page.enabled', true)) {
        Route::get('/', [ApiKeyController::class, 'index'])
            ->name('api-key.admin.index');

        Route::get('{ownerAlias}', [ApiKeyController::class, 'owners'])
            ->where('ownerAlias', '[A-Za-z0-9_-]+')
            ->name('api-key.admin.owners');

        Route::get('{ownerAlias}/{owner}', [ApiKeyController::class, 'show'])
            ->where('ownerAlias', '[A-Za-z0-9_-]+')
            ->where('owner', '[^/]+')
            ->name('api-key.admin.show');
    }

    Route::post('{ownerAlias}/{owner}/keys', [ApiKeyController::class, 'store'])
        ->where('ownerAlias', '[A-Za-z0-9_-]+')
        ->where('owner', '[^/]+')
        ->name('api-key.keys.store');

    Route::post('{ownerAlias}/{owner}/keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])
        ->where('ownerAlias', '[A-Za-z0-9_-]+')
        ->where('owner', '[^/]+')
        ->whereNumber('apiKey')
        ->name('api-key.keys.revoke');
});
