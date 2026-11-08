<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    public const TYPES = [
        'all_users' => 'All users',
        'new_user' => 'New user (first order)',
        'fixed_user' => 'Fixed users (listed mobiles)',
    ];

    public const DISCOUNT_TYPES = ['fixed' => 'Fixed amount (৳)', 'percent' => 'Percentage (%)'];

    protected static array $cacheGroups = [ModelCache::COUPONS];

    protected $fillable = ['code', 'type', 'discount_type', 'value', 'max_discount', 'min_order', 'mobiles',
        'usage_limit', 'per_mobile_limit', 'starts_at', 'ends_at', 'note', 'is_active'];

    protected $casts = [
        'value' => 'float',
        'max_discount' => 'float',
        'min_order' => 'float',
        'mobiles' => 'array',
        'usage_limit' => 'integer',
        'per_mobile_limit' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim((string) $value));
    }

    /** "৳100 off" / "10% off (max ৳200)" */
    public function getLabelAttribute(): string
    {
        return $this->discount_type === 'percent'
            ? rtrim(rtrim(number_format($this->value, 2), '0'), '.').'% off'.($this->max_discount ? ' (max '.money($this->max_discount).')' : '')
            : money($this->value).' off';
    }

    /** Discount on a subtotal (never more than the subtotal). */
    public function discountFor(float $subtotal): float
    {
        $amount = $this->discount_type === 'percent' ? $subtotal * $this->value / 100 : $this->value;
        if ($this->discount_type === 'percent' && $this->max_discount) {
            $amount = min($amount, $this->max_discount);
        }

        return round(min($amount, $subtotal), 2);
    }
}
