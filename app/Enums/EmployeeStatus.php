<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum EmployeeStatus: string implements HasLabel
{
    use InteractsWithEnum;

    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __("messages.enums.employee_status.{$this->value}");
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public static function default(): self
    {
        return self::Active;
    }
}
