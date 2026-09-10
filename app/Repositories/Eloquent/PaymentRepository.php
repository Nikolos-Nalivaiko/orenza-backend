<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ConstructionObject;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
     * @return Collection<int, Payment>
     */
    public function listForObject(ConstructionObject $object): Collection
    {
        return $this->query()->ofObject($object)->orderBy('id')->get();
    }
}
