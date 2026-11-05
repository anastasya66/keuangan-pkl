@extends('reports.pdf.layout')

@section('title', 'Laporan Laba Rugi — FinToko')
@section('report-title', 'Laporan Laba Rugi (Income Statement)')
@section('report-period', 'Periode: ' . $data['period']['formatted'])

@section('content')
    <h4 style="margin: 8px 0 4px; color: #059669; font-size: 11px; text-transform: uppercase;">1. PENDAPATAN (REVENUE)</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;" class="text-left">Kode Akun</th>
                <th style="width: 60%;" class="text-left">Nama Akun Pendapatan</th>
                <th style="width: 25%;" class="text-right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['revenues']['items'] as $item)
                <tr>
                    <td class="font-mono">{{ $item['account_code'] }}</td>
                    <td>{{ $item['account_name'] }}</td>
                    <td class="text-right font-mono">{{ number_format($item['amount'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #94a3b8;">Tidak ada pendapatan pada periode ini</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL PENDAPATAN</td>
                <td class="text-right font-mono" style="color: #059669;">Rp {{ number_format($data['revenues']['total'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h4 style="margin: 14px 0 4px; color: #e11d48; font-size: 11px; text-transform: uppercase;">2. BEBAN OPERASIONAL (EXPENSES)</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;" class="text-left">Kode Akun</th>
                <th style="width: 60%;" class="text-left">Nama Akun Beban</th>
                <th style="width: 25%;" class="text-right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['expenses']['items'] as $item)
                <tr>
                    <td class="font-mono">{{ $item['account_code'] }}</td>
                    <td>{{ $item['account_name'] }}</td>
                    <td class="text-right font-mono">{{ number_format($item['amount'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #94a3b8;">Tidak ada beban pada periode ini</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL BEBAN</td>
                <td class="text-right font-mono" style="color: #e11d48;">Rp {{ number_format($data['expenses']['total'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px; padding: 12px; background-color: {{ $data['is_profit'] ? '#f0fdf4' : '#fff1f2' }}; border: 1px solid {{ $data['is_profit'] ? '#bbf7d0' : '#fecdd3' }}; border-radius: 6px;">
        <table style="width: 100%;">
            <tr>
                <td style="font-size: 12px; font-weight: bold; color: {{ $data['is_profit'] ? '#166534' : '#9f1239' }};">
                    {{ $data['is_profit'] ? 'LABA BERSIH PERIODE (NET PROFIT)' : 'RUGI BERSIH PERIODE (NET LOSS)' }}
                </td>
                <td style="text-align: right; font-size: 14px; font-weight: bold; font-family: 'Courier New', Courier, monospace; color: {{ $data['is_profit'] ? '#166534' : '#9f1239' }};">
                    Rp {{ number_format($data['net_profit'], 2, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>
@endsection
