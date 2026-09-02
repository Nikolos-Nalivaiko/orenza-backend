<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Contracts\Action;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevokeAccessTokensAction implements Action
{
    public function handle(User $user, bool $everywhere = false): int
    {
        if ($everywhere) {
            return $user->tokens()->delete();
        }

        $current = $user->currentAccessToken();

        return $current instanceof PersonalAccessToken ? (int) $current->delete() : 0;
    }
}
