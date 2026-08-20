<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent implementation shared by every repository.
 *
 * @template TModel of Model
 *
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * Fully qualified class name of the model handled by the repository.
     *
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    /**
     * @return TModel
     */
    protected function newModel(): Model
    {
        $class = $this->model();

        return new $class;
    }

    /**
     * @return Builder<TModel>
     */
    public function query(): Builder
    {
        return $this->newModel()->newQuery();
    }

    /**
     * @param  array<int, string>  $columns
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    /**
     * @param  array<int, string>  $columns
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns);
    }

    /**
     * @return TModel|null
     */
    public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<TModel>
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return TModel|null
     */
    public function findBy(array $criteria): ?Model
    {
        return $this->applyCriteria($this->query(), $criteria)->first();
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, TModel>
     */
    public function getBy(array $criteria): Collection
    {
        return $this->applyCriteria($this->query(), $criteria)->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        $model = $this->newModel()->newInstance();
        $model->forceFill($attributes)->save();

        return $model->refresh();
    }

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->forceFill($attributes)->save();

        return $model->refresh();
    }

    /**
     * @param  TModel  $model
     */
    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function exists(array $criteria): bool
    {
        return $this->applyCriteria($this->query(), $criteria)->exists();
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function count(array $criteria = []): int
    {
        return $this->applyCriteria($this->query(), $criteria)->count();
    }

    /**
     * Translate an associative array into where clauses.
     *
     * Values given as arrays become `whereIn` constraints.
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $criteria
     * @return Builder<TModel>
     */
    protected function applyCriteria(Builder $query, array $criteria): Builder
    {
        foreach ($criteria as $column => $value) {
            match (true) {
                is_array($value) => $query->whereIn($column, $value),
                $value === null => $query->whereNull($column),
                default => $query->where($column, $value),
            };
        }

        return $query;
    }
}
