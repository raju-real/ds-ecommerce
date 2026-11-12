<?php

namespace App\Providers;

use App\Repositories\CategoryRepository;
use App\Repositories\OrderRepository;
use App\Repositories\SettingRepository;
use App\Services\CartService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Redis for the cache is set in config/cache.php ("redis_server"), not in .env: use it for Laravel's
        // Redis connections ("cache" for cached data, "default" for cache locks), before anything is cached.
        if (is_array($redis = config('cache.redis_server'))) {
            $server = ['url' => null, 'host' => $redis['host'], 'password' => $redis['password'], 'port' => $redis['port']];
            config([
                'database.redis.client' => $redis['client'],
                'database.redis.options.prefix' => $redis['prefix'],
                'database.redis.cache' => $server + ['database' => $redis['database']],
                'database.redis.default' => $server + ['database' => config('database.redis.default.database', 0)],
            ]);
        }

        // One instance per request so settings are read from cache only once.
        $this->app->singleton(SettingRepository::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(\App\Services\FacebookPixel::class); // collects a request's events, sends them once at the end
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();

        // A site configured for https must never write http:// links (blocked as "mixed content" by browsers,
        // e.g. quick view and add-to-cart requests), even when the proxy in front does not say it is https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Shared storefront data, resolved once per request (menu comes from cache).
        $shared = null;
        View::composer(['layouts.frontend', 'frontend.*'], function ($view) use (&$shared) {
            $shared ??= [
                'menuCategories' => app(CategoryRepository::class)->menu(),
                'menuBrands' => app(\App\Repositories\BrandRepository::class)->active(),
                'hasBlog' => app(\App\Repositories\BlogRepository::class)->published()->isNotEmpty(),
                'footerPages' => app(\App\Repositories\PageRepository::class)->active(), // Website → Pages, in the admin order
                'cartCount' => app(CartService::class)->count(),
            ];
            $view->with($shared);
        });

        View::composer('admin.layouts.app', function ($view) {
            $view->with('orderCounts', app(OrderRepository::class)->statusCounts());
            $view->with('notify', app(\App\Repositories\NotificationRepository::class)->summary());
        });
    }
}
