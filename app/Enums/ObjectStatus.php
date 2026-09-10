<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum ObjectStatus: string implements HasLabel
{
    use InteractsWithEnum;

    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Done = 'done';

    public function label(): string
    {
        return __("messages.enums.object_status.{$this->value}");
    }

    public function isLive(): bool
    {
        return $this->in(self::Planned, self::InProgress);
    }

    public function isDone(): bool
    {
        return $this === self::Done;
    }

    public function needsActualStart(): bool
    {
        return $this->in(self::InProgress, self::Done);
    }

    public static function default(): self
    {
        return self::Planned;
    }
}
