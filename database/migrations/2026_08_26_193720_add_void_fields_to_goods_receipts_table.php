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

            $table->string('status', 20)
                ->default('posted')
                ->after('remarks');

            $table->timestamp('voided_at')
                ->nullable()
                ->after('status');

            $table->text('void_reason')
                ->nullable()
                ->after('voided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('goods_receipts', function (Blueprint $table) {

            $table->dropColumn([
                'status',
                'voided_at',
                'void_reason',
            ]);
        });
    }
};
