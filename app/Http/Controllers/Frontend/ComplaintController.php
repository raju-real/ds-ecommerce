<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ImageService;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** /complaint — the customer complaint form (shop menu → Complaint). Each complaint gets a number 00001, 00002 … */
class ComplaintController extends Controller
{
    public function create()
    {
        return view('frontend.complaint');
    }

    public function store(Request $request, ImageService $images)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'mobile' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'message' => 'required|string|min:10|max:3000',
            'images' => 'nullable|array|max:2',
            'images.*' => upload_rules('complaint'),
        ], [
            'mobile.regex' => 'Enter a valid 11 digit mobile number (01XXXXXXXXX).',
            'message.required' => 'Please write your complaint.',
            'message.min' => 'Please describe the problem in a few words (10 characters).',
            'images.max' => 'You can add up to 2 photos.',
        ]);

        $paths = [];
        foreach (array_slice($request->file('images', []), 0, 2) as $file) {
            $paths[] = $images->store($file, 'complaints', config('shop.images.complaint'), 'max', 'webp');
        }

        $complaint = DB::transaction(fn () => Complaint::create([
            'complaint_no' => str_pad((string) Sequence::next('complaint'), 5, '0', STR_PAD_LEFT),
            'name' => trim($data['name']),
            'mobile' => $data['mobile'],
            'message' => trim($data['message']),
            'images' => $paths ?: null,
            'status' => 'received',
            'ip' => $request->ip(),
            'status_changed_at' => now(),
        ]));

        return redirect()->route('complaint')->with('complaint_no', $complaint->complaint_no);
    }
}
