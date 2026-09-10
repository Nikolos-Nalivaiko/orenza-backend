<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'construction_object_id',
    'name',
    'description',
    'amount',
    'status',
    'paid_at',
    'client_visible',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'client_visible' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ConstructionObject, $this>
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(ConstructionObject::class, 'construction_object_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfObject(Builder $query, ConstructionObject $object): Builder
    {
        return $query->where('construction_object_id', $object->getKey());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfStatus(Builder $query, PaymentStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
