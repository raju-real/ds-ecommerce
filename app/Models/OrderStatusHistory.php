<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStatusHistory extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::ORDERS];

    protected $fillable = ['order_id', 'user_id', 'status', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed(); // history keeps showing deleted records
    }
}
