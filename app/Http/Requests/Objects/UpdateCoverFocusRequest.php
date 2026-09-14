<?php

declare(strict_types=1);

namespace App\Http\Requests\Objects;

use App\Http\Requests\ApiFormRequest;

final class UpdateCoverFocusRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'focus_x' => ['required', 'numeric', 'between:0,1'],
            'focus_y' => ['required', 'numeric', 'between:0,1'],
        ];
    }

    public function focusX(): float
    {
        return (float) $this->input('focus_x');
    }

    public function focusY(): float
    {
        return (float) $this->input('focus_y');
    }
}
