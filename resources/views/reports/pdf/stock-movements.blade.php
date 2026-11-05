@extends('reports.pdf.layout')

@section('title', 'Laporan Riwayat Mutasi Persediaan — FinToko')
@section('report-title', 'Laporan Mutasi Persediaan (Stock Movements)')
@section('report-period', 'Dicetak: ' . now()->translatedFormat('d F Y H:i'))

@section('content')
    <div style="margin-bottom: 12px; padding: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
        <table style="width: 100%; font-size: 10px;">
            <tr>
                <td style="width: 25%;"><strong>Total Mutasi:</strong> {{ $data['summary']['total_movements'] }}</td>
                <td style="width: 25%;"><strong>Total Masuk:</strong> <span style="color: #059669; font-weight: bold;">+{{ number_format($data['summary']['total_stock_in'], 2, ',', '.') }}</span></td>
                <td style="width: 25%;"><strong>Total Keluar:</strong> <span style="color: #e11d48; font-weight: bold;">-{{ number_format($data['summary']['total_stock_out'], 2, ',', '.') }}</span></td>
                <td style="width: 25%; text-align: right;"><strong>Penyesuaian Opname:</strong> {{ number_format($data['summary']['total_adjustments'], 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;" class="text-left">Kode Mutasi</th>
                <th style="width: 10%;" class="text-center">Tanggal</th>
                <th style="width: 25%;" class="text-left">Nama Produk</th>
                <th style="width: 16%;" class="text-center">Jenis Mutasi</th>
                <th style="width: 10%;" class="text-right">Qty</th>
                <th style="width: 12%;" class="text-right">Stok Akhir</th>
                <th style="width: 12%;" class="text-left">Pencatat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['movements'] as $m)
                <tr>
                    <td class="font-mono">{{ $m['movement_code'] }}</td>
                    <td class="text-center font-mono">{{ $m['movement_date'] }}</td>
                    <td><strong>{{ $m['product_name'] }}</strong></td>
                    <td class="text-center">
                        @if($m['movement_type'] === 'stock_in')
                            <span class="badge badge-success">Masuk</span>
                        @elseif($m['movement_type'] === 'stock_out')
                            <span class="badge badge-danger">Keluar</span>
                        @else
                            <span class="badge badge-warning">Opname</span>
                        @endif
                    </td>
                    <td class="text-right font-mono font-bold">{{ number_format($m['quantity'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format($m['stock_after'], 2, ',', '.') }} {{ $m['unit_symbol'] }}</td>
                    <td>{{ $m['creator_name'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="color: #94a3b8;">Tidak ada data mutasi persediaan</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection

