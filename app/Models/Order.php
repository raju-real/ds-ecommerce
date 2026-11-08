<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::ORDERS];

    protected $guarded = ['id'];

    protected $hidden = ['otp_code'];

    protected $casts = [
        'subtotal' => 'float',
        'delivery_charge' => 'float',
        'discount' => 'float',
        'total' => 'float',
        'cost_total' => 'float',
        'courier_cost' => 'float',
        'otp_verified' => 'boolean',
        'otp_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'otp_sent_at' => 'datetime',
        'forwarded_at' => 'datetime',
    ];

    /** 12 → "NT-00012" (prefix and padding from config shop.order_number). */
    public static function formatNumber(int $sequence): string
    {
        $cfg = config('shop.order_number');

        return $cfg['prefix'].'-'.str_pad((string) $sequence, $cfg['digits'], '0', STR_PAD_LEFT);
    }

    /** What customers type → stored form: "#nt 12", "NT12", "nt-00012" all become "NT-00012"; older numbers are just upper-cased. */
    public static function normalizeNumber(string $input): string
    {
        $value = strtoupper(preg_replace('/[\s#]+/', '', $input));
        $prefix = config('shop.order_number.prefix');

        return preg_match('/^'.preg_quote($prefix, '/').'-?(\d{1,9})$/', $value, $m) ? static::formatNumber((int) $m[1]) : $value;
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function district()
    {
        return $this->belongsTo(District::class)->withTrashed(); // history keeps showing deleted records
    }

    public function thana()
    {
        return $this->belongsTo(Thana::class)->withTrashed(); // history keeps showing deleted records
    }

    /** Profit = what the customer pays - vendor cost - what we pay the courier. */
    public function getProfitAttribute(): float
    {
        return round($this->total - $this->cost_total - $this->courier_cost, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return config("shop.order_statuses.{$this->status}.label", ucfirst($this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return config("shop.order_statuses.{$this->status}.color", 'secondary');
    }

    /** The simplified status a customer sees on the tracking page. */
    public function getPublicStatusAttribute(): string
    {
        return config("shop.order_statuses.{$this->status}.public", 'pending');
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->address, optional($this->thana)->name, optional($this->district)->name])->filter()->implode(', ');
    }
}
