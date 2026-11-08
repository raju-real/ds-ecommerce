<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\BlogRepository;
use App\Services\ShortLinkService;

class BlogController extends Controller
{
    public function __construct(protected BlogRepository $blogs)
    {
    }

    public function index()
    {
        $posts = $this->blogs->paginate(config('shop.per_page.blog'));
        // No published post yet (the blog is hidden everywhere) or a page past the end: not found.
        abort_if($posts->isEmpty(), 404);

        return view('frontend.blog.index', compact('posts'));
    }

    public function show(string $slug, ShortLinkService $links)
    {
        $blog = $this->blogs->findPublished($slug) ?? abort(404);

        return view('frontend.blog.show', [
            'blog' => $blog,
            'related' => $this->blogs->related($blog, 5),
            // Share with the short link when the admin created one (it is never created from the public site).
            'shareUrl' => $links->existing('blog', $blog->id) ?? $blog->url,
        ]);
    }
}
