<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Product;
use App\Models\ShortLink;
use App\Services\ShortLinkService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShortLinkController extends Controller
{
    public function __construct(protected ShortLinkService $links)
    {
    }

    public function index(Request $request)
    {
        $filters = array_filter([
            'q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : null,
            'type' => array_key_exists((string) $request->query('type'), ShortLink::TYPES) ? $request->query('type') : null,
            'sort' => $request->query('sort') === 'clicks' ? 'clicks' : null,
        ]);

        return view('admin.short-links.index', [
            'links' => $this->links->adminPaginate($filters)->withQueryString(),
            'filters' => $filters,
            'totalClicks' => (int) ShortLink::sum('clicks'),
        ]);
    }

    /**
     * Short links for the share menus, created on first use: {targets: [{type, uid}, …]}
     * → [{type, url, clicks, last_clicked}, …] with fresh click counts.
     */
    public function ensure(Request $request)
    {
        $data = $request->validate([
            'targets' => 'required|array|min:1|max:3',
            'targets.*.type' => ['required', Rule::in(array_keys(ShortLink::TYPES))],
            'targets.*.uid' => ['required', 'string', 'regex:/^[a-z]{10}$/'],
        ]);

        return response()->json(collect($data['targets'])->map(function ($t) {
            $target = $t['type'] === 'blog' ? Blog::findByUidOrFail($t['uid']) : Product::findByUidOrFail($t['uid']);

            return ['type' => $t['type']] + $this->links->stats($this->links->ensure($t['type'], $target));
        })->values());
    }
}
