<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;
use Illuminate\Http\Response;

final class InvalidCredentialsException extends DomainException
{
    protected int $status = Response::HTTP_UNAUTHORIZED;

    protected string $errorCode = 'invalid_credentials';

    public static function make(): self
    {
        return new self('These credentials do not match our records.');
    }
}
