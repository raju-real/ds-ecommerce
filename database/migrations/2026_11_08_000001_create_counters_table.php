<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gap-free sequences (e.g. order numbers NT-00001, NT-00002, …).
 * A row is locked while the next value is taken, so concurrent orders never share a number.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('counters', function (Blueprint $table) {
            $table->string('name', 50)->primary();
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();
        });

        // Continue after any NT- numbers that already exist (deleted orders included).
        $prefix = config('shop.order_number.prefix').'-';
        $last = DB::table('orders')->where('order_number', 'like', $prefix.'%')->pluck('order_number')
            ->map(fn ($n) => (int) substr($n, strlen($prefix)))->max() ?? 0;

        DB::table('counters')->insert(['name' => 'order', 'value' => $last, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down()
    {
        Schema::dropIfExists('counters');
    }
};
