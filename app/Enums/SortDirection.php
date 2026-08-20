<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum SortDirection: string implements HasLabel
{
    use InteractsWithEnum;

    case Asc = 'asc';
    case Desc = 'desc';

    public function label(): string
    {
        return match ($this) {
            self::Asc => 'Ascending',
            self::Desc => 'Descending',
        };
    }

    public static function default(): self
    {
        return self::Desc;
    }
}
