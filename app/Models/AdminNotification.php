<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminNotification extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::NOTIFICATIONS];

    protected $fillable = ['type', 'title', 'body', 'order_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function isSeen(): bool
    {
        return $this->read_at !== null;
    }
}
