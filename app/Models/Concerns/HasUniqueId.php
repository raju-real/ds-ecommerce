<?php

namespace App\Models\Concerns;

use App\Support\UniqueId;

/**
 * Gives the model a random lowercase unique_id on create and makes it the
 * route key, so route('admin.products.edit', $product) uses it in the URL.
 */
trait HasUniqueId
{
    public static function bootHasUniqueId(): void
    {
        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = UniqueId::for($model->getTable());
            }
        });
    }

    public function initializeHasUniqueId(): void
    {
        $this->mergeGuarded(['unique_id']);
    }

    public function getRouteKeyName(): string
    {
        return 'unique_id';
    }

    public static function findByUidOrFail(string $uid): static
    {
        return static::where('unique_id', $uid)->firstOrFail();
    }
}
