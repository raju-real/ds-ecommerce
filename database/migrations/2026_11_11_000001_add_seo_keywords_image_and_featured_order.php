<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only (safe on production data):
 *  - meta_keywords (comma separated) + meta_image (share image) on products, categories, brands, blogs
 *  - products.featured_order: position in the featured list (NULL = not in the list). is_featured still
 *    decides whether a listed product is shown on the home page, so a product can stay in the list switched off.
 */
return new class extends Migration
{
    protected array $seoTables = ['products', 'categories', 'brands', 'blogs'];

    public function up()
    {
        foreach ($this->seoTables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'meta_keywords')) {
                    $t->string('meta_keywords', 500)->nullable()->after('meta_description');
                }
                if (! Schema::hasColumn($table, 'meta_image')) {
                    $t->string('meta_image')->nullable()->after('meta_keywords');
                }
            });
        }

        if (! Schema::hasColumn('products', 'featured_order')) {
            Schema::table('products', function (Blueprint $t) {
                $t->integer('featured_order')->nullable()->after('is_featured')->index();
            });

            // Keep today's home-page order (newest first) as the starting manual order.
            $ids = DB::table('products')->where('is_featured', true)->orderByDesc('id')->pluck('id');
            foreach ($ids as $i => $id) {
                DB::table('products')->where('id', $id)->update(['featured_order' => $i + 1]);
            }
        }
    }

    public function down()
    {
        if (Schema::hasColumn('products', 'featured_order')) {
            Schema::table('products', fn (Blueprint $t) => $t->dropColumn('featured_order'));
        }
        foreach ($this->seoTables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['meta_image', 'meta_keywords'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
