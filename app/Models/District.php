<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class District extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::LOCATIONS, ModelCache::ORDERS];

    protected $fillable = ['name', 'bn_name', 'delivery_charge', 'is_active'];

    protected $casts = ['delivery_charge' => 'float', 'is_active' => 'boolean'];

    public function thanas()
    {
        return $this->hasMany(Thana::class)->orderBy('name');
    }
}
