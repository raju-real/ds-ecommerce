<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Short links: /s/{code} → product page, direct order or blog post. The target is stored by id
        // and resolved on every click, so links survive slug and domain changes.
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 10)->unique();
            $table->string('code', 12)->unique();
            $table->string('type', 20);                     // product | buy | blog
            $table->unsignedBigInteger('target_id');
            $table->unsignedInteger('clicks')->default(0);  // people only — link-preview bots are not counted
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['type', 'target_id']);          // one short link per target, reused everywhere
        });

        // Admin notifications (new orders), with seen/unseen.
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 10)->unique();
            $table->string('type', 30)->default('order');
            $table->string('title', 150);
            $table->string('body', 255)->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('short_links');
    }
};
