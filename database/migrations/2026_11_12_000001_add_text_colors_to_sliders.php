<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banner text colours, chosen per slide so the text reads well on each image:
 * title, subtitle, button background + button text (#RRGGBB, empty = default), and an optional shade behind the text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sliders', function (Blueprint $t) {
            foreach (['title_color', 'subtitle_color', 'button_color', 'button_text_color'] as $col) {
                if (! Schema::hasColumn('sliders', $col)) {
                    $t->string($col, 7)->nullable()->after('link');
                }
            }
            if (! Schema::hasColumn('sliders', 'text_shade')) {
                $t->string('text_shade', 10)->default('none')->after('link'); // none | dark | light
            }
        });
    }

    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $t) {
            foreach (['title_color', 'subtitle_color', 'button_color', 'button_text_color', 'text_shade'] as $col) {
                if (Schema::hasColumn('sliders', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
