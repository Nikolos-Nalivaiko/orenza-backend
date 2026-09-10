<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\ObjectController;
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

            Route::prefix('{workspace}/clients')->name('clients.')->scopeBindings()->group(function (): void {
                Route::get('/', [ClientController::class, 'index'])->name('index');
                Route::post('/', [ClientController::class, 'store'])->name('store');
                Route::get('{client}', [ClientController::class, 'show'])->name('show');
                Route::patch('{client}', [ClientController::class, 'update'])->name('update');
                Route::delete('{client}', [ClientController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('{workspace}/objects')->name('objects.')->scopeBindings()->group(function (): void {
                Route::get('/', [ObjectController::class, 'index'])->name('index');
                Route::post('/', [ObjectController::class, 'store'])->name('store');
                Route::get('{object}', [ObjectController::class, 'show'])->name('show');
                Route::patch('{object}', [ObjectController::class, 'update'])->name('update');
                Route::delete('{object}', [ObjectController::class, 'destroy'])->name('destroy');

                Route::prefix('{object}/materials')->name('materials.')->group(function (): void {
                    Route::get('/', [MaterialController::class, 'index'])->name('index');
                    Route::post('/', [MaterialController::class, 'store'])->name('store');
                    Route::patch('status', [MaterialController::class, 'status'])->name('status');
                    Route::patch('{material}', [MaterialController::class, 'update'])->name('update');
                    Route::delete('{material}', [MaterialController::class, 'destroy'])->name('destroy');
                });
            });
        });
    });
});
