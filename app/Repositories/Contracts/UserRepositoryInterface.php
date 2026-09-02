<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * @extends RepositoryInterface<User>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function emailExists(string $email, ?int $exceptId = null): bool;

    public function phoneExists(string $phone, ?int $exceptId = null): bool;
}
