<?php

declare(strict_types=1);

namespace App\Actions\Services;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Services\ServiceData;
use App\Enums\ServiceStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class CreateServiceAction implements Action
{
    use Concerns\SyncsCrew;

    public function __construct(private ServiceRepositoryInterface $services) {}

    public function handle(ConstructionObject $object, ServiceData $data): Service
    {
        $attributes = $data->toArray();
        $crew = $data->crew() ?? [];

        unset($attributes['workers']);

        $name = trim((string) ($attributes['name'] ?? ''));

        if ($name === '') {
            throw BusinessRuleException::make(__('messages.services.name_required'), ['name' => null]);
        }

        return DB::transaction(function () use ($object, $attributes, $crew, $name): Service {
            $service = $this->services->create([
                ...$attributes,
                'construction_object_id' => $object->getKey(),
                'name' => $name,
                'planned_volume' => $attributes['planned_volume'] ?? 0,
                'status' => $attributes['status'] ?? ServiceStatus::default(),
            ]);

            $this->syncCrew($service, $object, $crew);

            return $service;
        });
    }
}
