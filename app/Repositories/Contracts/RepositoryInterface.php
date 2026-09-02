<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Persistence contract shared by every repository.
 *
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /**
     * A fresh query builder for the underlying model.
     *
     * @return Builder<TModel>
     */
    public function query(): Builder;

    /**
     * @param  array<int, string>  $columns
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * @param  array<int, string>  $columns
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * @return TModel|null
     */
    public function find(int|string $id): ?Model;

    /**
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<TModel>
     */
    public function findOrFail(int|string $id): Model;

    /**
     * @param  array<string, mixed>  $criteria
     * @return TModel|null
     */
    public function findBy(array $criteria): ?Model;

    /**
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, TModel>
     */
    public function getBy(array $criteria): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model;

    /**
     * @param  TModel  $model
     */
    public function delete(Model $model): bool;

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function exists(array $criteria): bool;

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function count(array $criteria = []): int;
}
