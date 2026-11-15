<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A customer complaint (shop menu → Complaint), numbered 00001, 00002 … */
class Complaint extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::COMPLAINTS];

    public const STATUSES = [
        'received' => ['label' => 'Received', 'color' => 'info'],
        'on_review' => ['label' => 'On review', 'color' => 'warning'],
        'solved' => ['label' => 'Solved', 'color' => 'success'],
        'rejected' => ['label' => 'Rejected', 'color' => 'secondary'],
    ];

    /** Still needs the admin's attention. */
    public const OPEN = ['received', 'on_review'];

    protected $fillable = ['complaint_no', 'name', 'mobile', 'message', 'images', 'status', 'admin_note', 'ip', 'status_changed_at'];

    protected $casts = ['images' => 'array', 'status_changed_at' => 'datetime'];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }
}
