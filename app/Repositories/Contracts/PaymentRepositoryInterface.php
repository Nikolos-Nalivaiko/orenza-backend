<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ConstructionObject;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends RepositoryInterface<Payment>
 */
interface PaymentRepositoryInterface extends RepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginateForObject(ConstructionObject $object, int $perPage): LengthAwarePaginator;
}
