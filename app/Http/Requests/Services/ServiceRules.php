<?php

declare(strict_types=1);

namespace App\Http\Requests\Services;

use App\Enums\ServiceStatus;
use Illuminate\Validation\Rule;

final class ServiceRules
{
    public const NAME_MAX = 255;

    public const DESCRIPTION_MAX = 2000;

    public const UNIT_MAX = 16;

    public const VOLUME_MAX = 9999999;

    public const PRICE_MAX = 99999999;

    public const CREW_MAX = 50;

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
            $key('unit') => [$must, 'string', 'max:'.self::UNIT_MAX],
            $key('planned_volume') => [$must, 'numeric', 'min:0', 'max:'.self::VOLUME_MAX],
            $key('actual_volume') => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:'.self::VOLUME_MAX],
            $key('client_price') => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:'.self::PRICE_MAX],
            $key('status') => ['sometimes', Rule::enum(ServiceStatus::class)],
            $key('workers') => ['sometimes', 'array', 'max:'.self::CREW_MAX],
            $key('workers.*.employee_id') => ['required', 'integer', 'min:1'],
            $key('workers.*.volume') => ['required', 'numeric', 'min:0', 'max:'.self::VOLUME_MAX],
            $key('workers.*.rate') => ['required', 'numeric', 'min:0', 'max:'.self::PRICE_MAX],
        ];
    }
}
