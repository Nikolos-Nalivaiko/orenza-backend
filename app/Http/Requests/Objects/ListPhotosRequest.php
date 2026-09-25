<?php

declare(strict_types=1);

namespace App\Http\Requests\Objects;

use App\Http\Requests\PageRequest;
use App\Support\MomentCursor;
use Closure;

final class ListPhotosRequest extends PageRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::maxPerPage()],
            'cursor' => [
                'sometimes',
                'nullable',
                'string',
                'max:200',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && MomentCursor::decode($value) === null) {
                        $fail(__('messages.http.invalid_cursor'));
                    }
                },
            ],
        ];
    }

    public function cursor(): ?MomentCursor
    {
        $value = $this->validated('cursor');

        return is_string($value) ? MomentCursor::decode($value) : null;
    }
}
