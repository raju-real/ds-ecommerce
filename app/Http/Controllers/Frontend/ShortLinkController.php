<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\ShortLinkService;
use App\Support\Bots;
use Illuminate\Http\Request;

class ShortLinkController extends Controller
{
    /** /s/{code}: count the visit and forward; link-preview bots get a preview page with the image instead. */
    public function go(Request $request, string $code, ShortLinkService $links)
    {
        $link = $links->find($code) ?? abort(404);
        $target = $links->target($link['type'], $link['target_id']);

        if (! $target) {
            // The product or post was removed or hidden: send people somewhere useful instead of an error.
            return $link['type'] === 'blog'
                ? redirect()->route('home')->with('error', 'Sorry, this article is no longer available.')
                : redirect()->route('products.index')->with('error', 'Sorry, this product is no longer available.');
        }

        if (Bots::isLinkPreview($request->userAgent())) {
            return response()->view('frontend.short-preview', ['target' => $target, 'shortUrl' => route('short.go', $code), 'type' => $link['type']])
                ->header('X-Robots-Tag', 'noindex');
        }

        // Browser prefetch/preview requests are not clicks.
        if (! in_array(strtolower((string) $request->header('Purpose', $request->header('Sec-Purpose'))), ['prefetch', 'preview'], true)) {
            $links->recordClick($link['id']);
        }

        // 302 (not 301) so browsers ask again next time and every click is counted.
        return redirect()->away($target['url'], 302)->header('X-Robots-Tag', 'noindex')->header('Cache-Control', 'no-store');
    }
}
