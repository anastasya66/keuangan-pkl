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
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('reference_type', 50)->nullable()->after('reference_number');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->decimal('cost_price', 14, 2)->nullable()->after('quantity');

            $table->index(['reference_type', 'reference_id'], 'inv_mov_ref_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inv_mov_ref_idx');
            $table->dropColumn(['reference_type', 'reference_id', 'cost_price']);
        });
    }
};

