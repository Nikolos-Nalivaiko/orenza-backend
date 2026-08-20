<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->toData(), $request->deviceName());

        return ApiResponse::created([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Registration completed.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->email(), $request->password(), $request->deviceName());

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Signed in.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success(new UserResource($user));
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $revoked = $this->auth->logout($user, $request->boolean('everywhere'));

        return ApiResponse::success(['revoked_tokens' => $revoked], 'Signed out.');
    }
}
