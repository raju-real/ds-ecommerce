<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('address', 500)->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 60)->unique();
            $table->string('slug')->unique();
            $table->string('short_description', 1000)->nullable();
            $table->longText('description')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->decimal('drop_shipping_price', 12, 2)->default(0); // admin only
            $table->string('vendor_product_url')->nullable(); // admin only
            $table->string('thumbnail')->nullable();
            $table->boolean('in_stock')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'created_at']);
            $table->index(['is_active', 'is_featured']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumb');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->decimal('delivery_charge', 10, 2)->default(130);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('thanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('button_text', 60)->nullable();
            $table->string('link')->nullable();
            $table->string('image');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->nullable()->unique();
            $table->string('customer_name');
            $table->string('mobile', 20)->index();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('thana_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address', 500);
            $table->string('note', 1000)->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('cost_total', 12, 2)->default(0); // sum of drop shipping prices
            $table->decimal('courier_cost', 10, 2)->default(0); // what we actually pay the courier
            $table->string('payment_method', 30)->default('cod');

            $table->string('status', 30)->default('pending')->index();

            $table->boolean('otp_verified')->default(false)->index();
            $table->timestamp('otp_verified_at')->nullable();
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->unsignedTinyInteger('otp_resends')->default(0);
            $table->timestamp('otp_sent_at')->nullable();

            // Forwarding to vendor / courier
            $table->string('vendor_order_ref')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('tracking_code')->nullable();
            $table->timestamp('forwarded_at')->nullable();

            $table->text('admin_note')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_code', 60)->nullable();
            $table->string('thumbnail')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('drop_shipping_price', 12, 2)->default(0);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->decimal('line_cost', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30);
            $table->string('note', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        foreach (['order_status_histories', 'order_items', 'orders', 'sliders', 'thanas', 'districts',
            'product_images', 'products', 'vendors', 'subcategories', 'categories', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
