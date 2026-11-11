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

    /**
     * /favicon.ico — browsers and Google ask for this address too. Serves the favicon uploaded in Settings
     * (a 192×192 PNG; every browser and Google accept a PNG here). 404 when no favicon is set.
     */
    public function favicon()
    {
        $path = setting('favicon');
        $file = $path ? rtrim((string) config('filesystems.disks.uploads.root', public_path('uploads')), '/\\').'/'.ltrim($path, '/') : null;
        abort_unless($file && is_file($file), 404);

        return response()->file($file, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
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
