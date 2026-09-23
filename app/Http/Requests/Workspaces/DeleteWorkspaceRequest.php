<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspaces;

use App\Http\Requests\ApiFormRequest;
use App\Models\Workspace;
use Closure;

final class DeleteWorkspaceRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $workspace = $this->route('workspace');

                    if ($workspace instanceof Workspace && trim((string) $value) !== trim($workspace->name)) {
                        $fail(__('messages.workspaces.delete_name_mismatch'));
                    }
                },
            ],
        ];
    }
}
