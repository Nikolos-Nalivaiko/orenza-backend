<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum WorkspaceType: string implements HasLabel
{
    use InteractsWithEnum;

    case Personal = 'personal';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Личный',
            self::Company => 'Компания',
        };
    }

    public function isPersonal(): bool
    {
        return $this === self::Personal;
    }

    public function isCompany(): bool
    {
        return $this === self::Company;
    }

    public static function default(): self
    {
        return self::Personal;
    }
}
