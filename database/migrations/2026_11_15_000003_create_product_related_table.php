<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Related products chosen by the admin on the product form ("You may need" on checkout).
 * product_id → related_id, in the admin's order. Rows go away with either product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_related');
    }
};
