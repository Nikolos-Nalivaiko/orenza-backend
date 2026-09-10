<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientType;
use App\Support\Collation;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'type', 'name', 'contact', 'phone', 'email', 'notes', 'discount'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ClientType::class,
            'discount' => 'decimal:2',
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
     * @return HasMany<ConstructionObject, $this>
     */
    public function objects(): HasMany
    {
        return $this->hasMany(ConstructionObject::class);
    }

    public function belongsToWorkspace(Workspace $workspace): bool
    {
        return $this->workspace_id === $workspace->getKey();
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
    public function scopeOfType(Builder $query, ClientType $type): Builder
    {
        return $query->where('type', $type);
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
            foreach (['name', 'contact', 'phone', 'email'] as $column) {
                $inner->orWhere($column, $like, $needle);
            }
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public static function collated(string $column, Builder $query): string
    {
        return Collation::wrap($column, $query);
    }
}
