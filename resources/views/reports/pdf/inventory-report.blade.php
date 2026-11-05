@extends('reports.pdf.layout')

@section('title', 'Laporan Stok & Valuasi Persediaan — FinToko')
@section('report-title', 'Laporan Stok Produk (Inventory Report)')
@section('report-period', 'Posisi Stok per: ' . now()->translatedFormat('d F Y'))

@section('content')
    <div style="margin-bottom: 12px; padding: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
        <table style="width: 100%; font-size: 10px;">
            <tr>
                <td style="width: 25%;"><strong>Total Item Produk:</strong> {{ $data['summary']['total_products'] }}</td>
                <td style="width: 25%;"><strong>Total Unit Fisik:</strong> {{ number_format($data['summary']['total_stock_units'], 2, ',', '.') }}</td>
                <td style="width: 25%;"><strong>Stok Menipis / Habis:</strong> {{ $data['summary']['low_stock_count'] + $data['summary']['out_of_stock_count'] }}</td>
                <td style="width: 25%; text-align: right;"><strong>Total Nilai Persediaan:</strong> <span style="color: #059669; font-weight: bold;">Rp {{ number_format($data['summary']['total_inventory_value'], 2, ',', '.') }}</span></td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;" class="text-left">SKU</th>
                <th style="width: 32%;" class="text-left">Nama Produk</th>
                <th style="width: 16%;" class="text-left">Kategori</th>
                <th style="width: 10%;" class="text-right">Stok</th>
                <th style="width: 13%;" class="text-right">Harga Beli</th>
                <th style="width: 17%;" class="text-right">Nilai Aset (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['products'] as $p)
                <tr>
                    <td class="font-mono">{{ $p['sku'] }}</td>
                    <td>
                        <strong>{{ $p['name'] }}</strong>
                        @if($p['stock_status'] === 'out_of_stock')
                            <span class="badge badge-danger">Habis</span>
                        @elseif($p['stock_status'] === 'low_stock')
                            <span class="badge badge-warning">Menipis</span>
                        @endif
                    </td>
                    <td>{{ $p['category_name'] }}</td>
                    <td class="text-right font-mono">{{ number_format($p['stock'], 2, ',', '.') }} {{ $p['unit_symbol'] }}</td>
                    <td class="text-right font-mono">{{ number_format($p['purchase_price'], 0, ',', '.') }}</td>
                    <td class="text-right font-mono font-bold">{{ number_format($p['inventory_value'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="color: #94a3b8;">Tidak ada data persediaan</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" class="text-right">GRAND TOTAL PERSEDIAAN</td>
                <td class="text-right font-mono">{{ number_format($data['summary']['total_stock_units'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono" style="color: #059669;">Rp {{ number_format($data['summary']['total_inventory_value'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
@endsection

