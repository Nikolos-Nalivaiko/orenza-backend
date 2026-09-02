<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Версия живёт в URL, а не в пространствах имён: контроллеры лежат плоско в
| App\Http\Controllers\Api. Отдельный неймспейс App\Http\Controllers\Api\V2
| заводится только тогда, когда реально появится вторая версия и старую
| нужно будет поддерживать параллельно.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::middleware('throttle:auth')->group(function (): void {
            Route::post('register', [AuthController::class, 'register'])->name('register');
            Route::post('login', [AuthController::class, 'login'])->name('login');
        });

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    });
});
