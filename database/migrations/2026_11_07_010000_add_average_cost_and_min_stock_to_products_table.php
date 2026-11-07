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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('average_cost', 14, 2)->default(0.00)->after('selling_price');
            $table->decimal('min_stock', 12, 2)->default(5.00)->after('stock');
            $table->index('min_stock', 'products_min_stock_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_min_stock_idx');
            $table->dropColumn(['average_cost', 'min_stock']);
        });
    }
};

