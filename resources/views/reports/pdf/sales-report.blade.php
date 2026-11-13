@extends('reports.pdf.layout')

@section('title', 'Laporan Penjualan — FinToko')
@section('report-title', 'Laporan Analisis Penjualan, Laba & Margin (' . ($data['mode'] === 'monthly' ? 'Bulanan' : 'Harian') . ')')
@section('report-period', 'Periode: ' . $data['period']['formatted'])

@section('content')
    {{-- Summary Cards --}}
    <table style="width: 100%; margin-bottom: 15px; border-collapse: separate; border-spacing: 6px;">
        <tr>
            <td style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px; text-align: center; width: 20%;">
                <div style="font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase;">Total Transaksi</div>
                <div style="font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 2px;">{{ number_format($data['summary']['total_transactions']) }}</div>
            </td>
            <td style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px; text-align: center; width: 20%;">
                <div style="font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase;">Total Omzet (Rp)</div>
                <div style="font-size: 13px; font-weight: bold; color: #0284c7; margin-top: 2px;">{{ number_format($data['summary']['total_revenue'], 0, ',', '.') }}</div>
            </td>
            <td style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px; text-align: center; width: 20%;">
                <div style="font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase;">Total HPP (Rp)</div>
                <div style="font-size: 13px; font-weight: bold; color: #475569; margin-top: 2px;">{{ number_format($data['summary']['total_cogs'], 0, ',', '.') }}</div>
            </td>
            <td style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px; text-align: center; width: 20%;">
                <div style="font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase;">Laba Kotor (Rp)</div>
                <div style="font-size: 13px; font-weight: bold; color: #059669; margin-top: 2px;">{{ number_format($data['summary']['gross_profit'], 0, ',', '.') }}</div>
            </td>
            <td style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px; text-align: center; width: 20%;">
                <div style="font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase;">Margin Rata-rata</div>
                <div style="font-size: 13px; font-weight: bold; color: #0d9488; margin-top: 2px;">{{ number_format($data['summary']['profit_margin'], 2, ',', '.') }}%</div>
            </td>
        </tr>
    </table>

    {{-- Detail Data Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 25%;" class="text-left">{{ $data['mode'] === 'monthly' ? 'Bulan & Tahun' : 'Tanggal Transaksi' }}</th>
                <th style="width: 10%;" class="text-center">Jml Tx</th>
                <th style="width: 20%;" class="text-right">Total Omzet (Rp)</th>
                <th style="width: 20%;" class="text-right">Total HPP (Rp)</th>
                <th style="width: 20%;" class="text-right">Laba Kotor (Rp)</th>
                <th style="width: 10%;" class="text-center">Margin (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['items'] as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item['label'] }}</strong>
                        @if(!empty($item['sublabel']))
                            <br><small style="color: #64748b;">{{ $item['sublabel'] }}</small>
                        @endif
                    </td>
                    <td class="text-center font-mono">{{ number_format($item['transaction_count']) }}</td>
                    <td class="text-right font-mono">{{ number_format($item['total_revenue'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format($item['total_cogs'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono" style="color: {{ $item['gross_profit'] >= 0 ? '#059669' : '#e11d48' }}; font-weight: bold;">
                        {{ number_format($item['gross_profit'], 2, ',', '.') }}
                    </td>
                    <td class="text-center font-mono">
                        <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8.5px; font-weight: bold; background-color: {{ $item['profit_margin'] >= 20 ? '#dcfce7' : ($item['profit_margin'] > 0 ? '#e0f2fe' : '#fee2e2') }}; color: {{ $item['profit_margin'] >= 20 ? '#15803d' : ($item['profit_margin'] > 0 ? '#0369a1' : '#b91c1c') }};">
                            {{ number_format($item['profit_margin'], 2, ',', '.') }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="color: #94a3b8; padding: 14px;">Tidak ada transaksi penjualan pada periode ini</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL AKUMULASI</td>
                <td class="text-center font-mono">{{ number_format($data['summary']['total_transactions']) }}</td>
                <td class="text-right font-mono">Rp {{ number_format($data['summary']['total_revenue'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">Rp {{ number_format($data['summary']['total_cogs'], 2, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #059669;">Rp {{ number_format($data['summary']['gross_profit'], 2, ',', '.') }}</td>
                <td class="text-center font-mono" style="color: #0d9488;">{{ number_format($data['summary']['profit_margin'], 2, ',', '.') }}%</td>
            </tr>
        </tbody>
    </table>
@endsection

