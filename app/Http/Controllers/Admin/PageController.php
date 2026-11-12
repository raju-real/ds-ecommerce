<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use App\Repositories\PageRepository;
use App\Services\ImageService;
use App\Support\ModelCache;
use App\Support\RichText;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Website → Pages: build pages (About us, Privacy policy …) shown at /page/{slug} and linked from the footer. */
class PageController extends Controller
{
    public function __construct(protected PageRepository $pages, protected ImageService $images)
    {
    }

    public function index()
    {
        return view('admin.pages.index', ['pages' => $this->pages->all()]);
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new Page(['is_active' => true, 'sort_order' => Page::nextSortOrder()])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $data['slug'] = Page::uniqueSlug(($data['slug'] ?? null) ?: $data['name']);
        $data['sort_order'] = Page::nextSortOrder();
        if ($request->hasFile('image')) {
            $data['image'] = $this->images->store($request->file('image'), 'pages', config('shop.images.page'), 'cover');
        }
        $page = Page::create($data);

        return redirect()->route($request->input('after') === 'new' ? 'admin.pages.create' : 'admin.pages.index')
            ->with('success', "Page “{$page->name}” created.");
    }

    public function edit(string $page)
    {
        return view('admin.pages.form', ['page' => $this->pages->adminFind($page) ?? abort(404)]);
    }

    public function update(Request $request, string $page)
    {
        $model = Page::findByUidOrFail($page);
        $data = $this->validated($request, $model);
        $data['slug'] = Page::uniqueSlug(($data['slug'] ?? null) ?: $data['name'], $model->id);

        if ($request->hasFile('image')) {
            $this->images->delete($model->image);
            $data['image'] = $this->images->store($request->file('image'), 'pages', config('shop.images.page'), 'cover');
        } elseif ($request->boolean('remove_image') && $model->image) {
            $this->images->delete($model->image);
            $data['image'] = null;
        }
        $model->update($data);

        return redirect()->route('admin.pages.index')->with('success', "Page “{$model->name}” updated.");
    }

    public function destroy(string $page)
    {
        // Soft delete: the image is kept so the page can be restored; its address is never reused.
        Page::findByUidOrFail($page)->delete();

        return back()->with('success', 'Page deleted.');
    }

    /** Active / inactive switch on the list. */
    public function toggle(string $page)
    {
        $model = Page::findByUidOrFail($page);
        $model->update(['is_active' => ! $model->is_active]);

        return response()->json(['ok' => true, 'value' => $model->is_active]);
    }

    /** Drag & drop on the list: {pages: [uid, …]} in the new order → sort_order 0, 1, 2 … */
    public function saveSort(Request $request)
    {
        $data = $request->validate([
            'pages' => 'required|array|max:500',
            'pages.*' => ['string', 'regex:/^[a-z]{10}$/'],
        ]);
        DB::transaction(function () use ($data) {
            foreach (array_values($data['pages']) as $i => $uid) {
                Page::where('unique_id', $uid)->toBase()->update(['sort_order' => $i, 'updated_at' => now()]);
            }
        });
        ModelCache::bump(ModelCache::PAGES); // one refresh: footer, sitemap and this list

        return response()->json(['ok' => true, 'message' => 'Page order saved.']);
    }

    protected function validated(Request $request, ?Page $page): array
    {
        // An "empty" editor still sends <p><br></p>: treat it as empty so its message shows together with the others.
        $html = (string) $request->input('content');
        if (Blog::summarize($html) === '' && ! str_contains($html, '<img')) {
            $request->merge(['content' => '']);
        }
        $data = $request->validate([
            'name' => 'required|string|min:2|max:160',
            'slug' => 'nullable|alpha_dash|max:180',
            'is_active' => 'required|in:0,1',
            'content' => 'required|string|max:200000',
            'image' => upload_rules('page'),
            'remove_image' => 'nullable|boolean',
            ...Seo::rules(),
        ], [
            'name.required' => 'Please enter the page name.',
            'content.required' => 'Please write the page content.',
            'image.dimensions' => 'The image must be at least '.implode('×', config('shop.uploads.min.page')).' pixels.',
            'image.max' => 'The image must be smaller than '.round(config('shop.uploads.page') / 1024, 1).'MB.',
            ...Seo::messages(),
        ]);

        $data['content'] = RichText::sanitize($data['content']);
        if (Blog::summarize($data['content']) === '' && ! str_contains($data['content'], '<img')) {
            throw ValidationException::withMessages(['content' => 'Please write the page content.']);
        }
        $data['is_active'] = $data['is_active'] === '1';
        unset($data['image'], $data['remove_image']);

        return Seo::apply($request, $data, $page, 'pages');
    }
}
