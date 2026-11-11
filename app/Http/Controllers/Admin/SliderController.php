<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Repositories\SliderRepository;
use App\Services\ImageService;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    public function __construct(protected SliderRepository $sliders, protected ImageService $images)
    {
    }

    public function index()
    {
        return view('admin.sliders.index', ['sliders' => $this->sliders->all()]);
    }

    public function create()
    {
        return view('admin.sliders.form', ['slider' => new Slider(['is_active' => true, 'show_text' => true, 'sort_order' => Slider::nextSortOrder()])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data['image'] = $this->images->store($request->file('image'), 'sliders', config('shop.images.slider'));
        Slider::create($data);

        return redirect()->route('admin.sliders.index')->with('success', 'Slide created.');
    }

    public function edit(string $slider)
    {
        return view('admin.sliders.form', ['slider' => $this->sliders->find($slider) ?? abort(404)]);
    }

    public function update(Request $request, string $slider)
    {
        $model = Slider::findByUidOrFail($slider);
        $data = $this->validated($request, false);

        if ($request->hasFile('image')) {
            $this->images->delete($model->image);
            $data['image'] = $this->images->store($request->file('image'), 'sliders', config('shop.images.slider'));
        }
        $model->update($data);

        return redirect()->route('admin.sliders.index')->with('success', 'Slide updated.');
    }

    /** Drag & drop on the Sliders page: {sliders: [uid, …]} in the new order → sort_order 0, 1, 2 … */
    public function saveSort(Request $request)
    {
        $data = $request->validate([
            'sliders' => 'required|array|max:200',
            'sliders.*' => ['string', 'regex:/^[a-z0-9]{6,20}$/'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            foreach (array_values($data['sliders']) as $i => $uid) {
                Slider::where('unique_id', $uid)->toBase()->update(['sort_order' => $i, 'updated_at' => now()]);
            }
        });
        \App\Support\ModelCache::bump(\App\Support\ModelCache::SLIDERS); // one refresh: home page + this list

        return response()->json(['ok' => true, 'message' => 'Banner order saved.']);
    }

    public function destroy(string $slider)
    {
        // Soft delete: the banner file is kept so the slide can be restored.
        Slider::findByUidOrFail($slider)->delete();

        return back()->with('success', 'Slide deleted.');
    }

    protected function validated(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            // Always required: shown on the banner when "Show text" is on, and always the image's alt text (SEO).
            'title' => 'required|string|max:80',
            'subtitle' => 'required|string|max:150',
            'button_text' => 'nullable|string|max:30|required_with:link',
            'link' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'image' => upload_rules('slider', $imageRequired),
            'title_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'subtitle_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'button_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'button_text_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text_shade' => 'nullable|in:'.implode(',', array_keys(Slider::SHADES)),
        ], [
            'title.required' => 'Please enter a title (also used as the image description for Google).',
            'subtitle.required' => 'Please enter a subtitle.',
            'title_color.regex' => 'Colour code looks like #FFFFFF.',
            'subtitle_color.regex' => 'Colour code looks like #FFFFFF.',
            'button_color.regex' => 'Colour code looks like #FFFFFF.',
            'button_text_color.regex' => 'Colour code looks like #FFFFFF.',
            'image.dimensions' => 'Banner must be at least '.implode('×', config('shop.uploads.min.slider')).' pixels.',
        ]);
        unset($data['image']);
        foreach (['title_color', 'subtitle_color', 'button_color', 'button_text_color'] as $c) {
            $data[$c] = ($data[$c] ?? null) ? strtolower($data[$c]) : null;
        }
        $data['text_shade'] = $data['text_shade'] ?? 'none';
        if (! \Illuminate\Support\Facades\Schema::hasColumn('sliders', 'text_shade')) {
            // Migration not run yet: save the slide without colours instead of failing.
            unset($data['title_color'], $data['subtitle_color'], $data['button_color'], $data['button_text_color'], $data['text_shade']);
        }
        $data['sort_order'] ??= ($uid = $request->route('slider')) ? Slider::where('unique_id', $uid)->value('sort_order') : Slider::nextSortOrder();
        $data['is_active'] = $request->boolean('is_active');
        if (\Illuminate\Support\Facades\Schema::hasColumn('sliders', 'show_text')) {
            $data['show_text'] = $request->boolean('show_text');
        }

        return $data;
    }
}
