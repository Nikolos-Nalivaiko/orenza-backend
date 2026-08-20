<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

/**
 * Marker for a single-purpose, reusable unit of business logic.
 *
 * Conventions every action in this application follows:
 *  - exactly one public entry point named `handle()`;
 *  - dependencies are injected through the constructor (resolved by the container);
 *  - input arrives as a DTO or a model, never as a Request;
 *  - it either returns the result or throws a {@see \App\Exceptions\DomainException}.
 *
 * Actions are composed by services; they never call each other's HTTP layer.
 */
interface Action {}
