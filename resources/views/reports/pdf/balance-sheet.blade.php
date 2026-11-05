@extends('reports.pdf.layout')

@section('title', 'Laporan Neraca — FinToko')
@section('report-title', 'Laporan Neraca (Balance Sheet)')
@section('report-period', 'Posisi Keuangan per: ' . $data['formatted_as_of'])

@section('content')
    <h4 style="margin: 8px 0 4px; color: #0284c7; font-size: 11px; text-transform: uppercase;">1. ASET (ASSETS)</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;" class="text-left">Kode Akun</th>
                <th style="width: 60%;" class="text-left">Nama Akun Aset</th>
                <th style="width: 25%;" class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['assets']['items'] as $item)
                <tr>
                    <td class="font-mono">{{ $item['account_code'] }}</td>
                    <td>{{ $item['account_name'] }}</td>
                    <td class="text-right font-mono">{{ number_format($item['amount'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #94a3b8;">Tidak ada data aset</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL ASET</td>
                <td class="text-right font-mono" style="color: #0284c7;">Rp {{ number_format($data['total_assets'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h4 style="margin: 14px 0 4px; color: #d97706; font-size: 11px; text-transform: uppercase;">2. KEWAJIBAN (LIABILITIES)</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;" class="text-left">Kode Akun</th>
                <th style="width: 60%;" class="text-left">Nama Akun Kewajiban</th>
                <th style="width: 25%;" class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['liabilities']['items'] as $item)
                <tr>
                    <td class="font-mono">{{ $item['account_code'] }}</td>
                    <td>{{ $item['account_name'] }}</td>
                    <td class="text-right font-mono">{{ number_format($item['amount'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #94a3b8;">Tidak ada data kewajiban</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL KEWAJIBAN</td>
                <td class="text-right font-mono" style="color: #d97706;">Rp {{ number_format($data['total_liabilities'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h4 style="margin: 14px 0 4px; color: #7c3aed; font-size: 11px; text-transform: uppercase;">3. EKUITAS / MODAL (EQUITY)</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;" class="text-left">Kode Akun</th>
                <th style="width: 60%;" class="text-left">Komponen Ekuitas</th>
                <th style="width: 25%;" class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['equity']['items'] as $item)
                <tr>
                    <td class="font-mono">{{ $item['account_code'] }}</td>
                    <td>{{ $item['account_name'] }}</td>
                    <td class="text-right font-mono">{{ number_format($item['amount'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #94a3b8;">Tidak ada data ekuitas</td>
                </tr>
            @endforelse
            <tr>
                <td class="font-mono">-</td>
                <td><em>Laba / (Rugi) Berjalan Kumulatif</em></td>
                <td class="text-right font-mono">{{ number_format($data['equity']['current_earnings'], 2, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL EKUITAS</td>
                <td class="text-right font-mono" style="color: #7c3aed;">Rp {{ number_format($data['total_equity'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px; padding: 12px; background-color: {{ $data['is_balanced'] ? '#f0fdf4' : '#fff1f2' }}; border: 1px solid {{ $data['is_balanced'] ? '#bbf7d0' : '#fecdd3' }}; border-radius: 6px;">
        <table style="width: 100%;">
            <tr>
                <td style="font-size: 11px; font-weight: bold; color: {{ $data['is_balanced'] ? '#166534' : '#9f1239' }};">
                    TOTAL KEWAJIBAN & EKUITAS: Rp {{ number_format($data['total_liabilities_and_equity'], 2, ',', '.') }}
                </td>
                <td style="text-align: right; font-size: 11px; font-weight: bold; color: {{ $data['is_balanced'] ? '#166534' : '#9f1239' }};">
                    {{ $data['is_balanced'] ? 'STATUS: SEIMBANG (EQUILIBRIUM)' : 'STATUS: TIDAK SEIMBANG (SELISIH: Rp ' . number_format($data['difference'], 2, ',', '.') . ')' }}
                </td>
            </tr>
        </table>
    </div>
@endsection
