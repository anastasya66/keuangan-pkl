<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Catat mutasi stok masuk (Stock In) secara atomik dengan concurrency lock.
     */
    public function recordStockIn(array $data, int $userId): InventoryMovement
    {
        return DB::transaction(function () use ($data, $userId) {
            /** @var Product $product */
            $product = Product::where('id', $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk ini berstatus nonaktif dan tidak dapat menerima mutasi stok.'],
                ]);
            }

            $quantity = (float) $data['quantity'];
            $stockBefore = (float) $product->stock;
            $stockAfter = $stockBefore + $quantity;

            // Update stok produk
            $product->stock = $stockAfter;
            $product->save();

            // Buat catatan mutasi stok
            $movement = InventoryMovement::create([
                'movement_code' => InventoryMovement::generateMovementCode($data['movement_date'] ?? null),
                'product_id' => $product->id,
                'movement_type' => 'stock_in',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'description' => $data['description'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'movement_date' => $data['movement_date'],
                'created_by' => $userId,
            ]);

            return $movement->load(['product.unit', 'creator']);
        });
    }

    /**
     * Catat mutasi stok keluar (Stock Out) secara atomik dengan proteksi stok negatif.
     */
    public function recordStockOut(array $data, int $userId): InventoryMovement
    {
        return DB::transaction(function () use ($data, $userId) {
            /** @var Product $product */
            $product = Product::where('id', $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk ini berstatus nonaktif dan tidak dapat memproses mutasi stok.'],
                ]);
            }

            $quantity = (float) $data['quantity'];
            $stockBefore = (float) $product->stock;

            // Validasi kecukupan stok
            if ($stockBefore < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        "Jumlah stok keluar ({$quantity}) melebihi stok yang tersedia saat ini ({$stockBefore}).",
                    ],
                ]);
            }

            $stockAfter = $stockBefore - $quantity;

            // Update stok produk
            $product->stock = $stockAfter;
            $product->save();

            // Buat catatan mutasi stok
            $movement = InventoryMovement::create([
                'movement_code' => InventoryMovement::generateMovementCode($data['movement_date'] ?? null),
                'product_id' => $product->id,
                'movement_type' => 'stock_out',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'description' => $data['description'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'movement_date' => $data['movement_date'],
                'created_by' => $userId,
            ]);

            return $movement->load(['product.unit', 'creator']);
        });
    }

    /**
     * Catat penyesuaian stok (Stock Adjustment) berdasarkan pemeriksaan fisik.
     */
    public function recordAdjustment(array $data, int $userId): InventoryMovement
    {
        return DB::transaction(function () use ($data, $userId) {
            /** @var Product $product */
            $product = Product::where('id', $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk ini berstatus nonaktif dan tidak dapat disesuaikan stoknya.'],
                ]);
            }

            $stockBefore = (float) $product->stock;
            $actualStock = (float) $data['actual_stock'];

            // Cek apakah ada perubahan
            if ($actualStock === $stockBefore) {
                throw ValidationException::withMessages([
                    'actual_stock' => [
                        'Stok fisik hasil pemeriksaan sama dengan stok sistem saat ini. Tidak ada penyesuaian yang perlu disimpan.',
                    ],
                ]);
            }

            if ($actualStock > $stockBefore) {
                $quantity = $actualStock - $stockBefore;
                $movementType = 'adjustment_in';
            } else {
                $quantity = $stockBefore - $actualStock;
                $movementType = 'adjustment_out';
            }

            $stockAfter = $actualStock;

            // Update stok produk ke nilai aktual
            $product->stock = $stockAfter;
            $product->save();

            // Buat catatan mutasi penyesuaian
            $movement = InventoryMovement::create([
                'movement_code' => InventoryMovement::generateMovementCode($data['movement_date'] ?? null),
                'product_id' => $product->id,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'adjustment_reason' => $data['adjustment_reason'],
                'description' => $data['description'] ?? null,
                'movement_date' => $data['movement_date'],
                'created_by' => $userId,
            ]);

            return $movement->load(['product.unit', 'creator']);
        });
    }
}
