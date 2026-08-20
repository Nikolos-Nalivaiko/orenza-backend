<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Response;

/**
 * Generic "the request is valid but the domain forbids it" failure.
 */
class BusinessRuleException extends DomainException
{
    protected int $status = Response::HTTP_UNPROCESSABLE_ENTITY;

    protected string $errorCode = 'business_rule_violation';

    /**
     * @param  array<string, mixed>  $context
     */
    public static function make(string $message, array $context = []): static
    {
        return new static($message, $context);
    }
}
