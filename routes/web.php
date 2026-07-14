<?php

use Illuminate\Support\Facades\Route;
use Uspdev\ApiKey\Http\Controllers\ApiKeyController;

Route::group([
    'prefix' => config('api-key.prefix'),
    'middleware' => (array) config('api-key.management.middleware', ['web', 'auth']),
], function (): void {
    Route::post('{ownerAlias}/{owner}/keys', [ApiKeyController::class, 'store'])
        ->where('ownerAlias', '[A-Za-z0-9_-]+')
        ->name('api-key.keys.store');

    Route::post('{ownerAlias}/{owner}/keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])
        ->where('ownerAlias', '[A-Za-z0-9_-]+')
        ->whereNumber('apiKey')
        ->name('api-key.keys.revoke');
});
