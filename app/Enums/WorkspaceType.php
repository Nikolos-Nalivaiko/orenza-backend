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
        return __("messages.enums.workspace_type.{$this->value}");
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
