@extends('reports.pdf.layout')

@section('title', 'Laporan Sisa Stok Persediaan — FinToko')
@section('report-title', 'Laporan Sisa Stok Persediaan (Inventory Report)')
@section('report-period', 'Posisi Stok per: ' . now()->translatedFormat('d F Y'))

@section('content')
    <div style="margin-bottom: 12px; padding: 8px 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
        <table style="width: 100%; font-size: 9px;">
            <tr>
                <td style="width: 25%;"><strong>Total Item:</strong> {{ $data['summary']['total_products'] }} jenis</td>
                <td style="width: 25%;"><strong>Total Sisa Unit:</strong> {{ number_format($data['summary']['total_stock_units'] ?? $data['summary']['total_units'] ?? 0, 0, ',', '.') }} unit</td>
                <td style="width: 25%;"><strong>Arus Mutasi:</strong> <span style="color: #059669; font-weight: bold;">+{{ number_format($data['summary']['total_in'] ?? 0, 0, ',', '.') }}</span> / <span style="color: #e11d48; font-weight: bold;">-{{ number_format($data['summary']['total_out'] ?? 0, 0, ',', '.') }}</span></td>
                <td style="width: 25%; text-align: right;"><strong>Total Nilai Persediaan:</strong> <span style="color: #059669; font-weight: bold;">Rp {{ number_format($data['summary']['total_inventory_value'] ?? $data['summary']['total_valuation'] ?? 0, 2, ',', '.') }}</span></td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;" class="text-center">No</th>
                <th style="width: 10%;" class="text-left">SKU</th>
                <th style="width: 23%;" class="text-left">Nama Produk</th>
                <th style="width: 13%;" class="text-left">Kategori</th>
                <th style="width: 9%;" class="text-right">Harga Beli</th>
                <th style="width: 9%;" class="text-right">Harga Jual</th>
                <th style="width: 7%;" class="text-right">Masuk</th>
                <th style="width: 7%;" class="text-right">Keluar</th>
                <th style="width: 7%;" class="text-right">Sisa</th>
                <th style="width: 12%;" class="text-right">Nilai Aset (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['products'] as $idx => $p)
                @php
                    $stock = (float) ($p['stock'] ?? 0);
                    $unit = $p['unit_symbol'] ?? $p['unit'] ?? 'unit';
                    $status = $p['stock_status'] ?? ($stock <= 0 ? 'out_of_stock' : ($stock <= 10 ? 'low_stock' : 'available'));
                @endphp
                <tr>
                    <td class="text-center font-mono" style="color: #64748b;">{{ $idx + 1 }}</td>
                    <td class="font-mono" style="font-weight: bold; color: #167a93;">{{ $p['sku'] }}</td>
                    <td>
                        <strong>{{ $p['name'] }}</strong>
                        @if($status === 'out_of_stock')
                            <span class="badge badge-danger">Habis</span>
                        @elseif($status === 'low_stock')
                            <span class="badge badge-warning">Menipis</span>
                        @endif
                    </td>
                    <td>{{ $p['category_name'] ?? $p['category'] ?? '-' }}</td>
                    <td class="text-right font-mono">{{ number_format($p['purchase_price'] ?? $p['average_cost'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format($p['selling_price'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="color: #059669;">+{{ number_format($p['total_in'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="color: #e11d48;">-{{ number_format($p['total_out'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="font-weight: bold;">{{ number_format($stock, 0, ',', '.') }} <span style="font-size: 7.5px; color: #64748b;">{{ $unit }}</span></td>
                    <td class="text-right font-mono" style="font-weight: bold;">{{ number_format($p['inventory_value'] ?? $p['valuation'] ?? 0, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="color: #94a3b8; padding: 16px;">Tidak ada data persediaan barang</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="6" class="text-right">GRAND TOTAL</td>
                <td class="text-right font-mono" style="color: #059669;">+{{ number_format($data['summary']['total_in'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #e11d48;">-{{ number_format($data['summary']['total_out'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right font-mono">{{ number_format($data['summary']['total_stock_units'] ?? $data['summary']['total_units'] ?? 0, 0, ',', '.') }} Unit</td>
                <td class="text-right font-mono" style="color: #167a93;">Rp {{ number_format($data['summary']['total_inventory_value'] ?? $data['summary']['total_valuation'] ?? 0, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
@endsection
