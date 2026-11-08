<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Support\ModelCache;

class DashboardController extends Controller
{
    public function index(OrderRepository $orders, ProductRepository $products)
    {
        return view('admin.dashboard', [
            'data' => $orders->dashboard(),
            'counts' => $orders->statusCounts(),
            'productStats' => $products->stats(),
        ]);
    }

    /** Invalidates every cache group; next request rebuilds from DB. */
    public function clearCache()
    {
        ModelCache::bumpAll();

        return back()->with('success', 'Cache refreshed successfully.');
    }
}
