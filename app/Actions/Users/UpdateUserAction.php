<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Users\UserData;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

final readonly class UpdateUserAction implements Action
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function handle(User $user, UserData $data): User
    {
        $attributes = $data->toArray();

        unset($attributes['password']);

        foreach (['first_name', 'last_name', 'email'] as $field) {
            if (array_key_exists($field, $attributes) && trim((string) $attributes[$field]) === '') {
                throw BusinessRuleException::make('First name, last name and e-mail cannot be empty.', [$field => null]);
            }
        }

        if (isset($attributes['email']) && $this->users->emailExists($attributes['email'], $user->id)) {
            throw BusinessRuleException::make('This e-mail address is already registered.', ['email' => $attributes['email']]);
        }

        if (isset($attributes['phone']) && $this->users->phoneExists($attributes['phone'], $user->id)) {
            throw BusinessRuleException::make('This phone number is already registered.', ['phone' => $attributes['phone']]);
        }

        if (isset($attributes['email']) && $attributes['email'] !== $user->email) {
            $attributes['email_verified_at'] = null;
        }

        if ($attributes === []) {
            return $user;
        }

        return $this->users->update($user, $attributes);
    }
}
