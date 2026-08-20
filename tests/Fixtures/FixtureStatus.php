<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

/**
 * Заглушка для проверки общего трейта перечислений.
 */
enum FixtureStatus: string implements HasLabel
{
    use InteractsWithEnum;

    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Published => 'Опубликовано',
        };
    }
}
