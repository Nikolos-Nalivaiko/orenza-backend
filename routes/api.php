<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('ping', HealthController::class)->name('ping');

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
        Route::prefix('workspaces')->name('workspaces.')->group(function (): void {
            Route::get('/', [WorkspaceController::class, 'index'])->name('index');
            Route::post('/', [WorkspaceController::class, 'store'])->name('store');
            Route::get('{workspace}', [WorkspaceController::class, 'show'])->name('show');
            Route::put('{workspace}/current', [WorkspaceController::class, 'switch'])->name('switch');
        });
    });
});
