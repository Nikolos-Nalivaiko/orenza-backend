<?php

declare(strict_types=1);

namespace App\Http\Requests\Objects;

use App\DataTransferObjects\Materials\MaterialData;
use App\DataTransferObjects\Services\ServiceData;
use App\Http\Requests\Materials\MaterialRules;
use App\Http\Requests\Services\ServiceRules;

final class StoreObjectRequest extends ObjectRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->sharedRules(),
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'materials' => ['sometimes', 'array', 'max:200'],
            ...MaterialRules::all('materials.*'),
            'services' => ['sometimes', 'array', 'max:200'],
            ...ServiceRules::all('services.*'),
        ];
    }

    /**
     * @return array<int, MaterialData>
     */
    public function materials(): array
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->validated('materials') ?? [];

        return array_map(MaterialData::fromArray(...), $rows);
    }

    /**
     * @return array<int, ServiceData>
     */
    public function services(): array
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->validated('services') ?? [];

        return array_map(ServiceData::fromArray(...), $rows);
    }
}
