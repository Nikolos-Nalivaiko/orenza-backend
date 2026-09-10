<?php

declare(strict_types=1);

namespace App\Http\Requests\Services;

use App\DataTransferObjects\Services\ServiceData;
use App\Http\Requests\ApiFormRequest;

final class StoreServiceRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ServiceRules::all();
    }

    public function toData(): ServiceData
    {
        return ServiceData::fromArray($this->validated());
    }
}
