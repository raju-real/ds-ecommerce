<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::SETTINGS];

    protected $fillable = ['key', 'value'];
}
