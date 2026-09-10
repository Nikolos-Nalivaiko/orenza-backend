<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Enums\MaterialBuyer;
use App\Enums\MaterialStatus;
use Illuminate\Validation\Rule;

final class MaterialRules
{
    public const NAME_MAX = 255;

    public const UNIT_MAX = 16;

    public const QUANTITY_MAX = 9999999;

    public const PRICE_MAX = 99999999;

    /**
     * @return array<string, mixed>
     */
    public static function all(string $prefix = '', bool $required = true): array
    {
        $key = static fn (string $field): string => $prefix === '' ? $field : "{$prefix}.{$field}";
        $must = $required ? 'required' : 'sometimes';

        return [
            $key('name') => [$must, 'string', 'min:2', 'max:'.self::NAME_MAX],
            $key('unit') => [$must, 'string', 'max:'.self::UNIT_MAX],
            $key('quantity') => [$must, 'numeric', 'min:0', 'max:'.self::QUANTITY_MAX],
            $key('buyer') => ['sometimes', Rule::enum(MaterialBuyer::class)],
            $key('cost_price') => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:'.self::PRICE_MAX],
            $key('client_price') => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:'.self::PRICE_MAX],
            $key('status') => ['sometimes', Rule::enum(MaterialStatus::class)],
            $key('approved_by_client') => ['sometimes', 'boolean'],
        ];
    }
}
