<?php

use App\Repositories\SettingRepository;

if (! function_exists('setting')) {
    /** Site setting from cache. */
    function setting(string $key, $default = null)
    {
        return app(SettingRepository::class)->get($key, $default);
    }
}

if (! function_exists('img_url')) {
    /** Public URL for an uploaded image path, with a neutral placeholder fallback. */
    function img_url(?string $path, string $fallback = 'images/placeholder.svg'): string
    {
        if (! $path) {
            return asset($fallback);
        }

        return preg_match('#^https?://#', $path) ? $path : asset('uploads/'.ltrim($path, '/'));
    }
}

if (! function_exists('like_escape')) {
    /** Escape LIKE wildcards so user input such as "50%" is matched literally. */
    function like_escape(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

if (! function_exists('upload_rules')) {
    /** Server-side validation rule for an image upload of the given type (see config shop.uploads). */
    function upload_rules(string $type, bool $required = false): string
    {
        $cfg = config('shop.uploads');
        $rule = ($required ? 'required' : 'nullable').'|image|mimes:'.implode(',', $cfg['mimes']).'|max:'.$cfg[$type];

        if ($min = $cfg['min'][$type] ?? null) {
            $rule .= '|dimensions:min_width='.$min[0].',min_height='.$min[1];
        }

        return $rule;
    }
}

if (! function_exists('upload_attrs')) {
    /** Matching HTML attributes so the browser enforces the same limits before upload. */
    function upload_attrs(string $type): string
    {
        $cfg = config('shop.uploads');
        $attrs = 'accept=".'.implode(',.', $cfg['mimes']).'" data-max-size="'.$cfg[$type].'"';

        if ($type === 'product') {
            $attrs .= ' data-max-files="'.$cfg['product_files'].'"';
        }
        if ($min = $cfg['min'][$type] ?? null) {
            $attrs .= ' data-min-width="'.$min[0].'" data-min-height="'.$min[1].'"';
        }
        // Largest size the server keeps: the browser scales bigger photos down to this before uploading,
        // so a 5 MB phone photo goes up as a few hundred KB (fast, and under web-server upload limits).
        $img = config('shop.images');
        $fit = [
            'thumbnail' => $img['product']['large'], 'product' => $img['product']['large'],
            'category' => $img['category'], 'brand' => $img['category'], 'slider' => $img['slider'],
            'logo' => $img['logo'], 'og_image' => $img['blog']['large'], 'blog' => $img['blog']['large'],
        ][$type] ?? null;
        if ($fit) {
            $attrs .= ' data-fit="'.$fit[0].'x'.$fit[1].'"';
        }

        return $attrs;
    }
}

if (! function_exists('upload_hint')) {
    function upload_hint(string $type): string
    {
        $cfg = config('shop.uploads');
        $hint = strtoupper(implode(', ', array_diff($cfg['mimes'], ['jpeg']))).' · max '.round($cfg[$type] / 1024, 1).'MB';

        return ($min = $cfg['min'][$type] ?? null) ? $hint.' · min '.implode('×', $min).'px' : $hint;
    }
}

if (! function_exists('money')) {
    function money($amount, bool $symbol = true): string
    {
        $amount = (float) $amount;
        $formatted = number_format($amount, fmod($amount, 1) == 0.0 ? 0 : 2);

        return $symbol ? config('shop.currency').$formatted : $formatted;
    }
}

if (! function_exists('order_status_badge')) {
    function order_status_badge(string $status): string
    {
        $cfg = config("shop.order_statuses.$status", ['label' => ucfirst($status), 'color' => 'secondary']);

        return '<span class="badge badge-soft-'.$cfg['color'].'">'.e($cfg['label']).'</span>';
    }
}
