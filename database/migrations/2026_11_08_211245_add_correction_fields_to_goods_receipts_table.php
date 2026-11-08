
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
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('corrected_from_grn_id')
                ->nullable()
                ->after('void_reason');

            $table->text('correction_reason')
                ->nullable()
                ->after('corrected_from_grn_id');

            $table->timestamp('corrected_at')
                ->nullable()
                ->after('correction_reason');

            $table->foreign('corrected_from_grn_id')
                ->references('id')
                ->on('goods_receipts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropForeign(['corrected_from_grn_id']);

            $table->dropColumn([
                'corrected_from_grn_id',
                'correction_reason',
                'corrected_at',
            ]);
        });
    }
};
