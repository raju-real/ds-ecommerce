<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cache checker for Admin → Settings → Cache and `php artisan cache:check`:
 * which driver is set (config/cache.php) and really used, Redis connection + server figures or file cache size,
 * a live write/read test, and when each group of cached site data was last refreshed.
 */
class CacheHealth
{
    public const GROUPS = [
        ModelCache::SETTINGS => 'Settings', ModelCache::PRODUCTS => 'Products', ModelCache::CATEGORIES => 'Categories',
        ModelCache::BRANDS => 'Brands', ModelCache::SLIDERS => 'Sliders', ModelCache::BLOGS => 'Blog',
        ModelCache::ORDERS => 'Orders', ModelCache::COUPONS => 'Coupons', ModelCache::LOCATIONS => 'Districts & thanas',
        ModelCache::VENDORS => 'Vendors', ModelCache::SHORTLINKS => 'Short links', ModelCache::SHORTLINK_STATS => 'Short link clicks',
        ModelCache::NOTIFICATIONS => 'Notifications',
    ];

    public static function report(): array
    {
        $requested = (string) config('cache.requested', config('cache.default'));
        $used = (string) config('cache.default');
        $redisCfg = (array) config('cache.redis_server', []);
        $client = $redisCfg['client'] ?? config('database.redis.client');

        $r = [
            'requested' => $requested,
            'used' => $used,
            'label' => self::label($used),
            'fallback' => $requested !== $used,
            'fix' => null,
            'client' => $client,
            'php' => PHP_VERSION,
            'prefix' => config('cache.prefix'),
            'ttl' => (int) config('shop.cache.ttl'),
            'list_ttl' => (int) config('shop.cache.list_ttl'),
            'redis' => null,
            'file' => null,
            'test' => self::test(),
            'groups' => self::groups(),
        ];

        if ($r['fallback'] && $requested === 'redis') {
            $r['fix'] = $client === 'predis'
                ? 'Install the package: composer require predis/predis — then run php artisan config:cache.'
                : 'Install the PHP extension: sudo apt install php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'-redis, restart PHP-FPM (sudo systemctl restart php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'-fpm), then run php artisan config:cache.';
        }
        if ($used === 'redis') {
            $r['redis'] = self::redis();
        }
        if ($used === 'file') {
            $r['file'] = self::file();
        }
        $r['ok'] = $r['test']['ok'] && ! $r['fallback'] && ($r['redis']['ok'] ?? true);

        return $r;
    }

    public static function label(string $store): string
    {
        return ['redis' => 'Redis', 'file' => 'File', 'array' => 'Array (memory, this request only)', 'database' => 'Database', 'memcached' => 'Memcached'][$store] ?? Str::title($store);
    }

    /** Write → read → delete a random value, timed. */
    protected static function test(): array
    {
        try {
            $key = 'cache-check:'.Str::random(10);
            $value = 'ok-'.Str::random(8);
            $start = microtime(true);
            Cache::put($key, $value, 60);
            $read = Cache::get($key);
            Cache::forget($key);
            $ms = round((microtime(true) - $start) * 1000, 2);

            return $read === $value
                ? ['ok' => true, 'ms' => $ms, 'message' => 'Wrote, read back and deleted a test value.']
                : ['ok' => false, 'ms' => $ms, 'message' => 'The value read back was different.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'ms' => null, 'message' => Str::limit($e->getMessage(), 200)];
        }
    }

    protected static function redis(): array
    {
        $conn = config('cache.stores.redis.connection', 'cache');
        $cfg = (array) config("database.redis.$conn", []);
        $out = [
            'ok' => false, 'error' => null,
            'host' => $cfg['host'] ?? null, 'port' => $cfg['port'] ?? null, 'database' => $cfg['database'] ?? null,
            'key_prefix' => config('database.redis.options.prefix').config('cache.prefix').':',
            'extension' => extension_loaded('redis'),
        ];
        try {
            $redis = Redis::connection($conn);
            $t = microtime(true);
            $redis->ping();
            $out['ping_ms'] = round((microtime(true) - $t) * 1000, 2);
            $info = self::flatten((array) $redis->info());
            $out['ok'] = true;
            $out['version'] = $info['redis_version'] ?? null;
            $out['uptime'] = isset($info['uptime_in_seconds']) ? Carbon::now()->subSeconds((int) $info['uptime_in_seconds'])->diffForHumans(null, true) : null;
            $out['memory'] = $info['used_memory_human'] ?? null;
            $out['max_memory'] = ! empty($info['maxmemory']) ? ($info['maxmemory_human'] ?? $info['maxmemory']) : 'no limit';
            $out['keys'] = (int) $redis->dbsize();
            $out['clients'] = $info['connected_clients'] ?? null;
            $hits = (int) ($info['keyspace_hits'] ?? 0);
            $misses = (int) ($info['keyspace_misses'] ?? 0);
            $out['hits'] = $hits;
            $out['misses'] = $misses;
            $out['hit_rate'] = $hits + $misses > 0 ? round($hits / ($hits + $misses) * 100, 1) : null;
        } catch (Throwable $e) {
            $out['error'] = Str::limit($e->getMessage(), 200);
        }

        return $out;
    }

    /** Files and total size of the file cache (stops counting after 20,000 files). */
    protected static function file(): array
    {
        $path = (string) config('cache.stores.file.path');
        $files = 0;
        $bytes = 0;
        $more = false;
        if (is_dir($path)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && $f->getFilename() !== '.gitignore') {
                    $files++;
                    $bytes += $f->getSize();
                    if ($files >= 20000) {
                        $more = true;
                        break;
                    }
                }
            }
        }

        return ['path' => $path, 'files' => $files, 'more' => $more, 'size' => self::bytes($bytes), 'writable' => is_writable($path)];
    }

    /** When each group of cached site data was last refreshed (decoded from its version). */
    protected static function groups(): array
    {
        $rows = [];
        foreach (self::GROUPS as $group => $label) {
            try {
                $v = ModelCache::version($group);
                $ms = (int) base_convert(substr($v, 0, -2), 36, 10);
                $at = $ms > 1_000_000_000_000 ? Carbon::createFromTimestampMs($ms) : null;
            } catch (Throwable) {
                $v = null;
                $at = null;
            }
            $rows[] = ['group' => $group, 'label' => $label, 'version' => $v, 'at' => $at];
        }

        return $rows;
    }

    protected static function flatten(array $info): array
    {
        $flat = [];
        foreach ($info as $k => $v) {
            is_array($v) ? $flat += self::flatten($v) : $flat[$k] = $v; // predis groups INFO by section, phpredis does not
        }

        return $flat;
    }

    protected static function bytes(int $b): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $u) {
            if ($b < 1024 || $u === 'GB') {
                return round($b, $u === 'B' ? 0 : 1).' '.$u;
            }
            $b /= 1024;
        }

        return '';
    }
}
