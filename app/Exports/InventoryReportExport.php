<?php

namespace App\Exports;

use Illuminate\Http\Response;

class InventoryReportExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file CSV/Excel.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-stok-persediaan-' . date('Ymd') . '.csv';

        $output = fopen('php://temp', 'r+');

        // UTF-8 BOM untuk Microsoft Excel
        fputs($output, "\xEF\xBB\xBF");

        // Header Dokumen
        fputcsv($output, ['FINTOKO - SISTEM MANAJEMEN KEUANGAN & PERSEDIAAN TOKO']);
        fputcsv($output, ['LAPORAN STOK & VALUASI PERSEDIAAN']);
        fputcsv($output, ['Tanggal Cetak:', $this->data['generated_at']]);
        fputcsv($output, ['Total Item Produk:', $this->data['summary']['total_products']]);
        fputcsv($output, ['Total Unit Fisik:', $this->data['summary']['total_stock_units']]);
        fputcsv($output, ['Total Nilai Aset Persediaan:', $this->data['summary']['total_inventory_value']]);
        fputcsv($output, []); // Baris kosong

        // Kolom Tabel
        fputcsv($output, [
            'SKU',
            'Barcode',
            'Nama Produk',
            'Kategori',
            'Satuan',
            'Stok Fisik',
            'Status Stok',
            'Harga Beli (Rp)',
            'Harga Jual (Rp)',
            'Nilai Persediaan (Rp)',
        ]);

        foreach ($this->data['products'] as $p) {
            fputcsv($output, [
                $p['sku'],
                $p['barcode'] ?: '-',
                $p['name'],
                $p['category_name'],
                $p['unit_symbol'],
                $p['stock'],
                $p['stock_status_label'],
                $p['purchase_price'],
                $p['selling_price'],
                $p['inventory_value'],
            ]);
        }

        // Baris Grand Total
        fputcsv($output, []);
        fputcsv($output, [
            'GRAND TOTAL',
            '',
            '',
            '',
            '',
            $this->data['summary']['total_stock_units'],
            '',
            '',
            '',
            $this->data['summary']['total_inventory_value'],
        ]);

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
