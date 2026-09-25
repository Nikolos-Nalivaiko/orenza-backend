<?php

declare(strict_types=1);

namespace App\Http\Requests;

class PageRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::maxPerPage()],
        ];
    }

    public function perPage(): int
    {
        $value = $this->validated('per_page');

        return $value === null ? (int) config('orenza.pagination.per_page') : (int) $value;
    }

    public static function maxPerPage(): int
    {
        return (int) config('orenza.pagination.max_per_page');
    }
}
