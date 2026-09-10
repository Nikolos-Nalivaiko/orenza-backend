<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ConstructionObject;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<Payment>
 */
interface PaymentRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, Payment>
     */
    public function listForObject(ConstructionObject $object): Collection;
}
