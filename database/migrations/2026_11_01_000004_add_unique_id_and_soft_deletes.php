<?php

use App\Support\UniqueId;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every application table gets a public unique_id (10 lowercase letters) and
 * soft deletes. Existing rows are back-filled before the column becomes required.
 */
return new class extends Migration
{
    protected const TABLES = [
        'users', 'settings', 'categories', 'subcategories', 'vendors', 'brands', 'products', 'product_images',
        'districts', 'thanas', 'sliders', 'orders', 'order_items', 'order_status_histories',
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('unique_id', UniqueId::LENGTH)->nullable()->after('id');
                $t->softDeletes();
            });

            $used = [];
            DB::table($table)->whereNull('unique_id')->orderBy('id')->pluck('id')->each(function ($id) use ($table, &$used) {
                do {
                    $uid = UniqueId::make();
                } while (isset($used[$uid]));
                $used[$uid] = true;
                DB::table($table)->where('id', $id)->update(['unique_id' => $uid]);
            });

            DB::statement("ALTER TABLE `$table` MODIFY `unique_id` VARCHAR(".UniqueId::LENGTH.') NOT NULL');
            Schema::table($table, fn (Blueprint $t) => $t->unique('unique_id'));
        }

        // A deleted product must not block re-using its code; uniqueness among live rows is checked in validation.
        Schema::table('products', function (Blueprint $t) {
            $t->dropUnique(['code']);
            $t->index('code');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $t) {
            $t->dropIndex(['code']);
            $t->unique('code');
        });

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropUnique(['unique_id']);
                $t->dropColumn('unique_id');
                $t->dropSoftDeletes();
            });
        }
    }
};
