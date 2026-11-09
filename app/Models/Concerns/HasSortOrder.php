<?php

namespace App\Models\Concerns;

/** New rows go after the existing ones: the default sort order is the current highest + 1. */
trait HasSortOrder
{
    public static function nextSortOrder(array $where = []): int
    {
        return min(9999, (int) static::query()->where($where)->max('sort_order') + 1);
    }
}
