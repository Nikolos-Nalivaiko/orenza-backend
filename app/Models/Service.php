<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceStatus;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'construction_object_id',
    'name',
    'description',
    'unit',
    'planned_volume',
    'actual_volume',
    'client_price',
    'status',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ServiceStatus::class,
            'planned_volume' => 'decimal:3',
            'actual_volume' => 'decimal:3',
            'client_price' => 'decimal:2',
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
     * @return HasMany<ServiceWorker, $this>
     */
    public function workers(): HasMany
    {
        return $this->hasMany(ServiceWorker::class)->orderBy('id');
    }

    public function volume(): float
    {
        $fact = $this->actual_volume === null ? 0.0 : (float) $this->actual_volume;

        return $fact > 0 ? $fact : (float) $this->planned_volume;
    }

    public function revenue(): ?float
    {
        return $this->client_price === null ? null : $this->volume() * (float) $this->client_price;
    }

    public function cost(): float
    {
        return $this->workers->sum(static fn (ServiceWorker $worker): float => $worker->cost());
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
    public function scopeOfStatus(Builder $query, ServiceStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
