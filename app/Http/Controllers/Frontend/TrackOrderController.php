<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class TrackOrderController extends Controller
{
    public function index(Request $request, OrderRepository $orders)
    {
        $order = null;
        $searched = $request->filled('order') || $request->filled('mobile');

        if ($searched) {
            $request->validate([
                'order' => 'required|string|max:30',
                'mobile' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            ], ['mobile.regex' => 'Enter the 11 digit mobile number used for the order.']);

            $order = $orders->track($request->input('order'), $request->input('mobile'));
        }

        return view('frontend.track', compact('order', 'searched'));
    }
}
