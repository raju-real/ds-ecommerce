<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Sitemap;

class SeoController extends Controller
{
    public function sitemap(Sitemap $sitemap)
    {
        return response()
            ->view('frontend.sitemap', ['groups' => $sitemap->groups()])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex'); // the file itself is not a search result
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /order/',
            // Data and popup endpoints, not pages.
            'Disallow: /search/',
            'Disallow: /featured-products',
            'Disallow: /locations/',
            'Disallow: /product/*/quick-view',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
