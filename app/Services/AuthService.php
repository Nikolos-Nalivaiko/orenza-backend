<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Auth\IssueAccessTokenAction;
use App\Actions\Auth\RevokeAccessTokensAction;
use App\Actions\Users\CreateUserAction;
use App\DataTransferObjects\Users\UserData;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

final readonly class AuthService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CreateUserAction $createUser,
        private IssueAccessTokenAction $issueToken,
        private RevokeAccessTokensAction $revokeTokens,
    ) {}

    /**
     * @return array{user: User, token: array{token: string, type: string, expires_at: string|null}}
     */
    public function register(UserData $data, ?string $device = null): array
    {
        $user = $this->createUser->handle($data);

        return [
            'user' => $user,
            'token' => $this->issueToken->handle($user, $device),
        ];
    }

    /**
     * @return array{user: User, token: array{token: string, type: string, expires_at: string|null}}
     *
     * @throws InvalidCredentialsException
     */
    public function login(string $email, string $password, ?string $device = null): array
    {
        $user = $this->users->findByEmail($email);

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            throw InvalidCredentialsException::make();
        }

        return [
            'user' => $user,
            'token' => $this->issueToken->handle($user, $device),
        ];
    }

    public function logout(User $user, bool $everywhere = false): int
    {
        return $this->revokeTokens->handle($user, $everywhere);
    }
}
