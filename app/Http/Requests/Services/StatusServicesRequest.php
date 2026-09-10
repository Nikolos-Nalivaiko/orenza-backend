<?php

declare(strict_types=1);

namespace App\Http\Requests\Services;

use App\Enums\ServiceStatus;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class StatusServicesRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::enum(ServiceStatus::class)],
        ];
    }

    public function status(): ServiceStatus
    {
        return ServiceStatus::from((string) $this->validated('status'));
    }

    /**
     * @return array<int, int>
     */
    public function ids(): array
    {
        /** @var array<int, int> $ids */
        $ids = $this->validated('ids');

        return array_values(array_unique(array_map(intval(...), $ids)));
    }
}
