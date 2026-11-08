<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | All reads go through App\Support\ModelCache which uses the default
    | Cache:: store, so switching CACHE_DRIVER (file, redis, memcached...)
    | needs no code change. Keys are versioned per group; observers bump the
    | version when a model changes, so stale keys simply expire by TTL.
    */
    'cache' => [
        'ttl' => (int) env('SHOP_CACHE_TTL', 60 * 60 * 24 * 7), // reference data / single records
        'list_ttl' => (int) env('SHOP_CACHE_LIST_TTL', 60 * 60 * 6), // paginated / filtered listings
        'prefix' => 'shop',
    ],

    'per_page' => [
        'frontend' => 12, // 3 rows x 4 products
        'admin' => 20,
        'blog' => 9, // blog listing: 3 rows x 3 posts
    ],

    'home' => [
        'new_arrivals' => 8,
        'featured' => 12,       // per "Load more" step on the home page
        'featured_max' => 200,  // after this many, "Load more" turns into "View all"
        'category_section_min' => 6, // "Shop by Category" shows only when there are MORE categories than this
        'blogs' => 4,
    ],

    /*
    | Image sizes (px). Products are fitted inside a white square canvas so
    | grids line up; sliders are cropped to a 16:6 banner.
    */
    'images' => [
        'disk' => 'uploads',
        'format' => 'webp',
        'quality' => 82,
        'product' => ['large' => [800, 800], 'thumb' => [400, 400]],
        'category' => [300, 300],
        'slider' => [1600, 600],
        'logo' => [400, 120],
        'favicon' => [64, 64],
        'blog' => ['large' => [1200, 630], 'thumb' => [600, 315]], // cover cropped to the social-share ratio
        'editor' => [1200, 1600],                                   // images inside blog/product text, scaled down only
    ],

    /*
    | Upload limits (KB). Used by upload_rules() on the server and by
    | upload_attrs() on <input type=file> so the browser checks the same limits.
    */
    'uploads' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'thumbnail' => 5120,
        'product' => 5120,
        'product_files' => 10,
        'category' => 2048,
        'brand' => 2048,
        'slider' => 6144,
        'logo' => 2048,
        'favicon' => 1024,
        'og_image' => 4096,
        'blog' => 4096,
        'editor' => 4096,

        // Smallest accepted image (width x height), enforced on both sides.
        'min' => [
            'thumbnail' => [400, 400],
            'blog' => [800, 420],
            'slider' => [1200, 400],
        ],

        // Shown as the upload hint: ideal width x height and aspect ratio.
        'recommended' => [
            'thumbnail' => [800, 800, '1:1 square'],
            'product' => [800, 800, '1:1 square'],
            'category' => [300, 300, '1:1 square'],
            'brand' => [300, 300, 'square or wide logo'],
            'slider' => [1600, 600, '8:3 wide banner'],
            'logo' => [400, 120, 'wide, transparent PNG'],
            'favicon' => [64, 64, '1:1 square'],
            'og_image' => [1200, 630, '1.91:1'],
            'blog' => [1200, 630, '1.91:1 — also the Facebook/WhatsApp share image'],
            'editor' => [1200, 800, 'any shape; wider images are scaled to 1200 px'],
        ],
    ],

    'search' => [
        'min_chars' => 2,
        'max_chars' => 100,
        'suggest_limit' => 6,
    ],

    // New orders are numbered NT-00001, NT-00002, … (older orders keep the number they were given).
    'order_number' => [
        'prefix' => 'NT',
        'digits' => 5,
    ],

    'otp' => [
        'length' => 6,
        'expires_minutes' => 5,
        'max_attempts' => 5,
        'resend_after_seconds' => 60,
        'max_resends' => 3,
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'), // log | http
        'url' => env('SMS_API_URL'),
        'api_key' => env('SMS_API_KEY'),
        'sender_id' => env('SMS_SENDER_ID'),
    ],

    // Admin-side order workflow. The customer only sees the 3-step public status.
    'order_statuses' => [
        'pending' => ['label' => 'Pending', 'color' => 'warning', 'public' => 'pending'],
        'received' => ['label' => 'Received', 'color' => 'info', 'public' => 'received'],
        'processing' => ['label' => 'Processing', 'color' => 'primary', 'public' => 'processing'],
        'forwarded' => ['label' => 'Forwarded to Vendor', 'color' => 'indigo', 'public' => 'processing'],
        'shipped' => ['label' => 'Handed to Courier', 'color' => 'purple', 'public' => 'processing'],
        'delivered' => ['label' => 'Delivered', 'color' => 'success', 'public' => 'processing'],
        'cancelled' => ['label' => 'Cancelled', 'color' => 'danger', 'public' => 'cancelled'],
        'returned' => ['label' => 'Returned', 'color' => 'secondary', 'public' => 'cancelled'],
    ],

    'public_statuses' => [
        'pending' => ['label' => 'Pending', 'icon' => 'bi-hourglass-split', 'text' => 'We have got your order request.'],
        'received' => ['label' => 'Received', 'icon' => 'bi-clipboard-check', 'text' => 'Your order is confirmed by our team.'],
        'processing' => ['label' => 'Processing', 'icon' => 'bi-truck', 'text' => 'Your order is being prepared & shipped.'],
    ],

    'currency' => '৳',
];
