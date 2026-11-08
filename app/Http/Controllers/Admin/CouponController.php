<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Support\ModelCache;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $filters = array_filter([
            'q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 50) : null,
            'type' => array_key_exists((string) $request->query('type'), Coupon::TYPES) ? $request->query('type') : null,
        ]);
        $key = 'admin:list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        $coupons = ModelCache::rememberList([ModelCache::COUPONS, ModelCache::ORDERS], $key, fn () => Coupon::query()
            ->withCount(['orders as used_count' => fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned'])])
            ->withSum(['orders as discount_given' => fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned'])], 'coupon_discount')
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where('code', 'like', '%'.like_escape(strtoupper($s)).'%'))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->latest('id')
            ->paginate(config('shop.per_page.admin')));

        return view('admin.coupons.index', ['coupons' => $coupons->withQueryString(), 'filters' => $filters]);
    }

    public function create()
    {
        return view('admin.coupons.form', ['coupon' => new Coupon(['type' => 'all_users', 'discount_type' => 'fixed', 'per_mobile_limit' => 1, 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $coupon = Coupon::create($this->validated($request));

        return redirect()->route('admin.coupons.index')->with('success', "Coupon {$coupon->code} created.");
    }

    public function edit(string $coupon)
    {
        return view('admin.coupons.form', ['coupon' => Coupon::findByUidOrFail($coupon)]);
    }

    public function update(Request $request, string $coupon)
    {
        $model = Coupon::findByUidOrFail($coupon);
        $model->update($this->validated($request, $model));

        return redirect()->route('admin.coupons.index')->with('success', "Coupon {$model->code} updated.");
    }

    public function destroy(string $coupon)
    {
        // Soft delete: orders that used it keep their coupon code and discount.
        Coupon::findByUidOrFail($coupon)->delete();

        return back()->with('success', 'Coupon deleted.');
    }

    protected function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)->whereNull('deleted_at')],
            'type' => ['required', Rule::in(array_keys(Coupon::TYPES))],
            'discount_type' => ['required', Rule::in(array_keys(Coupon::DISCOUNT_TYPES))],
            'value' => ['required', 'numeric', 'min:1', $request->input('discount_type') === 'percent' ? 'max:100' : 'max:9999999'],
            'max_discount' => 'nullable|numeric|min:1|max:9999999',
            'min_order' => 'nullable|numeric|min:0|max:9999999',
            'usage_limit' => 'nullable|integer|min:1|max:1000000',
            'per_mobile_limit' => 'required|integer|min:1|max:1000',
            'starts_at' => 'nullable|date_format:Y-m-d',
            'ends_at' => 'nullable|date_format:Y-m-d|after_or_equal:starts_at',
            'mobiles' => 'nullable|string|max:20000',
            'note' => 'nullable|string|max:255',
        ], [
            'code.regex' => 'Use letters, numbers, dashes or underscores only (no spaces).',
            'code.unique' => 'Another coupon already uses this code.',
            'value.max' => $request->input('discount_type') === 'percent' ? 'A percentage can be at most 100.' : 'The amount is too large.',
            'ends_at.after_or_equal' => 'The end date must be on or after the start date.',
        ]);

        // Mobile list (fixed users): one per line, or separated by commas/spaces; each must be a valid number.
        $mobiles = collect(preg_split('/[\s,;]+/', (string) ($data['mobiles'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($m) => preg_replace('/^(\+?88)(?=01)/', '', $m)) // allow +8801… / 8801…
            ->unique()->values();
        if ($data['type'] === 'fixed_user') {
            $bad = $mobiles->reject(fn ($m) => preg_match('/^01[3-9]\d{8}$/', $m));
            if ($mobiles->isEmpty()) {
                throw ValidationException::withMessages(['mobiles' => 'Add at least one mobile number for a fixed-user coupon.']);
            }
            if ($bad->isNotEmpty()) {
                throw ValidationException::withMessages(['mobiles' => 'Not valid 11 digit numbers: '.Str::limit($bad->take(5)->implode(', '), 120).'.']);
            }
        }

        $data['mobiles'] = $data['type'] === 'fixed_user' ? $mobiles->all() : null;
        $data['max_discount'] = $data['discount_type'] === 'percent' ? ($data['max_discount'] ?? null) : null;
        $data['starts_at'] = isset($data['starts_at']) ? Carbon::createFromFormat('Y-m-d', $data['starts_at'])->startOfDay() : null;
        $data['ends_at'] = isset($data['ends_at']) ? Carbon::createFromFormat('Y-m-d', $data['ends_at'])->endOfDay() : null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
