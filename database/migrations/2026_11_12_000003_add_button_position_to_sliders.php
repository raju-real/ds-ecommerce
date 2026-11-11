<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Per slide: where the banner button sits (below_text | bottom_left | bottom_center | bottom_right). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sliders', 'button_position')) {
            Schema::table('sliders', function (Blueprint $t) {
                $t->string('button_position', 15)->default('bottom_left')->after('button_text');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sliders', 'button_position')) {
            Schema::table('sliders', fn (Blueprint $t) => $t->dropColumn('button_position'));
        }
    }
};
