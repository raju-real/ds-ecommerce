<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pages built in the admin (Website → Pages): About us, Privacy policy, Return policy, Terms …
 * Shown at /page/{slug} and linked from the footer, in the order set on the Pages list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 10)->unique();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->longText('content');                // sanitised HTML from the editor
            $table->string('image')->nullable();        // optional banner, 1200×630 (also the share image)
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->string('meta_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
