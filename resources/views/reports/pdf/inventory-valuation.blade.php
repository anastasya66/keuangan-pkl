@extends('reports.pdf.layout')

@section('title', 'Laporan Valuasi Persediaan — FinToko')
@section('report-title', 'Laporan Valuasi Persediaan')
@section('report-period', 'Tanggal Cetak: ' . now()->translatedFormat('d F Y H:i'))

@section('content')
    <style>
        .summary-card {
            margin-bottom: 12px;
            border: 1px solid #D7EBEF;
            border-radius: 6px;
            background-color: #F8FDFE;
            padding: 8px 10px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 2px 6px;
            border: none;
            vertical-align: top;
        }
        .summary-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #577782;
            letter-spacing: 0.3px;
        }
        .summary-value {
            font-size: 11px;
            font-weight: bold;
            color: #16323B;
            margin-top: 1px;
        }
        .summary-value-teal {
            color: #167A93;
        }
        table.valuation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-top: 6px;
        }
        table.valuation-table th {
            background-color: #16323B;
            color: #FFFFFF;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 5px;
            border: 1px solid #10262E;
            font-size: 8px;
            letter-spacing: 0.2px;
        }
        table.valuation-table td {
            padding: 5px 5px;
            border: 1px solid #D7EBEF;
            vertical-align: middle;
        }
        table.valuation-table tr.even-row td {
            background-color: #F8FDFE;
        }
        table.valuation-table tr.total-row td {
            background-color: #EAF8FA !important;
            color: #16323B;
            font-weight: bold;
            border-top: 2px solid #167A93;
            border-bottom: 2px solid #167A93;
            font-size: 9px;
        }
        .badge-stock {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
        }
        .badge-out { background-color: #FEE2E2; color: #991B1B; }
        .badge-low { background-color: #FEF3C7; color: #92400E; }
    </style>

    {{-- Kotak Ringkasan Utama (4 Kartu Ringkas) --}}
    <div class="summary-card">
        <table class="summary-table">
            <tr>
                <td style="width: 25%;">
                    <div class="summary-label">Total Item Produk</div>
                    <div class="summary-value">{{ $data['summary']['total_products'] }} <span style="font-size: 9px; font-weight: normal; color: #577782;">jenis</span></div>
                </td>
                <td style="width: 25%;">
                    <div class="summary-label">Total Stok Fisik</div>
                    <div class="summary-value">{{ number_format($data['summary']['total_stock_units'], 0, ',', '.') }} <span style="font-size: 9px; font-weight: normal; color: #577782;">unit</span></div>
                </td>
                <td style="width: 25%;">
                    <div class="summary-label">Total Nilai Aset (HPP)</div>
                    <div class="summary-value summary-value-teal">Rp {{ number_format($data['summary']['total_valuation'], 0, ',', '.') }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="summary-label">Potensi Omzet</div>
                    <div class="summary-value">Rp {{ number_format($data['summary']['potential_revenue'], 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Tabel Rincian Nilai Persediaan --}}
    <table class="valuation-table">
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">No</th>
                <th style="width: 13%; text-align: left;">Kode SKU</th>
                <th style="width: 27%; text-align: left;">Nama Produk</th>
                <th style="width: 14%; text-align: left;">Kategori</th>
                <th style="width: 9%; text-align: right;">Stok Fisik</th>
                <th style="width: 11%; text-align: right;">HPP Rata-rata</th>
                <th style="width: 10%; text-align: right;">Harga Jual</th>
                <th style="width: 12%; text-align: right;">Total Nilai Aset</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['products'] as $idx => $p)
                @php
                    $sku = is_array($p) ? ($p['sku'] ?? '') : $p->sku;
                    $name = is_array($p) ? ($p['name'] ?? '') : $p->name;
                    $cat = is_array($p) ? ($p['category'] ?? $p['category_name'] ?? '-') : ($p->category?->name ?? '-');
                    $unit = is_array($p) ? ($p['unit'] ?? $p['unit_symbol'] ?? 'unit') : ($p->unit?->symbol ?? 'unit');
                    $stock = max(0.0, (float) (is_array($p) ? ($p['stock'] ?? 0) : $p->stock));
                    $avgCost = (float) (is_array($p) ? ($p['average_cost'] ?? $p['purchase_price'] ?? 0) : ($p->effective_cost ?? $p->average_cost ?? $p->purchase_price ?? 0));
                    $sellingPrice = (float) (is_array($p) ? ($p['selling_price'] ?? 0) : $p->selling_price);
                    $itemVal = round($stock * $avgCost, 2);
                    $minStock = (float) (is_array($p) ? ($p['min_stock'] ?? 5) : ($p->min_stock ?? 5));
                    $isLow = $stock > 0 && $stock <= $minStock;
                    $isOut = $stock <= 0;
                @endphp
                <tr class="{{ $loop->even ? 'even-row' : '' }}">
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td class="font-mono">{{ $sku }}</td>
                    <td>
                        <strong>{{ $name }}</strong>
                        @if($isOut)
                            <span class="badge-stock badge-out">Habis</span>
                        @elseif($isLow)
                            <span class="badge-stock badge-low">Menipis</span>
                        @endif
                    </td>
                    <td>{{ $cat }}</td>
                    <td style="text-align: right;" class="font-mono">
                        {{ number_format($stock, 0, ',', '.') }} {{ $unit }}
                    </td>
                    <td style="text-align: right;" class="font-mono">
                        Rp {{ number_format($avgCost, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right;" class="font-mono">
                        Rp {{ number_format($sellingPrice, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; color: #167A93; font-weight: bold;" class="font-mono">
                        Rp {{ number_format($itemVal, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 12px;">Tidak ada data produk persediaan.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL KESELURUHAN</td>
                <td style="text-align: right;" class="font-mono">{{ number_format($data['summary']['total_stock_units'], 0, ',', '.') }}</td>
                <td style="text-align: center;" class="font-mono">-</td>
                <td style="text-align: right;" class="font-mono">Rp {{ number_format($data['summary']['potential_revenue'], 0, ',', '.') }}</td>
                <td style="text-align: right; color: #167A93; font-weight: bold;" class="font-mono">
                    Rp {{ number_format($data['summary']['total_valuation'], 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
@endsection
