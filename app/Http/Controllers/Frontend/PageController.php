<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\PageRepository;

/** /{slug} (e.g. /privacy-policy): pages built in Admin → Website → Pages (only active ones; others are 404). */
class PageController extends Controller
{
    public function show(string $slug, PageRepository $pages)
    {
        $page = $pages->findActive($slug) ?? abort(404);

        return view('frontend.pages.show', ['page' => $page]);
    }

    /** Old /page/{slug} links (shared before pages moved to the top level) → /{slug}, permanently. */
    public function legacy(string $slug)
    {
        return redirect()->route('page.show', $slug, 301);
    }
}
