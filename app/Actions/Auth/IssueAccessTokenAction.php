<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Contracts\Action;
use App\Models\User;
use Illuminate\Support\Carbon;

final readonly class IssueAccessTokenAction implements Action
{
    /**
     * @return array{token: string, type: string, expires_at: string|null}
     */
    public function handle(User $user, ?string $device = null): array
    {
        $expiresAt = $this->expiration();

        $token = $user->createToken($device ?? 'api', ['*'], $expiresAt);

        return [
            'token' => $token->plainTextToken,
            'type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
        ];
    }

    private function expiration(): ?Carbon
    {
        $minutes = config('sanctum.expiration');

        return $minutes ? now()->addMinutes((int) $minutes) : null;
    }
}
