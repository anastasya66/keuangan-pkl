<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Laporan FinToko')</title>
    <style>
        @page {
            margin: 28px 32px 36px 32px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #167A93;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .app-name {
            font-size: 16px;
            font-weight: bold;
            color: #167A93;
            letter-spacing: -0.3px;
        }
        .app-tagline {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            text-transform: uppercase;
        }
        .report-period {
            font-size: 9px;
            color: #475569;
            text-align: right;
            margin-top: 2px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 12px;
        }
        table.data-table th {
            background-color: #16323B;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 6px;
            border: 1px solid #16323B;
        }
        table.data-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            font-size: 9px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .font-bold { font-weight: bold; }
        .total-row td {
            background-color: #eaf8fa !important;
            color: #16323B;
            font-weight: bold;
            border-top: 2px solid #167A93;
            border-bottom: 2px solid #167A93;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <div class="app-name">{{ $settings['store_name'] ?? 'FinToko Minimarket & Retail' }}</div>
                    <div class="app-tagline">
                        {{ $settings['store_address'] ?? 'Sistem Manajemen Keuangan & Persediaan Toko' }}
                        @if(!empty($settings['store_phone']))
                            • Telp: {{ $settings['store_phone'] }}
                        @endif
                    </div>
                </td>
                <td style="width: 45%; vertical-align: top;">
                    <div class="report-title">@yield('report-title')</div>
                    <div class="report-period">@yield('report-period')</div>
                </td>
            </tr>
        </table>
    </div>

    @yield('content')

    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: left; color: #94a3b8; font-size: 8px;">
                    Dicetak secara otomatis oleh sistem FinToko pada {{ now()->translatedFormat('d F Y H:i:s') }}
                </td>
                <td style="text-align: right; color: #94a3b8; font-size: 8px;">
                    Dokumen Resmi Toko
                </td>
            </tr>
        </table>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman " . $PAGE_NUM . " dari " . $PAGE_COUNT;
            $size = 8;
            $font = $fontMetrics->getFont("Helvetica");
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pdf->get_width() - $width - 32;
            $y = $pdf->get_height() - 20;
            $pdf->page_text($x, $y, $text, $font, $size, [0.58, 0.65, 0.72]);
        }
    </script>
</body>
</html>
