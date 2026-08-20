<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Single map between repository contracts and their Eloquent implementations.
 *
 * Swapping a persistence layer (cache decorator, API gateway, in-memory fake)
 * is a one-line change here.
 *
 * @example UserRepositoryInterface::class => UserRepository::class,
 */
final class RepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
    ];

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return array_keys($this->bindings);
    }
}
