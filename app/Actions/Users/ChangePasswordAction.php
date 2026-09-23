<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Auth\RevokeOtherAccessTokensAction;
use App\Actions\Contracts\Action;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class ChangePasswordAction implements Action
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RevokeOtherAccessTokensAction $revokeOthers,
    ) {}

    public function handle(User $user, string $password): int
    {
        if (Hash::check($password, $user->password)) {
            throw BusinessRuleException::make('The new password must differ from the current one.', ['password' => null]);
        }

        return DB::transaction(function () use ($user, $password): int {
            $this->users->update($user, ['password' => $password]);

            return $this->revokeOthers->handle($user);
        });
    }
}
