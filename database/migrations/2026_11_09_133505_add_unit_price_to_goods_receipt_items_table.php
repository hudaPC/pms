<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 4)
                ->nullable()
                ->after('purchase_order_item_id');
        });

        // Backfill existing GRN items using their currently linked PO prices.
        DB::table('goods_receipt_items')
            ->join(
                'purchase_order_items',
                'goods_receipt_items.purchase_order_item_id',
                '=',
                'purchase_order_items.id'
            )
            ->update([
                'goods_receipt_items.unit_price' =>
                    DB::raw('purchase_order_items.unit_price'),
            ]);
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
