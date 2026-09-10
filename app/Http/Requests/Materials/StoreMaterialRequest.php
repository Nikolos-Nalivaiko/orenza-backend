<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\DataTransferObjects\Materials\MaterialData;
use App\Http\Requests\ApiFormRequest;

final class StoreMaterialRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return MaterialRules::all();
    }

    public function toData(): MaterialData
    {
        return MaterialData::fromArray($this->validated());
    }
}
