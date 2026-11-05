<?php

namespace App\Exports;

use Illuminate\Http\Response;

class BalanceSheetExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file CSV/Excel.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-neraca-per-' . $this->data['as_of_date'] . '.csv';

        $output = fopen('php://temp', 'r+');

        // UTF-8 BOM untuk Microsoft Excel
        fputs($output, "\xEF\xBB\xBF");

        // Header Dokumen
        fputcsv($output, ['FINTOKO - SISTEM MANAJEMEN KEUANGAN & PERSEDIAAN TOKO']);
        fputcsv($output, ['LAPORAN NERACA (BALANCE SHEET)']);
        fputcsv($output, ['Posisi Keuangan per:', $this->data['formatted_as_of']]);
        fputcsv($output, ['Status Keseimbangan:', $this->data['is_balanced'] ? 'SEIMBANG (EQUILIBRIUM)' : 'TIDAK SEIMBANG']);
        fputcsv($output, ['Tanggal Cetak:', $this->data['generated_at']]);
        fputcsv($output, []); // Baris kosong

        // 1. ASET
        fputcsv($output, ['1. ASET (ASSETS)']);
        fputcsv($output, ['Kode Akun', 'Nama Akun Aset', 'Saldo (Rp)']);

        foreach ($this->data['assets']['items'] as $item) {
            fputcsv($output, [$item['account_code'], $item['account_name'], $item['amount']]);
        }
        fputcsv($output, ['TOTAL ASET', '', $this->data['total_assets']]);
        fputcsv($output, []);

        // 2. KEWAJIBAN
        fputcsv($output, ['2. KEWAJIBAN (LIABILITIES)']);
        fputcsv($output, ['Kode Akun', 'Nama Akun Kewajiban', 'Saldo (Rp)']);

        foreach ($this->data['liabilities']['items'] as $item) {
            fputcsv($output, [$item['account_code'], $item['account_name'], $item['amount']]);
        }
        fputcsv($output, ['TOTAL KEWAJIBAN', '', $this->data['total_liabilities']]);
        fputcsv($output, []);

        // 3. EKUITAS
        fputcsv($output, ['3. EKUITAS / MODAL (EQUITY)']);
        fputcsv($output, ['Kode Akun', 'Komponen Ekuitas', 'Saldo (Rp)']);

        foreach ($this->data['equity']['items'] as $item) {
            fputcsv($output, [$item['account_code'], $item['account_name'], $item['amount']]);
        }
        fputcsv($output, ['-', 'Laba / (Rugi) Berjalan Kumulatif', $this->data['equity']['current_earnings']]);
        fputcsv($output, ['TOTAL EKUITAS', '', $this->data['total_equity']]);
        fputcsv($output, []);

        // TOTAL KEWAJIBAN & EKUITAS
        fputcsv($output, ['TOTAL KEWAJIBAN DAN EKUITAS', '', $this->data['total_liabilities_and_equity']]);
        fputcsv($output, ['SELISIH KESEIMBANGAN', '', $this->data['difference']]);

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
