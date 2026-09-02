<?php

declare(strict_types=1);

namespace App\Enums\Contracts;

/**
 * Enum that exposes a human readable label for the UI / API layer.
 */
interface HasLabel
{
    public function label(): string;
}
