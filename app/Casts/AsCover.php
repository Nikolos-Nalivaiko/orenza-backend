<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Media\Cover;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<Cover|null, Cover|null>
 */
final class AsCover implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Cover
    {
        if ($value === null || $value === '') {
            return null;
        }

        /** @var array{key: string, width: int, height: int, color: string, focus_x?: float, focus_y?: float} $data */
        $data = is_array($value) ? $value : json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);

        return Cover::fromArray($data);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Cover) {
            throw new InvalidArgumentException('The cover attribute expects an instance of '.Cover::class.'.');
        }

        return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
    }
}
