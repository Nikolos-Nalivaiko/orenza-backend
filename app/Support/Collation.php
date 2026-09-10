<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class Collation
{
    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    public static function wrap(string $column, Builder $query): string
    {
        return self::onPostgres($query)
            ? $column.' collate "und-x-icu"'
            : $column;
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    public static function like(Builder $query): string
    {
        return self::onPostgres($query) ? 'ilike' : 'like';
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    private static function onPostgres(Builder $query): bool
    {
        return $query->getConnection()->getDriverName() === 'pgsql';
    }
}
