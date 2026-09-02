<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use App\Enums\Contracts\HasLabel;

/**
 * Shared helpers for backed enums: validation rules, select options, lookups.
 *
 * @mixin \BackedEnum
 */
trait InteractsWithEnum
{
    /**
     * All backing values of the enum.
     *
     * @return array<int, string|int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * All case names of the enum.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Options for selects / API metadata endpoints.
     *
     * @return array<int, array{value: string|int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case instanceof HasLabel ? $case->label() : (string) $case->value,
            ],
            self::cases(),
        );
    }

    /**
     * Resolve a case by its name (case insensitive).
     */
    public static function tryFromName(string $name): ?static
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->name, $name) === 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Check whether the current case matches any of the given cases.
     */
    public function in(self ...$cases): bool
    {
        return in_array($this, $cases, true);
    }

    /**
     * Check whether the current case is none of the given cases.
     */
    public function notIn(self ...$cases): bool
    {
        return ! $this->in(...$cases);
    }
}
