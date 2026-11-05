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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_code', 50)->unique();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('movement_type', 20); // 'stock_in', 'stock_out', 'adjustment_in', 'adjustment_out'
            $table->decimal('quantity', 12, 2);
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->text('adjustment_reason')->nullable();
            $table->text('description')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->date('movement_date');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('product_id', 'inv_mov_prod_idx');
            $table->index('movement_type', 'inv_mov_type_idx');
            $table->index('movement_date', 'inv_mov_date_idx');
            $table->index('created_by', 'inv_mov_user_idx');
            $table->index(['product_id', 'movement_date'], 'inv_mov_prod_date_idx');
            $table->index(['movement_type', 'movement_date'], 'inv_mov_type_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};

