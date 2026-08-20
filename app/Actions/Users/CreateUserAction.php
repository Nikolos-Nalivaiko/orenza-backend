<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Users\UserData;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

final readonly class CreateUserAction implements Action
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function handle(UserData $data): User
    {
        $attributes = $data->toArray();

        if (! isset($attributes['first_name'], $attributes['last_name'], $attributes['email'], $attributes['password'])) {
            throw BusinessRuleException::make('First name, last name, e-mail and password are required to create a user.');
        }

        if ($this->users->emailExists($attributes['email'])) {
            throw BusinessRuleException::make('This e-mail address is already registered.', ['email' => $attributes['email']]);
        }

        if (isset($attributes['phone']) && $this->users->phoneExists($attributes['phone'])) {
            throw BusinessRuleException::make('This phone number is already registered.', ['phone' => $attributes['phone']]);
        }

        $user = DB::transaction(fn (): User => $this->users->create($attributes));

        event(new Registered($user));

        return $user;
    }
}
