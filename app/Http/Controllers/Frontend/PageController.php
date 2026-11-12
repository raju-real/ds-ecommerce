<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\PageRepository;

/** /page/{slug}: pages built in Admin → Website → Pages (only active ones; others are 404). */
class PageController extends Controller
{
    public function show(string $slug, PageRepository $pages)
    {
        $page = $pages->findActive($slug) ?? abort(404);

        return view('frontend.pages.show', ['page' => $page]);
    }
}
