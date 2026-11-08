<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 10)->unique();
            $table->string('code', 30)->index();                // stored upper-case; unique among non-deleted coupons (validation)
            $table->string('type', 20);                          // new_user | fixed_user | all_users
            $table->string('discount_type', 10);                 // fixed | percent
            $table->decimal('value', 12, 2);                     // ৳ amount or %
            $table->decimal('max_discount', 12, 2)->nullable();  // cap for percent coupons
            $table->decimal('min_order', 12, 2)->nullable();     // minimum subtotal
            $table->json('mobiles')->nullable();                 // fixed_user: allowed mobile numbers
            $table->unsignedInteger('usage_limit')->nullable();  // total uses (null = unlimited)
            $table->unsignedInteger('per_mobile_limit')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('discount')->constrained()->nullOnDelete();
            $table->string('coupon_code', 30)->nullable()->after('coupon_id');
            $table->decimal('coupon_discount', 12, 2)->default(0)->after('coupon_code');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'coupon_discount']);
        });
        Schema::dropIfExists('coupons');
    }
};
