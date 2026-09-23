<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\RevokeOtherAccessTokensAction;
use App\Actions\Users\ChangePasswordAction;
use App\Actions\Users\DeleteUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly UpdateUserAction $updateUser,
        private readonly ChangePasswordAction $changePassword,
        private readonly RevokeOtherAccessTokensAction $revokeOthers,
        private readonly DeleteUserAction $deleteUser,
    ) {}

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user = $this->updateUser->handle($user, $request->toData());

        return ApiResponse::success(new UserResource($user), __('messages.profile.updated'));
    }

    public function password(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $revoked = $this->changePassword->handle($user, $request->password());

        return ApiResponse::success(['revoked_tokens' => $revoked], __('messages.profile.password_changed'));
    }

    public function destroySessions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $revoked = $this->revokeOthers->handle($user);

        return ApiResponse::success(['revoked_tokens' => $revoked], __('messages.profile.sessions_revoked'));
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->deleteUser->handle($user);

        return ApiResponse::success(null, __('messages.profile.deleted'));
    }
}
