<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thana extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::LOCATIONS, ModelCache::ORDERS];

    protected $fillable = ['district_id', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
