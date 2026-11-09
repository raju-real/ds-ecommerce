<?php

namespace App\Support;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Meta title, description, keywords and share image — the same fields and rules for products,
 * categories, brands and blogs (form partial: admin/partials/seo-fields). All optional.
 */
class Seo
{
    public const MAX_KEYWORDS = 20;

    /** Validation rules merged into each form's own rules. */
    public static function rules(): array
    {
        return [
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|array|max:'.self::MAX_KEYWORDS,
            'meta_keywords.*' => 'nullable|string|max:60',
            'meta_image' => upload_rules('meta_image'),
            'remove_meta_image' => 'nullable|boolean',
        ];
    }

    public static function messages(): array
    {
        return [
            'meta_keywords.max' => 'Use at most '.self::MAX_KEYWORDS.' keywords.',
            'meta_keywords.*.max' => 'Each keyword must be 60 characters or fewer.',
            'meta_image.max' => 'The share image must be smaller than '.round(config('shop.uploads.meta_image') / 1024, 1).'MB.',
            'meta_image.mimes' => 'The share image must be JPG, PNG or WebP.',
            'meta_image.dimensions' => 'The share image must be at least '.implode('×', config('shop.uploads.min.meta_image')).' pixels.',
        ];
    }

    /** ["Smart watch", " smart WATCH ", "bd"] → "Smart watch, bd" (trimmed, no duplicates, no commas inside). */
    public static function keywords(array|string|null $value): ?string
    {
        $list = is_array($value) ? $value : explode(',', (string) $value);
        $seen = [];
        foreach ($list as $word) {
            $word = trim(preg_replace('/\s+/u', ' ', str_replace([',', '<', '>', '"'], ' ', (string) $word)));
            $key = mb_strtolower($word);
            if ($word !== '' && ! isset($seen[$key]) && count($seen) < self::MAX_KEYWORDS) {
                $seen[$key] = Str::limit($word, 60, '');
            }
        }
        $joined = implode(', ', $seen);

        return $joined === '' ? null : Str::limit($joined, 500, '');
    }

    /** "a, b, c" → ['a', 'b', 'c'] (for the tag input; an array from old() input is passed through). */
    public static function list(array|string|null $keywords): array
    {
        $items = is_array($keywords) ? $keywords : explode(',', (string) $keywords);

        return array_values(array_filter(array_map(fn ($k) => trim((string) $k), $items), 'strlen'));
    }

    /**
     * Turns the validated SEO input into columns: keywords as text, and the share image stored
     * (1200×630, JPG — the format every social app reads), replaced or removed. Old files are deleted.
     */
    public static function apply(Request $request, array $data, ?Model $model, string $folder): array
    {
        if (array_key_exists('meta_keywords', $data) || $request->has('meta_keywords_present')) {
            $data['meta_keywords'] = self::keywords($data['meta_keywords'] ?? null);
        }
        unset($data['meta_image'], $data['remove_meta_image']);

        $images = app(ImageService::class);
        if ($request->hasFile('meta_image')) {
            $data['meta_image'] = $images->store($request->file('meta_image'), $folder.'/share', config('shop.images.share'), 'cover', 'jpg');
            $images->delete($model?->meta_image);
        } elseif ($request->boolean('remove_meta_image') && $model?->meta_image) {
            $images->delete($model->meta_image);
            $data['meta_image'] = null;
        }

        return $data;
    }
}
