<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Support\Collation;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'name', 'role', 'phone', 'email', 'status', 'notes'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmployeeStatus::class,
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
     * @return HasMany<ServiceWorker, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(ServiceWorker::class);
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
    public function scopeOfStatus(Builder $query, EmployeeStatus $status): Builder
    {
        return $query->where('status', $status);
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
            foreach (['name', 'role', 'phone', 'email'] as $column) {
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
