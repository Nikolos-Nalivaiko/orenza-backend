<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum ServiceStatus: string implements HasLabel
{
    use InteractsWithEnum;

    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return __("messages.enums.service_status.{$this->value}");
    }

    public function isDone(): bool
    {
        return $this === self::Done;
    }

    public static function default(): self
    {
        return self::Planned;
    }
}
