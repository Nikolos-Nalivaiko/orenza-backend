<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum MembershipStatus: string implements HasLabel
{
    use InteractsWithEnum;

    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return __("messages.enums.membership_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'red',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function canAccessWorkspace(): bool
    {
        return $this === self::Active;
    }

    public static function default(): self
    {
        return self::Active;
    }
}
