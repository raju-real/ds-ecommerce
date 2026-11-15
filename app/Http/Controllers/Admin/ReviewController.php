<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Repositories\ReviewRepository;
use Illuminate\Http\Request;

/** Customers → Reviews: every rating & review, with a show / hide switch and delete. */
class ReviewController extends Controller
{
    public function index(Request $request, ReviewRepository $reviews)
    {
        $filters = array_filter([
            'q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : null,
            'rating' => in_array($request->query('rating'), ['1', '2', '3', '4', '5'], true) ? $request->query('rating') : null,
            'status' => in_array($request->query('status'), ['visible', 'hidden'], true) ? $request->query('status') : null,
        ]);

        return view('admin.reviews.index', ['reviews' => $reviews->adminPaginate($filters)->withQueryString(), 'filters' => $filters]);
    }

    public function toggle(string $review)
    {
        $model = ProductReview::findByUidOrFail($review);
        $model->update(['is_active' => ! $model->is_active]);

        return response()->json(['ok' => true, 'value' => $model->is_active]);
    }

    public function destroy(string $review)
    {
        // Soft delete: the order still cannot be reviewed again.
        ProductReview::findByUidOrFail($review)->delete();

        return back()->with('success', 'Review deleted.');
    }
}
