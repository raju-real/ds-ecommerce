<?php

namespace App\Models\Concerns;

/**
 * Models using this trait declare which ModelCache groups they feed.
 * App\Observers\CacheObserver bumps those groups whenever the model
 * is created, updated or deleted.
 */
trait FlushesModelCache
{
    public static function cacheGroups(): array
    {
        return static::$cacheGroups ?? [];
    }
}
