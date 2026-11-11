<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per slide, show or hide each part on the banner separately: title, subtitle, button.
 * Existing slides keep how they look: each new switch starts with the old "show text" value.
 * (show_text stays in the table, unused, so nothing is lost.)
 */
return new class extends Migration
{
    public function up(): void
    {
        $cols = ['show_title', 'show_subtitle', 'show_button'];
        Schema::table('sliders', function (Blueprint $t) use ($cols) {
            foreach (array_reverse($cols) as $col) {
                if (! Schema::hasColumn('sliders', $col)) {
                    $t->boolean($col)->default(true)->after('button_text');
                }
            }
        });
        if (Schema::hasColumn('sliders', 'show_text')) {
            DB::table('sliders')->update(['show_title' => DB::raw('show_text'), 'show_subtitle' => DB::raw('show_text'), 'show_button' => DB::raw('show_text')]);
        }
    }

    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $t) {
            foreach (['show_title', 'show_subtitle', 'show_button'] as $col) {
                if (Schema::hasColumn('sliders', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
