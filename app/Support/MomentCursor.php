<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

final readonly class MomentCursor
{
    public function __construct(
        public CarbonImmutable $moment,
        public int $id,
    ) {}

    public static function decode(string $value): ?self
    {
        $raw = base64_decode(strtr($value, '-_', '+/'), true);

        if ($raw === false) {
            return null;
        }

        try {
            $data = json_decode($raw, true, 2, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($data) || ! is_string($data[0] ?? null) || ! is_int($data[1] ?? null) || $data[1] < 1) {
            return null;
        }

        try {
            $moment = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $data[0], 'UTC');
        } catch (Throwable) {
            return null;
        }

        return $moment instanceof CarbonImmutable ? new self($moment, $data[1]) : null;
    }

    public function encode(): string
    {
        $raw = json_encode([$this->moment->utc()->format('Y-m-d H:i:s'), $this->id], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
