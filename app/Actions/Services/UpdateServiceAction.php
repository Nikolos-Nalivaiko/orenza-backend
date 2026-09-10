<?php

declare(strict_types=1);

namespace App\Actions\Services;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Services\ServiceData;
use App\Exceptions\BusinessRuleException;
use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class UpdateServiceAction implements Action
{
    use Concerns\SyncsCrew;

    public function __construct(private ServiceRepositoryInterface $services) {}

    public function handle(Service $service, ServiceData $data): Service
    {
        $attributes = $data->toArray();
        $crew = $data->crew();

        unset($attributes['workers']);

        if (array_key_exists('name', $attributes)) {
            $name = trim((string) $attributes['name']);

            if ($name === '') {
                throw BusinessRuleException::make(__('messages.services.name_required'), ['name' => null]);
            }

            $attributes['name'] = $name;
        }

        return DB::transaction(function () use ($service, $attributes, $crew): Service {
            if ($attributes !== []) {
                $service = $this->services->update($service, $attributes);
            }

            if ($crew !== null) {
                $object = $service->object;

                if ($object !== null) {
                    $this->syncCrew($service, $object, $crew);
                }
            }

            return $service->load('workers');
        });
    }
}
