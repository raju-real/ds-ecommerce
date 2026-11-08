<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShortLink extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    public const TYPES = [
        'product' => 'Product page',
        'buy' => 'Direct order',
        'blog' => 'Blog post',
    ];

    protected static array $cacheGroups = [ModelCache::SHORTLINKS, ModelCache::SHORTLINK_STATS];

    protected $fillable = ['code', 'type', 'target_id'];

    protected $casts = [
        'clicks' => 'integer',
        'last_clicked_at' => 'datetime',
    ];

    public function getUrlAttribute(): string
    {
        return route('short.go', $this->code);
    }
}
