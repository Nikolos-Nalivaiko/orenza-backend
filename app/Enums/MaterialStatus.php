<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum MaterialStatus: string implements HasLabel
{
    use InteractsWithEnum;

    case Needed = 'needed';
    case Ordered = 'ordered';
    case Delivered = 'delivered';
    case Used = 'used';

    public function label(): string
    {
        return __("messages.enums.material_status.{$this->value}");
    }

    public function isOnSite(): bool
    {
        return $this->in(self::Delivered, self::Used);
    }

    public static function default(): self
    {
        return self::Needed;
    }
}
