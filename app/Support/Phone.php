<?php

declare(strict_types=1);

namespace App\Support;

final class Phone
{
    public static function normalise(string $phone): string
    {
        return (string) preg_replace('/(?!^\+)[^\d]/', '', trim($phone));
    }
}
