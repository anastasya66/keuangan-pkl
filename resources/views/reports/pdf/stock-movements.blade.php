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
                <th style="width: 14%;" class="text-left">Kode Mutasi</th>
                <th style="width: 13%;" class="text-center">Tanggal &amp; Waktu</th>
                <th style="width: 22%;" class="text-left">Nama Produk</th>
                <th style="width: 14%;" class="text-center">Jenis Mutasi</th>
                <th style="width: 9%;" class="text-right">Qty</th>
                <th style="width: 14%;" class="text-left">Keterangan</th>
                <th style="width: 14%;" class="text-left">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['movements'] as $m)
                @php
                    $rawType = $m['movement_type'] ?? $m['type'] ?? '';
                    $isIn = in_array($rawType, ['stock_in', 'purchase', 'initial_stock', 'sale_return']) || ($m['type_category'] ?? '') === 'in';
                    $isOut = in_array($rawType, ['stock_out', 'sale', 'adjustment_out', 'purchase_reversal', 'purchase_return']) || ($m['type_category'] ?? '') === 'out';
                @endphp
                <tr>
                    <td class="font-mono">
                        {{ $m['movement_code'] ?? $m['code'] }}
                        @if(!empty($m['reference_number']) && $m['reference_number'] !== ($m['movement_code'] ?? ''))
                            <br><small style="color: #64748b;">Ref: {{ $m['reference_number'] }}</small>
                        @endif
                    </td>
                    <td class="text-center font-mono">{{ $m['date'] ?? $m['movement_date'] }}</td>
                    <td>
                        <strong>{{ $m['product_name'] }}</strong>
                        <br><small style="color: #64748b;">SKU: {{ $m['product_sku'] ?? $m['sku'] ?? '-' }}</small>
                    </td>
                    <td class="text-center">
                        @if($isIn)
                            <span class="badge badge-success">{{ $m['movement_type_label'] ?? 'Masuk' }}</span>
                        @elseif($isOut)
                            <span class="badge badge-danger">{{ $m['movement_type_label'] ?? 'Keluar' }}</span>
                        @else
                            <span class="badge badge-warning">{{ $m['movement_type_label'] ?? 'Opname' }}</span>
                        @endif
                    </td>
                    <td class="text-right font-mono font-bold" style="color: {{ $isIn ? '#059669' : ($isOut ? '#e11d48' : '#d97706') }};">
                        {{ $isIn ? '+' : '-' }}{{ number_format($m['quantity'], 2, ',', '.') }} {{ $m['unit_symbol'] ?? '' }}
                    </td>
                    <td style="font-size: 8.5px; color: #334155;">{{ $m['description'] ?? $m['notes'] ?? '-' }}</td>
                    <td>{{ $m['creator_name'] ?? $m['creator'] ?? 'Administrator' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="color: #94a3b8;">Tidak ada data mutasi persediaan</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection

