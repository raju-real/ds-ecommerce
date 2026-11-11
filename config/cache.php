<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Cache settings — set here, not in .env
|--------------------------------------------------------------------------
| After changing a value, on the server run:  php artisan config:cache
| Check it with:  php artisan cache:check   or   Admin → Settings → Cache
*/

$cacheStore = 'redis'; // file | redis

$redisServer = [
    'client' => 'phpredis',   // phpredis (PHP extension) | predis (composer package)
    'host' => '127.0.0.1',
    'password' => null,       // null = no password
    'port' => 6379,
    'prefix' => 'neatly_',    // every key starts with this
    'database' => 1,          // Redis database number used for the cache
];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | The store really used. Safety net: if Redis is chosen but this PHP cannot talk to it (phpredis extension
    | not installed, or predis package missing), the file cache is used instead of an error on every page.
    |
    */

    'default' => $cacheStore === 'redis'
        && ! ($redisServer['client'] === 'predis' ? class_exists(\Predis\Client::class) : extension_loaded('redis'))
        ? 'file'
        : $cacheStore,

    // What is set above (the cache checker compares it with "default").
    'requested' => $cacheStore,

    // Redis server for the cache; AppServiceProvider copies it into the "redis" settings of config/database.php.
    'redis_server' => $redisServer,

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "apc", "array", "database", "file",
    |         "memcached", "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => env('CACHE_FILE_PATH', storage_path('framework/cache/data')),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a RAM based store such as APC or Memcached, there might
    | be other applications utilizing the same cache. So, we'll specify a
    | value to get prefixed to all our keys so we can avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache'),

];
