<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Support\Media\Contracts\ImageVariant;

enum CoverVariant: string implements ImageVariant
{
    use InteractsWithEnum;

    case Hero = 'hero';
    case Card = 'card';
    case Thumb = 'thumb';

    public function maxSide(): int
    {
        return match ($this) {
            self::Hero => 2048,
            self::Card => 960,
            self::Thumb => 320,
        };
    }

    public function quality(): int
    {
        return match ($this) {
            self::Hero => 82,
            self::Card => 80,
            self::Thumb => 78,
        };
    }
}
