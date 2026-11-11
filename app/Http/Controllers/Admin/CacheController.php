<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CacheHealth;
use App\Support\ModelCache;

/** Admin → Settings → Cache: which cache driver is used, live figures and a test; refresh cached site data. */
class CacheController extends Controller
{
    public function index()
    {
        return view('admin.cache.index', ['r' => CacheHealth::report()]);
    }

    /** New versions for every group: pages rebuild from the database on their next visit. Nothing is deleted. */
    public function refresh()
    {
        ModelCache::bumpAll();

        return redirect()->route('admin.cache')->with('success', 'All cached site data refreshed. Pages rebuild from the database on their next visit.');
    }
}
