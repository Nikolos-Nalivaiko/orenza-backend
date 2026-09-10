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

    public function belongsToWorkspace(Workspace $workspace): bool
    {
        return $this->workspace_id === $workspace->getKey();
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
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
