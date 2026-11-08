<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The thumbnail becomes its own required upload instead of mirroring the
 * first gallery image. Existing products get their first gallery image
 * promoted to the thumbnail so nothing is shown twice.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('thumbnail_large')->nullable()->after('thumbnail');
        });

        DB::table('products')->whereNull('thumbnail_large')->orderBy('id')->each(function ($product) {
            $first = DB::table('product_images')->where('product_id', $product->id)
                ->orderBy('sort_order')->orderBy('id')->first();
            if (! $first) {
                return;
            }
            DB::table('products')->where('id', $product->id)->update(['thumbnail' => $first->thumb, 'thumbnail_large' => $first->path]);
            DB::table('product_images')->where('id', $first->id)->delete();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('thumbnail_large');
        });
    }
};
