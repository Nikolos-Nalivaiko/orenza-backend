<?php

declare(strict_types=1);

namespace App\Actions\Services;

use App\Actions\Contracts\Action;
use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;

final readonly class DeleteServiceAction implements Action
{
    public function __construct(private ServiceRepositoryInterface $services) {}

    public function handle(Service $service): bool
    {
        return $this->services->delete($service);
    }
}
