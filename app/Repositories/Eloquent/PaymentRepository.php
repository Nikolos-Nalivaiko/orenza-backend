<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ConstructionObject;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<Payment>
 */
final class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    /**
     * @return class-string<Payment>
     */
    protected function model(): string
    {
        return Payment::class;
    }

    /**
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginateForObject(ConstructionObject $object, int $perPage): LengthAwarePaginator
    {
        return $this->query()->ofObject($object)->orderBy('id')->paginate($perPage);
    }
}
