<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Repositories\Eloquent\ClientRepository;
use App\Repositories\Eloquent\MaterialRepository;
use App\Repositories\Eloquent\ObjectRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\ServiceRepository;
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
        ClientRepositoryInterface::class => ClientRepository::class,
        MaterialRepositoryInterface::class => MaterialRepository::class,
        ObjectRepositoryInterface::class => ObjectRepository::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
        ServiceRepositoryInterface::class => ServiceRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        WorkspaceRepositoryInterface::class => WorkspaceRepository::class,
    ];
}
