<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\Request;

/** Image uploads from the rich-text editor (blogs, product descriptions). */
class EditorController extends Controller
{
    public function image(Request $request, ImageService $images)
    {
        $request->validate(['image' => upload_rules('editor', true)], [
            'image.max' => 'Image must be smaller than '.round(config('shop.uploads.editor') / 1024, 1).'MB.',
            'image.mimes' => 'Images must be JPG, PNG or WebP.',
        ]);

        // Scaled down to fit (never cropped or enlarged) and stored as WebP.
        $path = $images->store($request->file('image'), 'editor/'.date('Y/m'), config('shop.images.editor'), 'max');

        return response()->json(['url' => img_url($path)]);
    }
}
