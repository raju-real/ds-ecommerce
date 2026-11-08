<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\BlogRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SliderRepository;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(ProductRepository $products, SliderRepository $sliders, BlogRepository $blogs)
    {
        return view('frontend.home', [
            'sliders' => $sliders->active(),
            'newArrivals' => $products->newArrivals(config('shop.home.new_arrivals')),
            'featured' => $products->featuredPage(1),
            'blogs' => $blogs->latest(config('shop.home.blogs')),
        ]);
    }

    /** "Load more" on the home Featured section: next 12 product cards as HTML (served from cache). */
    public function featured(Request $request, ProductRepository $products)
    {
        $maxPage = (int) ceil(config('shop.home.featured_max') / config('shop.home.featured'));
        $page = filter_var($request->query('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 2, 'max_range' => $maxPage]]);
        abort_if($page === false, 404);

        $result = $products->featuredPage($page);

        return response()->json([
            'html' => view('frontend.partials.product-cards', ['products' => $result['items']])->render(),
            'count' => $result['items']->count(),
            'loaded' => $result['loaded'],
            'total' => $result['total'],
            'has_more' => $result['has_more'],
            'reached_max' => $result['reached_max'],
            'next_page' => $page + 1,
        ]);
    }
}
