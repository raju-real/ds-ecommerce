<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ModelCache;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoController extends Controller
{
    /** Keyword suggestions for the tag input: every keyword already used on the site, most used first. */
    public function keywords(Request $request)
    {
        $q = mb_strtolower(trim((string) ($request->validate(['q' => 'nullable|string|max:60'])['q'] ?? '')));

        $all = ModelCache::remember(
            [ModelCache::PRODUCTS, ModelCache::CATEGORIES, ModelCache::BRANDS, ModelCache::BLOGS, ModelCache::SETTINGS],
            'seo:keywords',
            function () {
                $texts = collect(['products', 'categories', 'brands', 'blogs'])
                    ->flatMap(fn ($t) => DB::table($t)->whereNull('deleted_at')->whereNotNull('meta_keywords')->pluck('meta_keywords'))
                    ->push((string) setting('meta_keywords'));
                $count = [];
                foreach ($texts as $text) {
                    foreach (Seo::list($text) as $word) {
                        $key = mb_strtolower($word);
                        $count[$key] ??= ['text' => $word, 'uses' => 0];
                        $count[$key]['uses']++;
                    }
                }

                return collect($count)->sortByDesc('uses')->values()->all();
            }
        );

        // Words that start with the query first, then words that contain it.
        $rows = collect($all);
        if ($q !== '') {
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['text']), $q))
                ->sortBy(fn ($r) => str_starts_with(mb_strtolower($r['text']), $q) ? 0 : 1)->values();
        }

        return response()->json($rows->take(15)->values());
    }
}
