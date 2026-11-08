<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;

class SeoController extends Controller
{
    public function sitemap(ProductRepository $products, CategoryRepository $categories)
    {
        return response()
            ->view('frontend.sitemap', [
                'products' => $products->sitemap(),
                'categories' => $categories->menu(),
                'brands' => app(\App\Repositories\BrandRepository::class)->active(),
                'blogs' => app(\App\Repositories\BlogRepository::class)->sitemap(),
            ])
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /order/',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines))->header('Content-Type', 'text/plain');
    }
}
