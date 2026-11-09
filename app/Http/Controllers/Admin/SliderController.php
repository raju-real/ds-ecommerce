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
        return view('admin.sliders.form', ['slider' => new Slider(['is_active' => true, 'sort_order' => Slider::nextSortOrder()])]);
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

    public function destroy(string $slider)
    {
        // Soft delete: the banner file is kept so the slide can be restored.
        Slider::findByUidOrFail($slider)->delete();

        return back()->with('success', 'Slide deleted.');
    }

    protected function validated(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:80',
            'subtitle' => 'nullable|string|max:150',
            'button_text' => 'nullable|string|max:30|required_with:link',
            'link' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'image' => upload_rules('slider', $imageRequired),
        ], [
            'image.dimensions' => 'Banner must be at least '.implode('×', config('shop.uploads.min.slider')).' pixels.',
        ]);
        unset($data['image']);
        $data['sort_order'] ??= ($uid = $request->route('slider')) ? Slider::where('unique_id', $uid)->value('sort_order') : Slider::nextSortOrder();
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
