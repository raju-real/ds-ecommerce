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
            // Complaints still to handle (received / on review), newest first — once the migration has run.
            'complaints' => \Illuminate\Support\Facades\Schema::hasTable('complaints')
                ? \App\Models\Complaint::whereIn('status', \App\Models\Complaint::OPEN)->latest('id')->take(5)->get()
                : collect(),
            'openComplaints' => \Illuminate\Support\Facades\Schema::hasTable('complaints')
                ? \App\Models\Complaint::whereIn('status', \App\Models\Complaint::OPEN)->count()
                : 0,
        ]);
    }

    /** Invalidates every cache group; next request rebuilds from DB. */
    public function clearCache()
    {
        ModelCache::bumpAll();

        return back()->with('success', 'Cache refreshed successfully.');
    }
}
