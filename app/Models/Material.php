<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MaterialBuyer;
use App\Enums\MaterialStatus;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'construction_object_id',
    'name',
    'unit',
    'quantity',
    'buyer',
    'cost_price',
    'client_price',
    'status',
    'approved_by_client',
])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'buyer' => MaterialBuyer::class,
            'status' => MaterialStatus::class,
            'quantity' => 'decimal:3',
            'cost_price' => 'decimal:2',
            'client_price' => 'decimal:2',
            'approved_by_client' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ConstructionObject, $this>
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(ConstructionObject::class, 'construction_object_id');
    }

    public function belongsToObject(ConstructionObject $object): bool
    {
        return $this->construction_object_id === $object->getKey();
    }

    public function cost(): ?float
    {
        return $this->buyer->isClient() || $this->cost_price === null
            ? null
            : (float) $this->quantity * (float) $this->cost_price;
    }

    public function revenue(): ?float
    {
        return $this->buyer->isClient() || $this->client_price === null
            ? null
            : (float) $this->quantity * (float) $this->client_price;
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
    public function scopeOfStatus(Builder $query, MaterialStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
