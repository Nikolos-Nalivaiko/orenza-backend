<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum MaterialBuyer: string implements HasLabel
{
    use InteractsWithEnum;

    case Contractor = 'contractor';
    case Client = 'client';

    public function label(): string
    {
        return __("messages.enums.material_buyer.{$this->value}");
    }

    public function isClient(): bool
    {
        return $this === self::Client;
    }

    public static function default(): self
    {
        return self::Contractor;
    }
}
