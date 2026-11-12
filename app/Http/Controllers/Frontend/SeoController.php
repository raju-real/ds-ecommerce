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
        // The .ico from Settings when uploaded (real ICO file), else the PNG favicon.
        $root = rtrim((string) config('filesystems.disks.uploads.root', public_path('uploads')), '/\\');
        foreach ([['favicon_ico', 'image/x-icon'], ['favicon', 'image/png']] as [$key, $type]) {
            $path = setting($key);
            $file = $path ? $root.'/'.ltrim($path, '/') : null;
            if ($file && is_file($file)) {
                return response()->file($file, ['Content-Type' => $type, 'Cache-Control' => 'public, max-age=86400']);
            }
        }
        abort(404);
    }

    /** /site.webmanifest — the shop's name and icons for "Add to home screen" / Android (192 and 512 px). */
    public function manifest()
    {
        $fav = setting('favicon');
        abort_unless($fav, 404);
        $name = setting('site_name', config('app.name'));

        return response()->json([
            'name' => $name,
            'short_name' => \Illuminate\Support\Str::limit($name, 12, ''),
            'start_url' => '/',
            'display' => 'browser',
            'theme_color' => '#4f46e5',
            'background_color' => '#ffffff',
            'icons' => array_values(array_filter([
                ['src' => img_url($fav), 'sizes' => '192x192', 'type' => 'image/png'],
                setting('favicon_512') ? ['src' => img_url(setting('favicon_512')), 'sizes' => '512x512', 'type' => 'image/png'] : null,
            ])),
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=86400'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
