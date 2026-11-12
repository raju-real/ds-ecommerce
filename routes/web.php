<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Frontend;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/
Route::get('/', [Frontend\HomeController::class, 'index'])->name('home');
Route::get('/products', [Frontend\ProductController::class, 'index'])->name('products.index');
Route::get('/featured-products', [Frontend\HomeController::class, 'featured'])->middleware('throttle:search')->name('home.featured');
Route::get('/category/{slug}', [Frontend\ProductController::class, 'category'])->name('category.show');
Route::get('/category/{slug}/{sub}', [Frontend\ProductController::class, 'subcategory'])->name('subcategory.show');
Route::get('/product/{slug}', [Frontend\ProductController::class, 'show'])->name('product.show');
Route::get('/product/{slug}/quick-view', [Frontend\ProductController::class, 'quickView'])->name('product.quick-view');
Route::get('/brand/{slug}', [Frontend\ProductController::class, 'brand'])->name('brand.show');
Route::get('/blog', [Frontend\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [Frontend\BlogController::class, 'show'])->name('blog.show');
// Old /page/{slug} links → the short address (pages are at /{slug}, see the end of this file).
Route::get('/page/{slug}', [Frontend\PageController::class, 'legacy'])->name('page.legacy');
Route::get('/search/suggest', [Frontend\SearchController::class, 'suggest'])->middleware('throttle:search')->name('search.suggest');

// Shareable direct-order link (social posts): adds the product and opens checkout.
// Short share links (/s/abc123) with click counting; rate-limited so counts cannot be inflated by one visitor.
Route::get('/s/{code}', [Frontend\ShortLinkController::class, 'go'])->where('code', '[a-z0-9]{4,12}')->middleware('throttle:60,1')->name('short.go');
Route::get('/buy/{slug}', [Frontend\CartController::class, 'buyNow'])->name('buy-now');

Route::controller(Frontend\CartController::class)->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', 'summary')->name('summary');
    Route::post('/add', 'add')->name('add');
    Route::post('/update', 'update')->name('update');
    Route::post('/remove', 'remove')->name('remove');
});

Route::controller(Frontend\CheckoutController::class)->group(function () {
    Route::get('/checkout', 'index')->name('checkout');
    Route::post('/checkout', 'store')->middleware('throttle:checkout')->name('checkout.store');
    // Rate-limited so coupon codes can't be guessed by trying many.
    Route::post('/checkout/coupon', 'coupon')->middleware('throttle:20,1')->name('checkout.coupon');
    Route::get('/order/{number}/verify', 'verifyForm')->name('checkout.verify');
    Route::post('/order/{number}/verify', 'verify')->middleware('throttle:otp')->name('checkout.verify.submit');
    Route::post('/order/{number}/resend', 'resend')->middleware('throttle:otp-resend')->name('checkout.resend');
    Route::get('/order/{number}/complete', 'success')->name('checkout.success');
});

