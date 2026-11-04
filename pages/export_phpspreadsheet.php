<?php
/**
 * =====================================================================
 * FINTOKO — EKSPOR EXCEL MENGGUNAKAN LIBRARY PHPSPREADSHEET
 * =====================================================================
 * Versi    : Menggunakan Composer PhpSpreadsheet (\PhpOffice\PhpSpreadsheet)
 * Fitur    : 1. Perhitungan Otomatis Lebar Kolom Berdasarkan Karakter Terpanjang
 *            2. Padding Ekstra (+4 Karakter) Bebas Tanda ###
 *            3. Styling Header Icy Azure & Format Angka Moneter #,##0
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_admin('../');

// Pastikan autoloader Composer tersedia jika menggunakan PhpSpreadsheet
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // Jika PhpSpreadsheet belum diinstal, fallback otomatis ke fungsi native
    $rows_exp = db()->query("
        SELECT tanggal, jenis, kategori, keterangan, metode, nominal, input_oleh
        FROM transaksi ORDER BY tanggal DESC, id DESC
    ")->fetch_all(MYSQLI_ASSOC);

    $headers = ['No', 'Tanggal Transaksi', 'Jenis Kas', 'Kategori', 'Keterangan', 'Metode', 'Nominal (Rp)', 'Dicatat Oleh'];
    $data = [];
    $no = 1;
    foreach ($rows_exp as $r) {
        $data[] = [
            $no++,
            tanggal_id($r['tanggal']),
            $r['jenis'],
            $r['kategori'] ?: 'Umum',
            $r['keterangan'] ?: '—',
            $r['metode'] ?? 'Tunai',
            (float)$r['nominal'],
            $r['input_oleh'] ?? '—'
        ];
    }
    export_excel_auto_fit('buku_kas_fintoko', $headers, $data, [0 => 'number', 6 => 'number'], 'Buku Kas');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// 1. Ambil Data dari Database
$rows = db()->query("
    SELECT tanggal, jenis, kategori, keterangan, metode, nominal, input_oleh
    FROM transaksi ORDER BY tanggal DESC, id DESC
")->fetch_all(MYSQLI_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Buku Kas FinToko');

// 2. Tulis Judul & Header Laporan
$sheet->setCellValue('A1', 'FINTOKO — LAPORAN TRANSAKSI BUKU KAS');
$sheet->setCellValue('A2', 'Instansi: ' . NAMA_INSTANSI . ' | Tanggal Unduh: ' . date('d/m/Y H:i'));

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
$sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

$headers = [
    'A' => 'No',
    'B' => 'Tanggal Transaksi',
    'C' => 'Jenis Kas',
    'D' => 'Kategori',
    'E' => 'Keterangan Transaksi',
    'F' => 'Metode Pembayaran',
    'G' => 'Nominal (Rp)',
    'H' => 'Dicatat Oleh'
];

$headerRow = 4;
foreach ($headers as $col => $title) {
    $sheet->setCellValue($col . $headerRow, $title);
}

// Styling Header (Icy Azure Theme #0EA5E9)
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11,
        'name' => 'Calibri'
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '0EA5E9']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '0284C7']
        ]
    ]
];
$sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->applyFromArray($headerStyle);
$sheet->getRowDimension($headerRow)->setRowHeight(26);

// 3. Tulis Isi Data
$dataStartRow = 5;
$currentRow = $dataStartRow;
$no = 1;

foreach ($rows as $r) {
    $sheet->setCellValue('A' . $currentRow, $no++);
    $sheet->setCellValue('B' . $currentRow, tanggal_id($r['tanggal']));
    $sheet->setCellValue('C' . $currentRow, $r['jenis']);
    $sheet->setCellValue('D' . $currentRow, $r['kategori'] ?: 'Umum');
    $sheet->setCellValue('E' . $currentRow, $r['keterangan'] ?: '—');
    $sheet->setCellValue('F' . $currentRow, $r['metode'] ?? 'Tunai');
    $sheet->setCellValue('G' . $currentRow, (float)$r['nominal']);
    $sheet->setCellValue('H' . $currentRow, $r['input_oleh'] ?? '—');

    // Format Angka Ribuan Kolom Nominal
    $sheet->getStyle('G' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('G' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Zebra Striping Halus
    if ($currentRow % 2 === 0) {
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F8FAFC');
    }

    $sheet->getRowDimension($currentRow)->setRowHeight(20);
    $currentRow++;
}

$lastRow = $currentRow - 1;

// Border halus seluruh tabel data
$sheet->getStyle('A' . $dataStartRow . ':H' . $lastRow)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN)
    ->getColor()->setRGB('E2E8F0');

// =====================================================================
// 4. LOGIKA PERHITUNGAN LEBAR KOLOM OTOMATIS (AUTO-FIT BERBASIS KARAKTER + PADDING)
// =====================================================================
$padding = 4; // Tambahan padding 4 karakter agar longgar & tidak mepet

foreach (array_keys($headers) as $col) {
    // A. Ambil panjang teks header
    $headerText = (string)$sheet->getCell($col . $headerRow)->getValue();
    $maxLen = mb_strlen($headerText, 'UTF-8');

    // B. Bandingkan dengan seluruh karakter isi sel pada kolom ini
    for ($row = $dataStartRow; $row <= $lastRow; $row++) {
        $cell = $sheet->getCell($col . $row);
        $val = $cell->getValue();

        if ($col === 'G') {
            // Kolom nominal diformat ribuan untuk estimasi panjang tampilan Excel
            $displayStr = 'Rp ' . number_format((float)$val, 0, ',', '.');
            $cellLen = mb_strlen($displayStr, 'UTF-8');
        } else {
            $cellLen = mb_strlen((string)$val, 'UTF-8');
        }

        if ($cellLen > $maxLen) {
            $maxLen = $cellLen;
        }
    }

    // C. Terapkan lebar kolom: Karakter Terpanjang + Padding (Minimum 12)
    $finalWidth = max($maxLen + $padding, 12);
    $sheet->getColumnDimension($col)->setWidth($finalWidth);
}

// 5. Unduh File XLSX
while (ob_get_level() > 0) {
    ob_end_clean();
}

$filename = 'buku_kas_fintoko_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

