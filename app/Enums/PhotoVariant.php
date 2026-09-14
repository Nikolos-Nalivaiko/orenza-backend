<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Support\Media\Contracts\ImageVariant;

enum PhotoVariant: string implements ImageVariant
{
    use InteractsWithEnum;

    case Full = 'full';
    case Thumb = 'thumb';

    public function maxSide(): int
    {
        return match ($this) {
            self::Full => 2048,
            self::Thumb => 480,
        };
    }

    public function quality(): int
    {
        return match ($this) {
            self::Full => 84,
            self::Thumb => 76,
        };
    }
}
