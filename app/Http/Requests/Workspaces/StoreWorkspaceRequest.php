<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspaces;

use App\DataTransferObjects\Workspaces\WorkspaceData;
use App\Enums\WorkspaceType;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class StoreWorkspaceRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(WorkspaceType::class)],
            'name' => [
                Rule::requiredIf(fn (): bool => $this->input('type') === WorkspaceType::Company->value),
                'nullable',
                'string',
                'min:2',
                'max:255',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'min:2',
                'max:48',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('workspaces', 'slug'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A company workspace requires a name.',
            'slug.regex' => 'The slug may contain lowercase letters, digits and single dashes only.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('slug'))) {
            $this->merge(['slug' => mb_strtolower(trim($this->string('slug')->value()))]);
        }
    }

    public function toData(): WorkspaceData
    {
        return WorkspaceData::fromArray($this->validated());
    }
}
