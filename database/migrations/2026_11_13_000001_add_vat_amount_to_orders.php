<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VAT paid by the shop on an order (an amount, not a percentage), entered on the order page like the courier cost.
 * Not charged to the customer: it is deducted from profit (profit = total - vendor cost - courier cost - VAT).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'vat_amount')) {
            Schema::table('orders', function (Blueprint $t) {
                $t->decimal('vat_amount', 10, 2)->default(0)->after('courier_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'vat_amount')) {
            Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('vat_amount'));
        }
    }
};
