<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Repositories\BlogRepository;
use App\Services\ImageService;
use App\Support\RichText;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BlogController extends Controller
{
    public function __construct(protected BlogRepository $blogs, protected ImageService $images)
    {
    }

    public function index(Request $request)
    {
        $filters = array_filter([
            'q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : null,
            'status' => in_array($request->query('status'), ['published', 'scheduled', 'hidden'], true) ? $request->query('status') : null,
        ]);

        return view('admin.blogs.index', ['blogs' => $this->blogs->adminPaginate($filters)->withQueryString(), 'filters' => $filters]);
    }

    public function create()
    {
        return view('admin.blogs.form', ['blog' => new Blog(['is_active' => true, 'published_at' => now()])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $data['slug'] = Blog::uniqueSlug(($data['slug'] ?? null) ?: $data['title']);
        $data += $this->storeCover($request);

        $blog = Blog::create($data);

        return redirect()->route($request->input('after') === 'new' ? 'admin.blogs.create' : 'admin.blogs.index')
            ->with('success', "Blog “{$blog->title}” created.");
    }

    public function edit(string $blog)
    {
        return view('admin.blogs.form', ['blog' => $this->blogs->adminFind($blog) ?? abort(404)]);
    }

    public function update(Request $request, string $blog)
    {
        $model = Blog::findByUidOrFail($blog);
        $data = $this->validated($request, $model);
        $data['slug'] = Blog::uniqueSlug(($data['slug'] ?? null) ?: $data['title'], $model->id);

        if ($cover = $this->storeCover($request)) {
            $this->images->delete($model->image, $model->thumb);
            $data += $cover;
        }
        $model->update($data);

        return redirect()->route('admin.blogs.index')->with('success', "Blog “{$model->title}” updated.");
    }

    public function destroy(string $blog)
    {
        // Soft delete: the cover image is kept so the post can be restored.
        Blog::findByUidOrFail($blog)->delete();

        return back()->with('success', 'Blog deleted.');
    }

    /** Editor "Link a blog" button: posts to link to (JSON). */
    public function linkSearch(Request $request)
    {
        $q = is_string($request->query('q')) ? mb_substr($request->query('q'), 0, 100) : '';
        $except = is_string($request->query('except')) ? $request->query('except') : null;

        return response()->json($this->blogs->pickerSearch($q, $except)->map(fn (Blog $b) => [
            'title' => $b->title,
            'slug' => $b->slug,
            'url' => route('blog.show', $b->slug),
            'published' => $b->isPublished(),
        ]));
    }

    protected function storeCover(Request $request): array
    {
        if (! $request->hasFile('image')) {
            return [];
        }
        $sizes = config('shop.images.blog');
        $file = $request->file('image');

        return [
            'image' => $this->images->store($file, 'blogs', $sizes['large'], 'cover'),
            'thumb' => $this->images->store($file, 'blogs', $sizes['thumb'], 'cover'),
        ];
    }

    protected function validated(Request $request, ?Blog $blog): array
    {
        $data = $request->validate([
            'title' => 'required|string|min:3|max:160',
            'slug' => 'nullable|alpha_dash|max:180',
            'description' => 'required|string|max:200000',
            'image' => upload_rules('blog', ! $blog?->image),
            'published_at' => 'nullable|date_format:Y-m-d',
            ...Seo::rules(),
        ], [
            'image.required' => 'Please choose a cover image.',
            'image.dimensions' => 'Cover image must be at least '.implode('×', config('shop.uploads.min.blog')).' pixels.',
            'image.max' => 'Cover image must be smaller than '.round(config('shop.uploads.blog') / 1024, 1).'MB.',
            'description.required' => 'Please write the blog description.',
            ...Seo::messages(),
        ]);

        $data['description'] = RichText::sanitize($data['description']);
        if (Blog::summarize($data['description']) === '' && ! str_contains($data['description'], '<img')) {
            throw ValidationException::withMessages(['description' => 'Please write the blog description.']);
        }

        // Keep the original time when the date did not change; a new date publishes at the start of that day.
        $date = $data['published_at'] ?? null;
        $data['published_at'] = match (true) {
            $date === null => $blog?->published_at ?? now(),
            $blog?->published_at?->toDateString() === $date => $blog->published_at,
            $date === now()->toDateString() => now(),
            default => Carbon::createFromFormat('Y-m-d', $date)->startOfDay(),
        };
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);

        return Seo::apply($request, $data, $blog, 'blogs');
    }
}
