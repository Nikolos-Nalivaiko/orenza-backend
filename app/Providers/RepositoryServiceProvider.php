<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\WorkspaceRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Single map between repository contracts and their Eloquent implementations.
 *
 * Swapping a persistence layer (cache decorator, API gateway, in-memory fake)
 * is a one-line change here.
 *
 * Deliberately not deferred: Laravel recompiles bootstrap/cache/services.php only
 * when the provider list changes, so a new binding added here would stay invisible
 * until the cache is cleared by hand. Container bindings are lazy anyway.
 */
final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        WorkspaceRepositoryInterface::class => WorkspaceRepository::class,
    ];
}
