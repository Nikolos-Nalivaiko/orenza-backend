<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\MomentCursor;
use Carbon\CarbonImmutable;
use Database\Factories\ObjectPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'construction_object_id',
    'uploaded_by',
    'key',
    'width',
    'height',
    'color',
    'original_name',
    'taken_at',
])]
class ObjectPhoto extends Model
{
    /** @use HasFactory<ObjectPhotoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'taken_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function moment(): ?CarbonImmutable
    {
        /** @var CarbonImmutable|null */
        return $this->taken_at ?? $this->created_at;
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
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query
            ->orderByRaw('coalesce(taken_at, created_at) desc')
            ->orderByDesc('id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAfterPosition(Builder $query, MomentCursor $cursor): Builder
    {
        $moment = $cursor->moment->utc()->format('Y-m-d H:i:s');

        return $query->where(static function (Builder $inner) use ($moment, $cursor): void {
            $inner
                ->whereRaw('coalesce(taken_at, created_at) < ?', [$moment])
                ->orWhere(static function (Builder $same) use ($moment, $cursor): void {
                    $same
                        ->whereRaw('coalesce(taken_at, created_at) = ?', [$moment])
                        ->where('id', '<', $cursor->id);
                });
        });
    }

    public function position(): MomentCursor
    {
        return new MomentCursor($this->moment() ?? CarbonImmutable::now(), (int) $this->id);
    }
}
