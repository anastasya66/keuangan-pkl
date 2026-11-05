<?php

namespace App\Exports;

use Illuminate\Http\Response;

class StockMovementExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file CSV/Excel.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'riwayat-mutasi-persediaan-' . date('Ymd') . '.csv';

        $output = fopen('php://temp', 'r+');

        // UTF-8 BOM untuk Microsoft Excel
        fputs($output, "\xEF\xBB\xBF");

        // Header Dokumen
        fputcsv($output, ['FINTOKO - SISTEM MANAJEMEN KEUANGAN & PERSEDIAAN TOKO']);
        fputcsv($output, ['LAPORAN RIWAYAT MUTASI PERSEDIAAN (STOCK MOVEMENTS)']);
        fputcsv($output, ['Tanggal Cetak:', $this->data['generated_at']]);
        fputcsv($output, ['Total Catatan Mutasi:', $this->data['summary']['total_movements']]);
        fputcsv($output, ['Total Masuk (+):', $this->data['summary']['total_stock_in']]);
        fputcsv($output, ['Total Keluar (-):', $this->data['summary']['total_stock_out']]);
        fputcsv($output, ['Total Penyesuaian Opname:', $this->data['summary']['total_adjustments']]);
        fputcsv($output, []); // Baris kosong

        // Kolom Tabel
        fputcsv($output, [
            'Kode Mutasi',
            'Tanggal',
            'SKU Produk',
            'Nama Produk',
            'Jenis Mutasi',
            'Jumlah (Qty)',
            'Satuan',
            'Stok Sebelum',
            'Stok Sesudah',
            'No. Referensi',
            'Keterangan / Alasan',
            'Pencatat',
        ]);

        foreach ($this->data['movements'] as $m) {
            fputcsv($output, [
                $m['movement_code'],
                $m['movement_date'],
                $m['product_sku'],
                $m['product_name'],
                $m['movement_type_label'],
                $m['quantity'],
                $m['unit_symbol'],
                $m['stock_before'],
                $m['stock_after'],
                $m['reference_number'] ?: '-',
                $m['description'] ?: '-',
                $m['creator_name'],
            ]);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
