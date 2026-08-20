<?php

declare(strict_types=1);

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
| Публичные маршруты — здесь, приватные — внутри группы auth:sanctum.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    //

    Route::middleware('auth:sanctum')->group(function (): void {
        //
    });
});
