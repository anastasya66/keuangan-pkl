import React, { useEffect, useState, useMemo, useCallback } from 'react';
import {
  RefreshCw,
  FileText,
  FileSpreadsheet,
  Calendar,
  TrendingUp,
  DollarSign,
  ShoppingCart,
  Percent,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
} from 'lucide-react';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';
import PageHeader from '../../components/ui/PageHeader';

export default function SalesReportPage() {
  const currentYear = new Date().getFullYear();
  const today = new Date().toISOString().split('T')[0];
  const firstDayOfMonth = new Date(currentYear, new Date().getMonth(), 1)
    .toISOString()
    .split('T')[0];

  // Filter States
  const [mode, setMode] = useState('daily'); // 'daily' | 'monthly'
  const [startDate, setStartDate] = useState(firstDayOfMonth);
  const [endDate, setEndDate] = useState(today);
  const [activePreset, setActivePreset] = useState('thisMonth'); // 'today' | '7days' | 'thisMonth' | null
  const [selectedYear, setSelectedYear] = useState(currentYear);
  const [selectedMonth, setSelectedMonth] = useState(''); // '' (Semua) | '1' .. '12'

  // Data States
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [exportingPdf, setExportingPdf] = useState(false);
  const [exportingExcel, setExportingExcel] = useState(false);
  const [error, setError] = useState(null);
  const [lastRefreshed, setLastRefreshed] = useState(null);

  // Sorting States
  const [sortField, setSortField] = useState('period_key');
  const [sortDirection, setSortDirection] = useState('asc'); // 'asc' | 'desc'

  // Fetch Report Data from API
  const fetchReport = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { mode };
      if (mode === 'daily') {
        params.start_date = startDate;
        params.end_date = endDate;
      } else {
        params.year = selectedYear;
        if (selectedMonth) {
          params.month = selectedMonth;
        }
      }

      const res = await reportService.getSalesReport(params);
      setData(res.data);
      setLastRefreshed(new Date());
    } catch (err) {
      console.error('Gagal mengambil Laporan Penjualan:', err);
      setError('Gagal memuat laporan penjualan. Silakan periksa koneksi atau coba lagi.');
    } finally {
      setLoading(false);
    }
  }, [mode, startDate, endDate, selectedYear, selectedMonth]);

  // Reactive Effect: Fetch otomatis saat ada perubahan filter apapun
  useEffect(() => {
    fetchReport();
  }, [fetchReport]);

  // Quick Date Range Presets for Daily Mode
  const handleDatePreset = (preset) => {
    const now = new Date();
    setActivePreset(preset);

    if (preset === 'today') {
      const str = now.toISOString().split('T')[0];
      setStartDate(str);
      setEndDate(str);
    } else if (preset === '7days') {
      const past = new Date(now.getTime() - 6 * 24 * 60 * 60 * 1000);
      setStartDate(past.toISOString().split('T')[0]);
      setEndDate(now.toISOString().split('T')[0]);
    } else if (preset === 'thisMonth') {
      const start = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
      setStartDate(start);
      setEndDate(now.toISOString().split('T')[0]);
    }
  };

  // Export Handlers
  const handleExportPdf = async () => {
    setExportingPdf(true);
    try {
      const params = { mode };
      if (mode === 'daily') {
        params.start_date = startDate;
        params.end_date = endDate;
      } else {
        params.year = selectedYear;
        if (selectedMonth) params.month = selectedMonth;
      }
      await reportService.downloadSalesReportPdf(params);
    } catch (err) {
      alert(err.message || 'Gagal mengunduh file PDF Laporan Penjualan.');
    } finally {
      setExportingPdf(false);
    }
  };

  const handleExportExcel = async () => {
    setExportingExcel(true);
    try {
      const params = { mode };
      if (mode === 'daily') {
        params.start_date = startDate;
        params.end_date = endDate;
      } else {
        params.year = selectedYear;
        if (selectedMonth) params.month = selectedMonth;
      }
      await reportService.downloadSalesReportExcel(params);
    } catch (err) {
      alert(err.message || 'Gagal mengunduh file Excel Laporan Penjualan.');
    } finally {
      setExportingExcel(false);
    }
  };

  // Sorting Handler
  const handleSort = (field) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('desc'); // Default descending untuk sorting kolom metrik baru
    }
  };

  // Render Sort Icon
  const renderSortIcon = (field) => {
    if (sortField !== field) {
      return (
        <ArrowUpDown className="w-3 h-3 text-[#A0ABBA] opacity-50 group-hover:opacity-100 transition-opacity shrink-0" />
      );
    }
    return sortDirection === 'asc' ? (
      <ArrowUp className="w-3 h-3 text-[#167A93] shrink-0" />
    ) : (
      <ArrowDown className="w-3 h-3 text-[#167A93] shrink-0" />
    );
  };

  const summary = data?.summary || {
    total_transactions: 0,
    total_revenue: 0,
    total_cogs: 0,
    gross_profit: 0,
    profit_margin: 0,
  };

  const rawItems = data?.items || [];

  // Sorted Items (Semua baris tampil langsung tanpa pagination)
  const sortedItems = useMemo(() => {
    if (!rawItems || rawItems.length === 0) return [];
    const itemsCopy = [...rawItems];

    return itemsCopy.sort((a, b) => {
      let aVal = a[sortField];
      let bVal = b[sortField];

      if (sortField === 'period_key' || sortField === 'date') {
        aVal = a.period_key || a.date || '';
        bVal = b.period_key || b.date || '';
        return sortDirection === 'asc'
          ? String(aVal).localeCompare(String(bVal))
          : String(bVal).localeCompare(String(aVal));
      }

      aVal = Number(aVal || 0);
      bVal = Number(bVal || 0);

      return sortDirection === 'asc' ? aVal - bVal : bVal - aVal;
    });
  }, [rawItems, sortField, sortDirection]);

  const totalItems = sortedItems.length;

  // Generate Year Options (5 tahun ke belakang dan tahun ini)
  const yearOptions = useMemo(() => {
    const years = [];
    for (let y = currentYear; y >= currentYear - 4; y--) {
      years.push(y);
    }
    return years;
  }, [currentYear]);

  // Months list for dropdown
  const monthOptions = [
    { value: '', label: 'Semua Bulan (Jan - Des)' },
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
  ];

  return (
    <div className="space-y-6 animate-fade-in">
      {/* 1. Header & Actions */}
      <PageHeader
        title="Laporan Penjualan"
        subtitle="Analisis laba kotor, HPP (modal barang), dan persentase margin keuntungan harian serta bulanan dari transaksi kasir POS."
      >
        <div className="flex items-center gap-2">
          <button
            onClick={fetchReport}
            disabled={loading}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-[#EAEFF1] hover:bg-[#D7EBEF] text-[#20282D] font-bold text-xs transition shadow-xs disabled:opacity-50 cursor-pointer whitespace-nowrap"
            title="Muat ulang dan sinkronkan data penjualan terbaru"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
            <span>{loading ? 'Menyinkronkan...' : 'Sinkronkan Data'}</span>
          </button>

          <button
            onClick={handleExportPdf}
            disabled={exportingPdf || loading}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-[#E5F6FA] text-[#37AFCB] hover:bg-[#D0F1F8] font-bold text-xs transition shadow-xs disabled:opacity-50 cursor-pointer whitespace-nowrap"
          >
            <FileText className="w-3.5 h-3.5" />
            <span>{exportingPdf ? 'Mengunduh...' : 'PDF'}</span>
          </button>

          <button
            onClick={handleExportExcel}
            disabled={exportingExcel || loading}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-[#EBF7F2] text-[#48B58B] hover:bg-[#D5EFE3] font-bold text-xs transition shadow-xs disabled:opacity-50 cursor-pointer whitespace-nowrap"
          >
            <FileSpreadsheet className="w-3.5 h-3.5" />
            <span>{exportingExcel ? 'Mengunduh...' : 'Excel'}</span>
          </button>
        </div>
      </PageHeader>

      {/* Real-time Indicator */}
      <div className="mt-1 pt-2 flex items-center justify-between text-[11px] text-[#577782]">
        <div className="flex items-center gap-2">
          <span className="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Sinkronisasi otomatis aktif dari transaksi kasir POS.</span>
        </div>
        {lastRefreshed && (
          <span className="text-[10px] text-[#8EA6AF]">
            Update terakhir: {lastRefreshed.toLocaleTimeString('id-ID')} WIB
          </span>
        )}
      </div>

      {/* 2. Filter Bar (Reactive Auto-Sync Filter) */}
      <div className="bg-white border border-[#E7ECEE] rounded-2xl p-5 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          {/* Mode Switch Tabs */}
          <div className="flex items-center gap-1 p-1 bg-[#F1F4F5] rounded-xl self-start">
            <button
              type="button"
              onClick={() => setMode('daily')}
              className={`px-4 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                mode === 'daily'
                  ? 'bg-white text-[#20282D] shadow-xs'
                  : 'text-[#879197] hover:text-[#20282D]'
              }`}
            >
              Harian (Daily)
            </button>
            <button
              type="button"
              onClick={() => setMode('monthly')}
              className={`px-4 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                mode === 'monthly'
                  ? 'bg-white text-[#20282D] shadow-xs'
                  : 'text-[#879197] hover:text-[#20282D]'
              }`}
            >
              Bulanan (Monthly)
            </button>
          </div>

          {/* Filter Controls (100% Reactive - Tanpa Tombol Tampilkan) */}
          <div className="flex flex-wrap items-end gap-3">
            {mode === 'daily' ? (
              <>
                <div>
                  <label className="block text-[11px] font-semibold text-[#879197] mb-1">
                    Dari Tanggal
                  </label>
                  <input
                    type="date"
                    value={startDate}
                    onChange={(e) => {
                      setStartDate(e.target.value);
                      setActivePreset(null);
                    }}
                    className="bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl px-3 py-2 text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-semibold text-[#879197] mb-1">
                    Sampai Tanggal
                  </label>
                  <input
                    type="date"
                    value={endDate}
                    onChange={(e) => {
                      setEndDate(e.target.value);
                      setActivePreset(null);
                    }}
                    className="bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl px-3 py-2 text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
                  />
                </div>

                {/* Shortcut Preset Buttons (Hari Ini, 7 Hari, Bulan Ini) dengan State Highlight Aktif */}
                <div className="flex items-center gap-1.5">
                  <button
                    type="button"
                    onClick={() => handleDatePreset('today')}
                    className={`px-3 py-2 rounded-xl text-[11px] transition cursor-pointer border ${
                      activePreset === 'today'
                        ? 'bg-[#20282D] text-white border-[#20282D] font-bold shadow-xs'
                        : 'bg-[#F8FAFA] hover:bg-[#EAEFF1] text-[#577782] border-[#E7ECEE] font-semibold'
                    }`}
                  >
                    Hari Ini
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDatePreset('7days')}
                    className={`px-3 py-2 rounded-xl text-[11px] transition cursor-pointer border ${
                      activePreset === '7days'
                        ? 'bg-[#20282D] text-white border-[#20282D] font-bold shadow-xs'
                        : 'bg-[#F8FAFA] hover:bg-[#EAEFF1] text-[#577782] border-[#E7ECEE] font-semibold'
                    }`}
                  >
                    7 Hari
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDatePreset('thisMonth')}
                    className={`px-3 py-2 rounded-xl text-[11px] transition cursor-pointer border ${
                      activePreset === 'thisMonth'
                        ? 'bg-[#20282D] text-white border-[#20282D] font-bold shadow-xs'
                        : 'bg-[#F8FAFA] hover:bg-[#EAEFF1] text-[#577782] border-[#E7ECEE] font-semibold'
                    }`}
                  >
                    Bulan Ini
                  </button>
                </div>
              </>
            ) : (
              <>
                {/* Dropdown Pilih Bulan */}
                <div>
                  <label className="block text-[11px] font-semibold text-[#879197] mb-1">
                    Pilih Bulan
                  </label>
                  <select
                    value={selectedMonth}
                    onChange={(e) => setSelectedMonth(e.target.value)}
                    className="bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl px-3.5 py-2 text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8] min-w-[175px]"
                  >
                    {monthOptions.map((opt) => (
                      <option key={opt.value} value={opt.value}>
                        {opt.label}
                      </option>
                    ))}
                  </select>
                </div>

                {/* Dropdown Pilih Tahun */}
                <div>
                  <label className="block text-[11px] font-semibold text-[#879197] mb-1">
                    Pilih Tahun
                  </label>
                  <select
                    value={selectedYear}
                    onChange={(e) => setSelectedYear(Number(e.target.value))}
                    className="bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl px-3.5 py-2 text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8] min-w-[110px]"
                  >
                    {yearOptions.map((y) => (
                      <option key={y} value={y}>
                        Tahun {y}
                      </option>
                    ))}
                  </select>
                </div>
              </>
            )}
          </div>
        </div>
      </div>

      {/* 3. Summary Cards (Statistik Laba & Margin) */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
        <div className="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-0 md:divide-x md:divide-[#D7EBEF]">
          {/* Card 1: Total Transaksi */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">
              Total Transaksi
            </span>
            <div className="text-xl sm:text-2xl font-extrabold font-mono text-[#16323B] mt-0.5">
              {loading ? '...' : Number(summary.total_transactions).toLocaleString('id-ID')}{' '}
              <span className="text-xs font-normal text-[#577782]">Struk</span>
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Penjualan selesai</span>
          </div>

          {/* Card 2: Total Omzet */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">
              Total Omzet
            </span>
            <div className="text-xl sm:text-2xl font-extrabold font-mono text-[#167A93] mt-0.5">
              {loading ? '...' : formatRupiah(summary.total_revenue)}
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Pendapatan kotor</span>
          </div>

          {/* Card 3: Total HPP */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">
              Total HPP (Modal)
            </span>
            <div className="text-xl sm:text-2xl font-extrabold font-mono text-[#577782] mt-0.5">
              {loading ? '...' : formatRupiah(summary.total_cogs)}
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Biaya modal barang</span>
          </div>

          {/* Card 4: Laba Kotor */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">
              Laba Kotor (Profit)
            </span>
            <div className={`text-xl sm:text-2xl font-extrabold font-mono mt-0.5 ${summary.gross_profit >= 0 ? 'text-[#1B8751]' : 'text-[#C53030]'}`}>
              {loading ? '...' : formatRupiah(summary.gross_profit)}
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Omzet dikurangi HPP</span>
          </div>

          {/* Card 5: Rata-rata Margin */}
          <div className="px-3 py-1 col-span-2 md:col-span-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">
              Rata-rata Margin
            </span>
            <div className="flex items-center gap-2 mt-0.5">
              <span className={`text-xl sm:text-2xl font-extrabold font-mono ${summary.profit_margin >= 20 ? 'text-emerald-700' : (summary.profit_margin > 0 ? 'text-teal-700' : 'text-rose-700')}`}>
                {loading ? '...' : `${Number(summary.profit_margin).toFixed(2)}%`}
              </span>
              <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#EAF8FA] text-[#167A93]">
                {mode === 'daily' ? 'Harian' : 'Bulanan'}
              </span>
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Rasio keuntungan</span>
          </div>
        </div>
      </div>

      {/* 4. Tabel Rincian Data (Full Baris Tanpa Batas Paginasi & Alignment Presisi) */}
      <div className="bg-white border border-[#E7ECEE] rounded-2xl overflow-hidden shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        {/* Table Subheader (Hanya Menampilkan Indikator Jumlah Data Tanpa Dropdown Tampilkan) */}
        <div className="p-4 border-b border-[#F1F4F5] flex items-center justify-between">
          <div className="flex items-center gap-2">
            <h3 className="text-xs font-bold uppercase tracking-wider text-[#20282D]">
              Rincian Analisis Penjualan & Margin ({mode === 'monthly' ? 'Bulanan' : 'Harian'})
            </h3>
            {data?.period?.formatted && (
              <span className="text-[11px] text-[#879197] font-medium hidden sm:inline">
                • {data.period.formatted}
              </span>
            )}
          </div>
          
          <span className="text-xs font-semibold text-[#879197]">
            Total: {totalItems} {mode === 'monthly' ? 'Bulan' : 'Hari'}
          </span>
        </div>

        {loading ? (
          <div className="text-center py-16 text-[#879197]">
            <div className="inline-block w-8 h-8 border-2 border-[#55BFD8] border-t-transparent rounded-full animate-spin mb-3"></div>
            <p className="text-xs font-medium">Memuat data laporan penjualan...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-[#E47777] font-semibold text-xs">{error}</div>
        ) : totalItems === 0 ? (
          <div className="text-center py-16 text-[#879197]">
            <ShoppingCart className="w-10 h-10 mx-auto text-[#D7EBEF] mb-3" />
            <p className="text-xs font-semibold text-[#20282D]">Belum ada data penjualan pada periode ini</p>
            <p className="text-[11px] text-[#8EA6AF] mt-1">Transaksi penjualan kasir POS yang terselesaikan akan otomatis muncul di sini.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full border-collapse">
              <thead>
                <tr className="bg-[#F8FAFA] text-[11px] font-bold text-[#879197] uppercase tracking-wider border-b border-[#E7ECEE]">
                  {/* Kolom NO: Rata tengah */}
                  <th className="py-3.5 px-4 w-14 text-center select-none">
                    No
                  </th>

                  {/* Kolom TANGGAL TRANSAKSI / BULAN & TAHUN: Rata kiri */}
                  <th
                    onClick={() => handleSort('period_key')}
                    className="py-3.5 px-4 text-left cursor-pointer group select-none hover:text-[#167A93] transition-colors"
                  >
                    <div className="inline-flex items-center gap-1">
                      <span>{mode === 'monthly' ? 'Bulan & Tahun' : 'Tanggal Transaksi'}</span>
                      {renderSortIcon('period_key')}
                    </div>
                  </th>

                  {/* Kolom JML TRANSAKSI: Rata tengah presisi */}
                  <th
                    onClick={() => handleSort('transaction_count')}
                    className="py-3.5 px-4 text-center cursor-pointer group select-none hover:text-[#167A93] transition-colors"
                  >
                    <div className="flex items-center justify-center gap-1 w-full">
                      <span>Jml Transaksi</span>
                      {renderSortIcon('transaction_count')}
                    </div>
                  </th>

                  {/* Kolom TOTAL OMZET: Rata kanan presisi */}
                  <th
                    onClick={() => handleSort('total_revenue')}
                    className="py-3.5 px-4 text-right cursor-pointer group select-none hover:text-[#167A93] transition-colors"
                  >
                    <div className="flex items-center justify-end gap-1 w-full">
                      <span>Total Omzet</span>
                      {renderSortIcon('total_revenue')}
                    </div>
                  </th>

                  {/* Kolom TOTAL HPP (MODAL): Rata kanan presisi */}
                  <th
                    onClick={() => handleSort('total_cogs')}
                    className="py-3.5 px-4 text-right cursor-pointer group select-none hover:text-[#167A93] transition-colors"
                  >
                    <div className="flex items-center justify-end gap-1 w-full">
                      <span>Total HPP (Modal)</span>
                      {renderSortIcon('total_cogs')}
                    </div>
                  </th>

                  {/* Kolom LABA KOTOR: Rata kanan presisi */}
                  <th
                    onClick={() => handleSort('gross_profit')}
                    className="py-3.5 px-4 text-right cursor-pointer group select-none hover:text-[#167A93] transition-colors"
                  >
                    <div className="flex items-center justify-end gap-1 w-full">
                      <span>Laba Kotor</span>
                      {renderSortIcon('gross_profit')}
                    </div>
                  </th>

                  {/* Kolom MARGIN (%): Rata tengah presisi */}
                  <th
                    onClick={() => handleSort('profit_margin')}
                    className="py-3.5 px-4 text-center cursor-pointer group select-none hover:text-[#167A93] transition-colors"
                  >
                    <div className="flex items-center justify-center gap-1 w-full">
                      <span>Margin (%)</span>
                      {renderSortIcon('profit_margin')}
                    </div>
                  </th>
                </tr>
              </thead>

              <tbody className="divide-y divide-[#F1F4F5] text-xs">
                {sortedItems.map((item, idx) => {
                  const isPositive = Number(item.gross_profit) >= 0;
                  const margin = Number(item.profit_margin || 0);

                  return (
                    <tr
                      key={item.period_key || idx}
                      className="hover:bg-[#F8FAFA]/80 transition-colors"
                    >
                      {/* NO: Rata tengah */}
                      <td className="py-3 px-4 w-14 text-center text-[#879197] font-mono text-[11px]">
                        {idx + 1}
                      </td>

                      {/* TANGGAL TRANSAKSI / BULAN & TAHUN: Rata kiri */}
                      <td className="py-3 px-4 text-left">
                        <div className="font-semibold text-[#20282D]">{item.label}</div>
                        {item.sublabel && (
                          <div className="text-[10px] text-[#879197]">{item.sublabel}</div>
                        )}
                      </td>

                      {/* JML TRANSAKSI: Rata tengah presisi */}
                      <td className="py-3 px-4 text-center font-mono">
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#F1F4F5] text-[#577782]">
                          {Number(item.transaction_count).toLocaleString('id-ID')}
                        </span>
                      </td>

                      {/* TOTAL OMZET: Rata kanan presisi */}
                      <td className="py-3 px-4 text-right font-mono font-medium text-[#20282D]">
                        {formatRupiah(item.total_revenue)}
                      </td>

                      {/* TOTAL HPP (MODAL): Rata kanan presisi */}
                      <td className="py-3 px-4 text-right font-mono text-[#577782]">
                        {formatRupiah(item.total_cogs)}
                      </td>

                      {/* LABA KOTOR: Rata kanan presisi */}
                      <td className={`py-3 px-4 text-right font-mono font-bold ${isPositive ? 'text-[#1B8751]' : 'text-[#C53030]'}`}>
                        {formatRupiah(item.gross_profit)}
                      </td>

                      {/* MARGIN (%): Rata tengah presisi */}
                      <td className="py-3 px-4 text-center">
                        <span
                          className={`inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold font-mono ${
                            margin >= 25
                              ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                              : margin > 0
                              ? 'bg-cyan-50 text-cyan-700 border border-cyan-200'
                              : margin === 0
                              ? 'bg-gray-100 text-gray-600 border border-gray-200'
                              : 'bg-rose-50 text-rose-700 border border-rose-200'
                          }`}
                        >
                          {margin.toFixed(2)}%
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>

              {/* Baris Total di Bawah Tabel: TOTAL KESELURUHAN */}
              <tfoot>
                <tr className="bg-slate-50 border-t-2 border-[#E7ECEE] font-bold text-xs text-[#20282D]">
                  <td colSpan={2} className="py-3.5 px-4 uppercase tracking-wider text-left font-bold text-[#20282D]">
                    TOTAL KESELURUHAN
                  </td>
                  <td className="py-3.5 px-4 text-center font-mono font-bold text-[#20282D]">
                    {Number(summary.total_transactions).toLocaleString('id-ID')}
                  </td>
                  <td className="py-3.5 px-4 text-right font-mono font-bold text-[#167A93]">
                    {formatRupiah(summary.total_revenue)}
                  </td>
                  <td className="py-3.5 px-4 text-right font-mono font-bold text-[#577782]">
                    {formatRupiah(summary.total_cogs)}
                  </td>
                  <td className={`py-3.5 px-4 text-right font-mono font-bold ${summary.gross_profit >= 0 ? 'text-[#1B8751]' : 'text-[#C53030]'}`}>
                    {formatRupiah(summary.gross_profit)}
                  </td>
                  <td className="py-3.5 px-4 text-center font-mono font-bold text-[#167A93]">
                    {Number(summary.profit_margin).toFixed(2)}%
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
