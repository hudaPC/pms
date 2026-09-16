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
        Schema::table('usage_logs', function (Blueprint $table) {

            $table->string('source', 50)
                ->nullable()
                ->after('item_id');

            $table->foreignId('production_plan_id')
                ->nullable()
                ->after('source')
                ->constrained('production_plans')
                ->nullOnDelete();

            $table->index(
                [
                    'production_plan_id',
                    'item_id',
                    'status',
                ],
                'usage_production_plan_item_status_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('usage_logs', function (Blueprint $table) {

            $table->dropIndex(
                'usage_production_plan_item_status_idx'
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
