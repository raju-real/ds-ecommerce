<?php

namespace App\Console\Commands;

use App\Support\ModelCache;
use App\Support\Seo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * One-time cleanup: SEO texts saved with HTML entities ("Toothbrush &amp; Gum Care") showed up in Google
 * as "&amp;". New saves are cleaned by Seo::plain(); this fixes rows saved before.
 *
 *   php artisan seo:clean-entities           shows what would change (nothing is written)
 *   php artisan seo:clean-entities --apply   writes the changes
 */
class CleanSeoEntities extends Command
{
    protected $signature = 'seo:clean-entities {--apply : Write the changes (without it, only shows them)}';

    protected $description = 'Turn HTML entities like &amp; in meta titles, descriptions and keywords back into plain text';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $changes = 0;

        // Products, categories, brands, blog posts: meta title / description / keywords.
        foreach (['products' => 'name', 'categories' => 'name', 'brands' => 'name', 'blogs' => 'title'] as $table => $label) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $fields = array_values(array_filter(Seo::TEXT_FIELDS, fn ($f) => Schema::hasColumn($table, $f)));
            if (! $fields) {
                continue;
            }
            DB::table($table)->select(array_merge(['id', $label], $fields))
                ->where(function ($q) use ($fields) {
                    foreach ($fields as $f) {
                        $q->orWhere($f, 'like', '%&%;%');
                    }
                })
                ->lazyById(200) // by id, so rows fixed along the way are not skipped
                ->each(function ($row) use ($table, $label, $fields, $apply, &$changes) {
                    $update = [];
                    foreach ($fields as $f) {
                        $clean = $f === 'meta_keywords' ? Seo::keywords($row->$f) : Seo::plain($row->$f);
                        if ($clean !== $row->$f) {
                            $update[$f] = $clean;
                            $this->line(sprintf('  %s #%d (%s) %s: "%s" → "%s"', $table, $row->id, Str::limit((string) $row->$label, 30), $f, Str::limit((string) $row->$f, 60), Str::limit((string) $clean, 60)));
                        }
                    }
                    if ($update) {
                        $changes += count($update);
                        if ($apply) {
                            DB::table($table)->where('id', $row->id)->update($update);
                        }
                    }
                });
        }

        // Site settings shown in titles and meta tags.
        if (Schema::hasTable('settings')) {
            foreach (DB::table('settings')->whereIn('key', ['site_name', 'tagline', 'meta_title', 'meta_description', 'meta_keywords'])->where('value', 'like', '%&%;%')->get() as $row) {
                $clean = $row->key === 'meta_keywords' ? Seo::keywords($row->value) : Seo::plain($row->value);
                if ($clean !== $row->value) {
                    $changes++;
                    $this->line(sprintf('  settings %s: "%s" → "%s"', $row->key, Str::limit((string) $row->value, 60), Str::limit((string) $clean, 60)));
                    if ($apply) {
                        DB::table('settings')->where('id', $row->id)->update(['value' => $clean]);
                    }
                }
            }
        }

        if ($changes === 0) {
            $this->info('Nothing to clean — no HTML entities found in SEO texts.');

            return self::SUCCESS;
        }
        if (! $apply) {
            $this->warn("$changes field(s) would change. Nothing was written. Run again with --apply to save.");

            return self::SUCCESS;
        }

        ModelCache::bump(ModelCache::PRODUCTS, ModelCache::CATEGORIES, ModelCache::BRANDS, ModelCache::BLOGS, ModelCache::SETTINGS);
        $this->info("Done: $changes field(s) cleaned. Pages show the new text right away.");

        return self::SUCCESS;
    }
}
