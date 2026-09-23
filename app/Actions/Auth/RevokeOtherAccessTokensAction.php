<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Contracts\Action;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevokeOtherAccessTokensAction implements Action
{
    public function handle(User $user): int
    {
        $current = $user->currentAccessToken();

        return $user->tokens()
            ->when(
                $current instanceof PersonalAccessToken,
                static fn ($query) => $query->whereKeyNot($current->getKey()),
            )
            ->delete();
    }
}
