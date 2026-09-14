<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum DueState: string implements HasLabel
{
    use InteractsWithEnum;

    private const TOLERANCE = 0.01;

    case None = 'none';
    case Partial = 'partial';
    case Paid = 'paid';
    case Over = 'over';

    public function label(): string
    {
        return __("messages.enums.due_state.{$this->value}");
    }

    public static function of(float $client, float $paid): self
    {
        $left = $client - $paid;

        if ($paid > 0 && $left < -self::TOLERANCE) {
            return self::Over;
        }

        if ($client > 0 && $left <= self::TOLERANCE) {
            return self::Paid;
        }

        return $paid > 0 ? self::Partial : self::None;
    }
}