Route::get('/track-order', [Frontend\TrackOrderController::class, 'index'])->middleware('throttle:track')->name('track');
Route::get('/locations/{districtId}/thanas', [Frontend\LocationController::class, 'thanas'])->name('locations.thanas');
Route::get('/sitemap.xml', [Frontend\SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [Frontend\SeoController::class, 'robots']);
Route::get('/favicon.ico', [Frontend\SeoController::class, 'favicon'])->name('favicon');
Route::get('/site.webmanifest', [Frontend\SeoController::class, 'manifest'])->name('manifest');

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:admin-login')->name('login.submit');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('categories/sort', [Admin\CategoryController::class, 'sort'])->name('categories.sort');
        Route::post('categories/sort', [Admin\CategoryController::class, 'saveSort'])->name('categories.sort.save');
        Route::resource('categories', Admin\CategoryController::class)->except('show');
        Route::resource('subcategories', Admin\SubcategoryController::class)->except('show');
        Route::get('categories/{category}/subcategories', [Admin\SubcategoryController::class, 'byCategory'])->name('categories.subcategories');
        Route::resource('vendors', Admin\VendorController::class)->except('show');
        Route::resource('brands', Admin\BrandController::class)->except('show');

        Route::controller(Admin\ProductController::class)->prefix('products')->name('products.featured')->group(function () {
            Route::get('featured', 'featured');
            Route::get('featured/search', 'featuredSearch')->name('.search');
            Route::post('featured/sort', 'featuredSort')->name('.sort');
            Route::patch('{product}/featured/toggle', 'featuredToggle')->name('.toggle');
            Route::post('{product}/featured', 'feature')->name('.add');
            Route::delete('{product}/featured', 'unfeature')->name('.remove');
        });
        Route::get('products/check-code', [Admin\ProductController::class, 'checkCode'])->name('products.check-code');
        Route::resource('products', Admin\ProductController::class);
        Route::patch('products/{product}/toggle/{field}', [Admin\ProductController::class, 'toggle'])->name('products.toggle');
        Route::delete('products/{product}/images/{image}', [Admin\ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::post('products/{product}/images/sort', [Admin\ProductController::class, 'sortImages'])->name('products.images.sort');

        Route::controller(Admin\OrderController::class)->prefix('orders')->name('orders.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{order}', 'show')->name('show');
            Route::get('{order}/invoice', 'invoice')->name('invoice');
            Route::post('{order}/status', 'status')->name('status');
            Route::post('{order}/forward', 'forward')->name('forward');
            Route::post('{order}/details', 'details')->name('details');
            Route::get('{order}/courier-ratio', 'courierRatio')->middleware('throttle:30,1')->name('courier-ratio');
            Route::post('{order}/verify', 'markVerified')->name('verify');
            Route::delete('{order}', 'destroy')->name('destroy');
        });

        Route::post('sliders/sort', [Admin\SliderController::class, 'saveSort'])->name('sliders.sort');
        Route::resource('sliders', Admin\SliderController::class)->except('show');
        Route::post('pages/sort', [Admin\PageController::class, 'saveSort'])->name('pages.sort');
        Route::patch('pages/{page}/toggle', [Admin\PageController::class, 'toggle'])->name('pages.toggle');
        Route::resource('pages', Admin\PageController::class)->except('show');
        Route::get('blogs/link-search', [Admin\BlogController::class, 'linkSearch'])->name('blogs.link-search');
        Route::resource('blogs', Admin\BlogController::class)->except('show');
        Route::resource('coupons', Admin\CouponController::class)->except('show');
        Route::get('short-links', [Admin\ShortLinkController::class, 'index'])->name('short-links.index');
        Route::post('short-links/ensure', [Admin\ShortLinkController::class, 'ensure'])->name('short-links.ensure');

        Route::controller(Admin\NotificationController::class)->prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('summary', 'summary')->name('summary');
            Route::post('mark-all', 'markAll')->name('mark-all');
            Route::get('{notification}/open', 'open')->name('open');
            Route::patch('{notification}/toggle', 'toggle')->name('toggle');
        });

        Route::post('editor/images', [Admin\EditorController::class, 'image'])->middleware('throttle:60,1')->name('editor.image');
        Route::get('seo/keywords', [Admin\SeoController::class, 'keywords'])->name('seo.keywords');
        Route::post('seo/slug', [Admin\SeoController::class, 'slug'])->middleware('throttle:60,1')->name('seo.slug');
        Route::resource('districts', Admin\DistrictController::class)->except('show');
        Route::resource('thanas', Admin\ThanaController::class)->except('show');

        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports');
        Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings');
        Route::get('cache', [Admin\CacheController::class, 'index'])->name('cache');
        Route::post('cache/refresh', [Admin\CacheController::class, 'refresh'])->middleware('throttle:10,1')->name('cache.refresh');
        Route::post('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/facebook-test', [Admin\SettingController::class, 'facebookTest'])->middleware('throttle:10,1')->name('settings.facebook-test');
        Route::post('settings/courier-test', [Admin\SettingController::class, 'courierTest'])->middleware('throttle:10,1')->name('settings.courier-test');
        Route::post('settings/sms-balance', [Admin\SettingController::class, 'smsBalance'])->middleware('throttle:20,1')->name('settings.sms-balance');
        Route::post('settings/sms-test', [Admin\SettingController::class, 'smsTest'])->middleware('throttle:5,1')->name('settings.sms-test');
        Route::get('profile', [Admin\ProfileController::class, 'edit'])->name('profile');
        Route::post('profile', [Admin\ProfileController::class, 'update'])->name('profile.update');
        Route::post('cache/clear', [Admin\DashboardController::class, 'clearCache'])->name('cache.clear');
    });
});

/*
|--------------------------------------------------------------------------
| Pages (Admin → Website → Pages) at the top level: /privacy-policy, /about-us …
|--------------------------------------------------------------------------
| Must stay LAST: every shop and admin address above is matched first. A page can never take a
| name used above (Page::reservedSlugs), and unknown names are a normal 404.
*/
Route::get('/{slug}', [Frontend\PageController::class, 'show'])->where('slug', '[A-Za-z0-9_-]+')->name('page.show');
