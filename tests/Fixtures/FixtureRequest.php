<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Http\Requests\ApiFormRequest;

/**
 * Заглушка для проверки формата ошибок валидации в ApiFormRequest.
 */
final class FixtureRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:10'],
        ];
    }
}
