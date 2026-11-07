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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('cost_price', 14, 2)->default(0.00); // Snapshot HPP rata-rata saat penjualan
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('subtotal', 14, 2);
            $table->decimal('cogs_total', 14, 2)->default(0.00);
            $table->timestamps();

            $table->index('sale_id', 'sale_items_sale_idx');
            $table->index('product_id', 'sale_items_product_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
