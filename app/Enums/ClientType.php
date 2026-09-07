<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum ClientType: string implements HasLabel
{
    use InteractsWithEnum;

    case Person = 'person';
    case Company = 'company';

    public function label(): string
    {
        return __("messages.enums.client_type.{$this->value}");
    }

    public function isPerson(): bool
    {
        return $this === self::Person;
    }

    public function isCompany(): bool
    {
        return $this === self::Company;
    }

    public static function default(): self
    {
        return self::Person;
    }
}
