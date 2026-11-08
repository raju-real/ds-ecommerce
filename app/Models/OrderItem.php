<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::ORDERS];

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price' => 'float',
        'drop_shipping_price' => 'float',
        'line_total' => 'float',
        'line_cost' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed(); // history keeps showing deleted records
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class)->withTrashed(); // history keeps showing deleted records
    }
}
