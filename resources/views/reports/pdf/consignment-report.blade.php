@extends('reports.pdf.layout')

@section('title', 'Laporan Penjualan & Bagi Hasil Konsinyasi — FinToko')
@section('report-title', 'Laporan Penjualan & Bagi Hasil Konsinyasi')
@section('report-period', 'Dicetak pada: ' . now()->translatedFormat('d F Y H:i'))

@section('content')
    <div style="margin-bottom: 12px; padding: 8px 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
        <table style="width: 100%; font-size: 9px;">
            <tr>
                <td style="width: 25%;"><strong>Total Terjual:</strong> {{ number_format($data['summary']['total_sold_qty'] ?? 0, 0, ',', '.') }} unit</td>
                <td style="width: 25%;"><strong>Total Penjualan:</strong> Rp {{ number_format($data['summary']['total_sales_amount'] ?? 0, 0, ',', '.') }}</td>
                <td style="width: 25%;"><strong>Komisi Toko:</strong> <span style="color: #059669; font-weight: bold;">Rp {{ number_format($data['summary']['total_store_commission'] ?? 0, 0, ',', '.') }}</span></td>
                <td style="width: 25%; text-align: right;"><strong>Tagihan Belum Disetor:</strong> <span style="color: #d97706; font-weight: bold;">Rp {{ number_format($data['summary']['unsettled_payable'] ?? 0, 0, ',', '.') }}</span></td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;" class="text-center">No</th>
                <th style="width: 10%;" class="text-center">Tanggal</th>
                <th style="width: 13%;" class="text-left">No Faktur POS</th>
                <th style="width: 20%;" class="text-left">Produk Konsinyasi</th>
                <th style="width: 14%;" class="text-left">Mitra (Konsinyor)</th>
                <th style="width: 6%;" class="text-right">Qty</th>
                <th style="width: 11%;" class="text-right">Total Jual (Rp)</th>
                <th style="width: 11%;" class="text-right">Modal Mitra (Rp)</th>
                <th style="width: 12%;" class="text-right">Komisi Toko (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['sales'] as $idx => $item)
                @php
                    $saleDate = !empty($item['sale']['sale_date']) ? date('d/m/Y', strtotime($item['sale']['sale_date'])) : '-';
                @endphp
                <tr>
                    <td class="text-center font-mono" style="color: #64748b;">{{ $idx + 1 }}</td>
                    <td class="text-center font-mono">{{ $saleDate }}</td>
                    <td class="font-mono" style="font-weight: bold; color: #167a93;">{{ $item['sale']['transaction_number'] ?? $item['sale']['invoice_number'] ?? '-' }}</td>
                    <td><strong>{{ $item['product']['name'] ?? '-' }}</strong></td>
                    <td>{{ $item['consignor']['name'] ?? '-' }}</td>
                    <td class="text-right font-mono">{{ number_format($item['quantity'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="color: #64748b;">{{ number_format($item['consignment_cost'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="font-weight: bold; color: #059669;">{{ number_format($item['commission_amount'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 16px; color: #94a3b8;">
                        Tidak ada riwayat transaksi penjualan konsinyasi pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($data['sales']))
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="5" class="text-center">TOTAL KESELURUHAN</td>
                    <td class="text-right font-mono">{{ number_format($data['summary']['total_sold_qty'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($data['summary']['total_sales_amount'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono">Rp {{ number_format(($data['summary']['unsettled_payable'] ?? 0) + ($data['summary']['settled_paid'] ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="color: #059669;">Rp {{ number_format($data['summary']['total_store_commission'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection

