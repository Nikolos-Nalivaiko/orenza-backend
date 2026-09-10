<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel
{
    use InteractsWithEnum;

    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("messages.enums.payment_status.{$this->value}");
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    public function isAwaited(): bool
    {
        return $this->in(self::Pending, self::Overdue);
    }

    public static function default(): self
    {
        return self::Pending;
    }
}
