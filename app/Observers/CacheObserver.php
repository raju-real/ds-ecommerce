<?php

namespace App\Observers;

use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Registered for every cached model in EventServiceProvider::$observers.
 * Any write bumps the model's cache groups so readers rebuild fresh data.
 */
class CacheObserver
{
    public function saved(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    protected function flush(Model $model): void
    {
        ModelCache::bump(...$model::cacheGroups());
    }
}
