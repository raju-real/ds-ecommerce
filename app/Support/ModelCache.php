<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Driver-agnostic, versioned cache built only on the default Cache:: store.
 *
 * Every cached value belongs to one or more "groups" (products, orders, ...).
 * A key embeds the current version of each group it depends on; bumping a
 * group (done by CacheObserver when a model changes) makes every dependent
 * key unreachable at once — no cache tags required, so file/database/redis/
 * memcached all behave the same. Orphaned keys die by their TTL.
 */
class ModelCache
{
    public const SETTINGS = 'settings';
    public const CATEGORIES = 'categories';
    public const VENDORS = 'vendors';
    public const PRODUCTS = 'products';
    public const LOCATIONS = 'locations';
    public const SLIDERS = 'sliders';
    public const ORDERS = 'orders';
    public const BRANDS = 'brands';
    public const BLOGS = 'blogs';
    public const SHORTLINKS = 'shortlinks';          // code → target lookups (changes when links are created/deleted)
    public const SHORTLINK_STATS = 'shortlink-stats';  // admin list with click counts (bumped on every counted click)
    public const NOTIFICATIONS = 'notifications';
    public const COUPONS = 'coupons';
    public const PAGES = 'pages';                // Website → Pages (footer links, /{slug} e.g. /privacy-policy)

    /** Per-request memo so each group's version is read from the store once. */
    protected static array $versions = [];

    public static function remember(array|string $groups, string $key, Closure $callback, ?int $ttl = null): mixed
    {
        $ttl ??= config('shop.cache.ttl');

        return Cache::remember(static::key($groups, $key), $ttl, $callback);
    }

    /** Shorter TTL for listing/filter combinations, which are numerous. */
    public static function rememberList(array|string $groups, string $key, Closure $callback): mixed
    {
        return static::remember($groups, $key, $callback, config('shop.cache.list_ttl'));
    }

    public static function key(array|string $groups, string $key): string
    {
        $versions = collect((array) $groups)
            ->map(fn ($group) => $group.'.'.static::version($group))
            ->implode('|');

        return config('shop.cache.prefix').':'.$versions.':'.$key;
    }

    public static function version(string $group): string
    {
        return static::$versions[$group] ??= (string) Cache::rememberForever(
            static::versionKey($group),
            fn () => static::newVersion()
        );
    }

    public static function bump(string ...$groups): void
    {
        foreach ($groups as $group) {
            $version = static::newVersion();
            Cache::forever(static::versionKey($group), $version);
            static::$versions[$group] = (string) $version;
        }
    }

    public static function bumpAll(): void
    {
        static::bump(self::SETTINGS, self::CATEGORIES, self::VENDORS, self::PRODUCTS,
            self::LOCATIONS, self::SLIDERS, self::ORDERS, self::BRANDS, self::BLOGS,
            self::SHORTLINKS, self::SHORTLINK_STATS, self::NOTIFICATIONS, self::COUPONS, self::PAGES);
    }

    /** Stable hash for a filter array so equal filters share a key. */
    public static function hash(array $params): string
    {
        $params = array_filter($params, fn ($v) => $v !== null && $v !== '');
        ksort($params);

        return md5(json_encode($params));
    }

    protected static function versionKey(string $group): string
    {
        return config('shop.cache.prefix').':version:'.$group;
    }

    protected static function newVersion(): string
    {
        return base_convert((string) (int) (microtime(true) * 1000), 10, 36).mt_rand(10, 99);
    }
}
