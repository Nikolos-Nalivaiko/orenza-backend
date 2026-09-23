<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspaces;

use App\Http\Requests\ApiFormRequest;

final class UpdateWorkspaceRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }

    public function name(): string
    {
        return (string) $this->validated('name');
    }
}
