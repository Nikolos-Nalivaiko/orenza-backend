<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObjectStatus;
use App\Support\Collation;
use Database\Factories\ConstructionObjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'workspace_id',
    'client_id',
    'name',
    'description',
    'address',
    'status',
    'discount_percent',
    'discount_amount',
    'started_at',
    'finished_at',
    'actual_started_at',
    'actual_finished_at',
    'cover_path',
    'public_token',
    'archived_at',
])]
class ConstructionObject extends Model
{
    /** @use HasFactory<ConstructionObjectFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ObjectStatus::class,
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'started_at' => 'date',
            'finished_at' => 'date',
            'actual_started_at' => 'date',
            'actual_finished_at' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<Material, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class)->orderBy('id');
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    public function belongsToWorkspace(Workspace $workspace): bool
    {
        return $this->workspace_id === $workspace->getKey();
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isFinished(): bool
    {
        return $this->status->isDone() || $this->actual_finished_at !== null;
    }

    public function grossRevenue(): float
    {
        return $this->materials->sum(static fn (Material $material): float => $material->revenue() ?? 0.0)
            + $this->services->sum(static fn (Service $service): float => $service->revenue() ?? 0.0);
    }

    public function discountTotal(): float
    {
        $gross = $this->grossRevenue();

        if ($this->discount_amount !== null) {
            return min((float) $this->discount_amount, $gross);
        }

        if ($this->discount_percent === null) {
            return 0.0;
        }

        return round($gross * min((float) $this->discount_percent, 100.0)) / 100;
    }

    public function clientTotal(): float
    {
        return $this->grossRevenue() - $this->discountTotal();
    }

    public function paidTotal(): float
    {
        return $this->payments
            ->filter(static fn (Payment $payment): bool => $payment->status->isPaid())
            ->sum(static fn (Payment $payment): float => (float) $payment->amount);
    }

    public function readiness(): ?float
    {
        if ($this->status->isDone()) {
            return 1.0;
        }

        $planned = $this->services->sum(static fn (Service $service): float => (float) $service->planned_volume);

        if ($planned <= 0) {
            return null;
        }

        $done = $this->services->sum(static function (Service $service): float {
            $plan = (float) $service->planned_volume;
            $fact = $service->actual_volume !== null
                ? (float) $service->actual_volume
                : ($service->status->isDone() ? $plan : 0.0);

            return min($fact, $plan);
        });

        return max(0.0, min(1.0, $done / $planned));
    }

    public function completedServicesCount(): int
    {
        return $this->services
            ->filter(static function (Service $service): bool {
                $plan = (float) $service->planned_volume;

                return $service->status->isDone()
                    || ($plan > 0 && (float) ($service->actual_volume ?? 0) >= $plan);
            })
            ->count();
    }

    public static function newPublicToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfWorkspace(Builder $query, Workspace $workspace): Builder
    {
        return $query->where('workspace_id', $workspace->getKey());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfStatus(Builder $query, ObjectStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfClient(Builder $query, Client $client): Builder
    {
        return $query->where('client_id', $client->getKey());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $needle = '%'.trim($term).'%';
        $like = Collation::like($query);

        return $query->where(function (Builder $inner) use ($needle, $like): void {
            foreach (['name', 'address'] as $column) {
                $inner->orWhere($column, $like, $needle);
            }
        });
    }
}
