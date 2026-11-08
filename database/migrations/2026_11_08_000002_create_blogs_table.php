<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 10)->unique();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->longText('description');           // sanitised HTML from the editor
            $table->string('excerpt', 300)->nullable(); // plain-text summary, generated on save
            $table->string('image')->nullable();        // 1200×630 cover (also the social share image)
            $table->string('thumb')->nullable();        // 600×315 card image
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable()->index();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('blogs');
    }
};
