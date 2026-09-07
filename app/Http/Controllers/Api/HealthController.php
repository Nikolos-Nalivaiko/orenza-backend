<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'name' => config('app.name'),
            'env' => config('app.env'),
            'api_version' => 'v1',
            'time' => Carbon::now()->toIso8601String(),
        ]);
    }
}
