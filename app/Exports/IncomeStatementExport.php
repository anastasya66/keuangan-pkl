<?php

namespace App\Exports;

use Illuminate\Http\Response;

class IncomeStatementExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file CSV/Excel.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-laba-rugi-' . $this->data['period']['start_date'] . '-sd-' . $this->data['period']['end_date'] . '.csv';

        $output = fopen('php://temp', 'r+');

        // UTF-8 BOM untuk Microsoft Excel
        fputs($output, "\xEF\xBB\xBF");

        // Header Dokumen
        fputcsv($output, ['FINTOKO - SISTEM MANAJEMEN KEUANGAN & PERSEDIAAN TOKO']);
        fputcsv($output, ['LAPORAN LABA RUGI (INCOME STATEMENT)']);
        fputcsv($output, ['Periode:', $this->data['period']['formatted']]);
        fputcsv($output, ['Tanggal Cetak:', $this->data['generated_at']]);
        fputcsv($output, []); // Baris kosong

        // 1. PENDAPATAN
        fputcsv($output, ['1. PENDAPATAN (REVENUE)']);
        fputcsv($output, ['Kode Akun', 'Nama Akun Pendapatan', 'Nominal (Rp)']);

        foreach ($this->data['revenues']['items'] as $item) {
            fputcsv($output, [$item['account_code'], $item['account_name'], $item['amount']]);
        }
        fputcsv($output, ['TOTAL PENDAPATAN', '', $this->data['revenues']['total']]);
        fputcsv($output, []);

        // 2. BEBAN
        fputcsv($output, ['2. BEBAN OPERASIONAL (EXPENSES)']);
        fputcsv($output, ['Kode Akun', 'Nama Akun Beban', 'Nominal (Rp)']);

        foreach ($this->data['expenses']['items'] as $item) {
            fputcsv($output, [$item['account_code'], $item['account_name'], $item['amount']]);
        }
        fputcsv($output, ['TOTAL BEBAN', '', $this->data['expenses']['total']]);
        fputcsv($output, []);

        // 3. LABA / RUGI BERSIH
        $status = $this->data['is_profit'] ? 'LABA BERSIH (NET PROFIT)' : 'RUGI BERSIH (NET LOSS)';
        fputcsv($output, [$status, '', $this->data['net_profit']]);

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
