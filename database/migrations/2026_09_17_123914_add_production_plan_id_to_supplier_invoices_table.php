<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {

            $table->foreignId('production_plan_id')
                ->nullable()
                ->after('supplier_id')
                ->constrained('production_plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {

            $table->dropForeign([
                'production_plan_id'
            ]);

            $table->dropColumn(
                'production_plan_id'
            );
        });
    }
};