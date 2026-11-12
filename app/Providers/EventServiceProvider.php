<?php

namespace App\Providers;

use App\Observers\CacheObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Every cached model is observed; any write bumps its ModelCache groups.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $observers = [
        \App\Models\Setting::class => [CacheObserver::class],
        \App\Models\Category::class => [CacheObserver::class],
        \App\Models\Subcategory::class => [CacheObserver::class],
        \App\Models\Vendor::class => [CacheObserver::class],
        \App\Models\Brand::class => [CacheObserver::class],
        \App\Models\Product::class => [CacheObserver::class],
        \App\Models\ProductImage::class => [CacheObserver::class],
        \App\Models\District::class => [CacheObserver::class],
        \App\Models\Thana::class => [CacheObserver::class],
        \App\Models\Slider::class => [CacheObserver::class],
        \App\Models\Blog::class => [CacheObserver::class],
        \App\Models\Page::class => [CacheObserver::class],
        \App\Models\ShortLink::class => [CacheObserver::class],
        \App\Models\AdminNotification::class => [CacheObserver::class],
        \App\Models\Coupon::class => [CacheObserver::class],
        \App\Models\Order::class => [CacheObserver::class],
        \App\Models\OrderItem::class => [CacheObserver::class],
        \App\Models\OrderStatusHistory::class => [CacheObserver::class],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
