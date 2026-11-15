<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer ratings & reviews (product page). Only for a delivered order that contains the product, one review per order.
 * Shown right away (is_active = true); the admin can hide or delete one. Products keep rating_avg / rating_count
 * up to date for the stars on product cards (no extra query per card).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 10)->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('mobile', 11);
            $table->unsignedTinyInteger('rating');          // 1–5
            $table->text('review');
            $table->json('images')->nullable();             // up to 3 paths
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('order_id');                     // one review per order (also after a soft delete)
            $table->index(['product_id', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'rating_avg')) {
                $table->decimal('rating_avg', 3, 2)->default(0)->after('in_stock');
                $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'rating_avg')) {
                $table->dropColumn(['rating_avg', 'rating_count']);
            }
        });
    }
};
