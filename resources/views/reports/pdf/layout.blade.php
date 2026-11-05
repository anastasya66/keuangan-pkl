<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Laporan FinToko')</title>
    <style>
        @page {
            margin: 28px 32px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #059669;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-table {
            width: 100%;
        }
        .app-name {
            font-size: 20px;
            font-weight: bold;
            color: #059669;
            letter-spacing: -0.5px;
        }
        .app-tagline {
            font-size: 10px;
            color: #64748b;
        }
        .report-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            text-transform: uppercase;
        }
        .report-period {
            font-size: 10px;
            color: #475569;
            text-align: right;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 14px;
        }
        table.data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 7px 8px;
            border: 1px solid #0f172a;
        }
        table.data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
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
            background-color: #f1f5f9 !important;
            font-weight: bold;
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #94a3b8;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <div class="app-name">FinToko</div>
                    <div class="app-tagline">Sistem Manajemen Keuangan & Persediaan Toko</div>
                </td>
                <td style="width: 50%;">
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
                    Dokumen Resmi Toko • Halaman 1
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
