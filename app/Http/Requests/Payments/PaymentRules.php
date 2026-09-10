<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\Enums\PaymentStatus;
use Illuminate\Validation\Rule;

final class PaymentRules
{
    public const NAME_MAX = 255;

    public const DESCRIPTION_MAX = 2000;

    public const AMOUNT_MAX = 999999999;

    /**
     * @return array<string, mixed>
     */
    public static function all(string $prefix = '', bool $required = true): array
    {
        $key = static fn (string $field): string => $prefix === '' ? $field : "{$prefix}.{$field}";
        $must = $required ? 'required' : 'sometimes';

        return [
            $key('name') => [$must, 'string', 'min:2', 'max:'.self::NAME_MAX],
            $key('description') => ['sometimes', 'nullable', 'string', 'max:'.self::DESCRIPTION_MAX],
            $key('amount') => [$must, 'numeric', 'min:0', 'max:'.self::AMOUNT_MAX],
            $key('status') => ['sometimes', Rule::enum(PaymentStatus::class)],
            $key('paid_at') => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            $key('client_visible') => ['sometimes', 'boolean'],
        ];
    }
}
