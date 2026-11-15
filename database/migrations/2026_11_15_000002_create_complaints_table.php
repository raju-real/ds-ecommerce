<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customer complaints (the "Complaint" link in the shop menu). Each one gets a number 00001, 00002 … from the
 * gap-free counter (counters.name = complaint) and a status the admin moves along: received → on_review → solved / rejected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 10)->unique();
            $table->string('complaint_no', 12)->unique();   // 00001
            $table->string('name', 80);
            $table->string('mobile', 11)->index();
            $table->text('message');
            $table->json('images')->nullable();             // up to 2 paths
            $table->string('status', 20)->default('received')->index();
            $table->text('admin_note')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('counters')->insertOrIgnore(['name' => 'complaint', 'value' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        DB::table('counters')->where('name', 'complaint')->delete();
    }
};
