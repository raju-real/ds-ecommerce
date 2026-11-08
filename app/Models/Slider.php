<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::SLIDERS];

    protected $fillable = ['title', 'subtitle', 'button_text', 'link', 'image', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
