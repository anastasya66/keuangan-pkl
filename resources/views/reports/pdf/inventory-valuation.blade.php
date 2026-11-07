@extends('reports.pdf.layout')

@section('title', 'Laporan Nilai Persediaan — FinToko')
@section('report-title', 'Laporan Nilai Persediaan (Moving Weighted Average)')
@section('report-period', 'Posisi Valuasi per: ' . now()->translatedFormat('d F Y H:i'))

@section('content')
    <div style="margin-bottom: 12px; padding: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
        <table style="width: 100%; font-size: 10px;">
            <tr>
                <td style="width: 25%;"><strong>Total Item SKU:</strong> {{ $data['summary']['total_products'] }}</td>
                <td style="width: 25%;"><strong>Total Kuantitas Fisik:</strong> {{ number_format($data['summary']['total_stock_units'], 2, ',', '.') }} unit</td>
                <td style="width: 25%;"><strong>Potensi Omzet:</strong> Rp {{ number_format($data['summary']['potential_revenue'], 2, ',', '.') }}</td>
                <td style="width: 25%; text-align: right;"><strong>Total Nilai Persediaan (HPP):</strong> <span style="color: #059669; font-weight: bold;">Rp {{ number_format($data['summary']['total_valuation'], 2, ',', '.') }}</span></td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;" class="text-left">Kode SKU</th>
                <th style="width: 28%;" class="text-left">Nama Produk</th>
                <th style="width: 14%;" class="text-left">Kategori</th>
                <th style="width: 10%;" class="text-right">Stok Fisik</th>
                <th style="width: 12%;" class="text-right">HPP Rata-rata</th>
                <th style="width: 12%;" class="text-right">Harga Jual</th>
                <th style="width: 12%;" class="text-right">Total Nilai HPP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['products'] as $p)
                @php
                    $stock = max(0.0, (float) $p->stock);
                    $avgCost = (float) $p->effective_cost;
                    $itemVal = round($stock * $avgCost, 2);
                @endphp
                <tr>
                    <td class="font-mono">{{ $p->sku }}</td>
                    <td>
                        <strong>{{ $p->name }}</strong>
                    </td>
                    <td>{{ $p->category?->name ?? '-' }}</td>
                    <td class="text-right font-mono">{{ number_format($stock, 2, ',', '.') }} {{ $p->unit?->symbol ?? 'pcs' }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($avgCost, 2, ',', '.') }}</td>
                    <td class="text-right font-mono">Rp {{ number_format((float) $p->selling_price, 2, ',', '.') }}</td>
                    <td class="text-right font-mono font-bold" style="color: #0f766e;">Rp {{ number_format($itemVal, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="color: #94a3b8;">Tidak ada data produk persediaan</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL KESELURUHAN VALUASI</td>
                <td class="text-right font-mono">{{ number_format($data['summary']['total_stock_units'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono" style="color: #059669;">Rp {{ number_format($data['summary']['total_valuation'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
@endsection
