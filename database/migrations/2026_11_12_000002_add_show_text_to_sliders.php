<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per slide: show the title / subtitle / button on the banner, or only the image
 * (the title is then still used as the image's alt text).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sliders', 'show_text')) {
            Schema::table('sliders', function (Blueprint $t) {
                $t->boolean('show_text')->default(true)->after('button_text');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sliders', 'show_text')) {
            Schema::table('sliders', fn (Blueprint $t) => $t->dropColumn('show_text'));
        }
    }
};
