<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Throwable;

/**
 * Base class for every expected business failure.
 *
 * Domain exceptions render themselves as a consistent JSON error payload, so
 * controllers never need try/catch blocks for the happy path of the API.
 */
abstract class DomainException extends RuntimeException
{
    protected int $status = Response::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * Machine readable code the client can branch on.
     */
    protected string $errorCode = 'domain_error';

    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message = '', array $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message !== '' ? $message : 'The request could not be processed.', 0, $previous);

        $this->context = $context;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        return ApiResponse::error(
            message: $this->getMessage(),
            status: $this->status,
            errorCode: $this->errorCode,
            errors: $this->context,
        );
    }
}
