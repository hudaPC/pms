<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            
            /*
            |--------------------------------------------------------------------------
            | Purchase Order Source
            |--------------------------------------------------------------------------
            |
            | Examples:
            |
            | manual
            | purchase-planning
            | production-plan
            |
            */

            $table->string('source', 50)
                ->nullable()
                ->after('supplier_id');


            /*
            |--------------------------------------------------------------------------
            | Production Plan Reference
            |--------------------------------------------------------------------------
            */

            $table->foreignId('production_plan_id')
                ->nullable()
                ->after('source')
                ->constrained('production_plans')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Lookup Index
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'production_plan_id',
                    'supplier_id',
                    'status',
                ],
                'po_production_plan_supplier_status_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {

            $table->dropIndex(
                'po_production_plan_supplier_status_idx'
            );

            $table->dropForeign([
                'production_plan_id'
            ]);

            $table->dropColumn([
                'production_plan_id',
                'source',
            ]);
        });
    }
};
