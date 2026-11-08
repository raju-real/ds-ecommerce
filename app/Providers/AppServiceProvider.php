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
        // One instance per request so settings are read from cache only once.
        $this->app->singleton(SettingRepository::class);
        $this->app->singleton(CartService::class);
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
