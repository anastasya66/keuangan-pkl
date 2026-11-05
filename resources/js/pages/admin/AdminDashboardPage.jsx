import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import ExpenseDistributionChart from '../../components/charts/ExpenseDistributionChart';
import FinancialChart from '../../components/charts/FinancialChart';
import useAuth from '../../hooks/useAuth';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';
import {
  IconDashboard,
  IconBox,
  IconWallet,
  IconTrendingUp,
  IconTrendingDown,
  IconBalance,
  IconJournal,
  IconIncomeStatement,
} from '../../components/common/Icons';

export function AdminDashboardPage() {
  const { user } = useAuth();

  // Period filter state
  const [period, setPeriod] = useState('this_month');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [isCustomDate, setIsCustomDate] = useState(false);

  // Data states
  const [loading, setLoading] = useState(true);
  const [summary, setSummary] = useState(null);
  const [chartData, setChartData] = useState([]);
  const [distribution, setDistribution] = useState(null);
  const [activities, setActivities] = useState([]);
  const [inventorySummary, setInventorySummary] = useState(null);
  const [lowStock, setLowStock] = useState([]);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      const params = { period };
      if (period === 'custom' && startDate && endDate) {
        params.start_date = startDate;
        params.end_date = endDate;
      }

      const [sumRes, chartRes, distRes, actRes, invRes, lowRes] = await Promise.all([
        reportService.getFinancialSummary(params),
        reportService.getFinancialChart(params),
        reportService.getExpenseDistribution(params),
        reportService.getRecentActivities(8),
        reportService.getInventorySummary(),
        reportService.getLowStock(6),
      ]);

      setSummary(sumRes.data);
      setChartData(chartRes.data?.points || []);
      setDistribution(distRes.data);
      setActivities(actRes.data || []);
      setInventorySummary(invRes.data);
      setLowStock(lowRes.data || []);
    } catch (err) {
      console.error('Gagal memuat data dashboard:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDashboardData();
  }, [period]);

  const handlePeriodChange = (newPeriod) => {
    setPeriod(newPeriod);
    setIsCustomDate(newPeriod === 'custom');
  };

  const handleCustomFilterSubmit = (e) => {
    e.preventDefault();
    if (startDate && endDate) {
      loadDashboardData();
    }
  };

  return (
    <div className="space-y-6">
      {/* Welcome Banner */}
      <div className="bg-gradient-to-r from-white via-[#F8FDFF] to-[#EBF8FB] border border-[#D7EBEF] rounded-3xl p-6 sm:p-8 shadow-xs relative overflow-hidden">
        <div className="absolute right-0 top-0 w-80 h-80 bg-gradient-to-bl from-[#38BFE0]/10 to-transparent rounded-full blur-2xl pointer-events-none" />

        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
          <div>
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#E3F6FA] text-[#167A93] border border-[#38BFE0]/30 mb-3">
              <span className="w-1.5 h-1.5 rounded-full bg-[#27C5E5] animate-pulse"></span>
              Beranda Ringkasan Toko
            </div>
            <h1 className="text-2xl sm:text-3xl font-black text-[#10262E] tracking-tight">
              Halo, {user?.name}! 👋
            </h1>
            <p className="mt-1 text-xs sm:text-sm text-[#577782] max-w-2xl leading-relaxed">
              Yuk pantau arus uang kas, penjualan, dan stok barang tokomu hari ini dengan rapi.
            </p>
          </div>

          {/* Quick Action Shortcuts */}
          <div className="flex flex-wrap items-center gap-2.5">
            <Link
              to="/admin/products"
              className="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-white hover:bg-[#F3FBFC] border border-[#D7EBEF] text-[#16323B] font-semibold text-xs shadow-2xs transition-all cursor-pointer btn-press"
            >
              <IconBox className="w-4 h-4 text-[#27C5E5]" />
              <span>Katalog Barang</span>
            </Link>
            <Link
              to="/admin/reports/income-statement"
              className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-semibold text-xs shadow-md shadow-[#27C5E5]/20 transition-all cursor-pointer btn-press"
            >
              <IconIncomeStatement className="w-4 h-4 text-white" />
              <span>Cek Untung &amp; Rugi</span>
            </Link>
          </div>
        </div>

        {/* Period Selector Tabs */}
        <div className="mt-6 pt-5 border-t border-[#D7EBEF]/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div className="text-xs font-semibold text-[#577782]">
            Periode Tampilan: <span className="text-[#10262E] font-bold">{summary?.period?.label || 'Bulan Ini'}</span>
          </div>

          <div className="flex flex-wrap items-center gap-1.5">
            {[
              { key: 'today', label: 'Hari Ini' },
              { key: 'this_week', label: 'Minggu Ini' },
              { key: 'this_month', label: 'Bulan Ini' },
              { key: 'this_year', label: 'Tahun Ini' },
              { key: 'custom', label: 'Pilih Tanggal' },
            ].map((p) => (
              <button
                key={p.key}
                type="button"
                onClick={() => handlePeriodChange(p.key)}
                className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer btn-press ${
                  period === p.key
                    ? 'bg-[#27C5E5] text-white shadow-sm shadow-[#27C5E5]/30'
                    : 'bg-white hover:bg-[#F3FBFC] text-[#577782] border border-[#D7EBEF]'
                }`}
              >
                {p.label}
              </button>
            ))}
          </div>
        </div>

        {/* Custom Date Form */}
        {isCustomDate && (
          <form
            onSubmit={handleCustomFilterSubmit}
            className="mt-4 pt-4 border-t border-[#D7EBEF] flex flex-wrap items-end gap-3 animate-modal-pop"
          >
            <div>
              <label className="block text-[11px] font-semibold text-[#577782] mb-1">
                Mulai Tanggal
              </label>
              <input
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
                required
                className="bg-white border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:outline-none focus:border-[#38BFE0] input-focus-glow"
              />
            </div>
            <div>
              <label className="block text-[11px] font-semibold text-[#577782] mb-1">
                Sampai Tanggal
              </label>
              <input
                type="date"
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
                required
                className="bg-white border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:outline-none focus:border-[#38BFE0] input-focus-glow"
              />
            </div>
            <button
              type="submit"
              className="px-4 py-2 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#38BFE0] text-white transition-all cursor-pointer shadow-xs btn-press"
            >
              Tampilkan
            </button>
          </form>
        )}
      </div>

      {/* 4 Financial Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        {/* Card 1: Total Pemasukan */}
        <div className="bg-white border border-[#D7EBEF] rounded-3xl p-5 shadow-xs hover:border-[#36B878]/50 card-interactive group">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-[#577782] uppercase tracking-wider">
              Total Uang Masuk
            </span>
            <div className="w-9 h-9 rounded-xl bg-emerald-50 text-[#36B878] flex items-center justify-center border border-emerald-100">
              <IconTrendingUp className="w-4 h-4" />
            </div>
          </div>
          <div className="text-2xl font-black text-[#10262E] mt-3 font-mono">
            {loading ? '...' : formatRupiah(summary?.total_income || 0)}
          </div>
          <div className="flex items-center gap-1.5 mt-2 text-xs font-semibold text-[#36B878]">
            <span className="w-1.5 h-1.5 rounded-full bg-[#36B878]"></span>
            <span>Kas masuk tercatat</span>
          </div>
        </div>

        {/* Card 2: Total Pengeluaran */}
        <div className="bg-white border border-[#D7EBEF] rounded-3xl p-5 shadow-xs hover:border-[#E66D79]/50 card-interactive group">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-[#577782] uppercase tracking-wider">
              Total Uang Keluar
            </span>
            <div className="w-9 h-9 rounded-xl bg-rose-50 text-[#E66D79] flex items-center justify-center border border-rose-100">
              <IconTrendingDown className="w-4 h-4" />
            </div>
          </div>
          <div className="text-2xl font-black text-[#10262E] mt-3 font-mono">
            {loading ? '...' : formatRupiah(summary?.total_expense || 0)}
          </div>
          <div className="flex items-center gap-1.5 mt-2 text-xs font-semibold text-[#E66D79]">
            <span className="w-1.5 h-1.5 rounded-full bg-[#E66D79]"></span>
            <span>Biaya &amp; belanja toko</span>
          </div>
        </div>

        {/* Card 3: Saldo Kas & Bank */}
        <div className="bg-white border border-[#D7EBEF] rounded-3xl p-5 shadow-xs hover:border-[#38BFE0]/50 card-interactive group">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-[#577782] uppercase tracking-wider">
              Sisa Uang Kas &amp; Bank
            </span>
            <div className="w-9 h-9 rounded-xl bg-[#EAF8FA] text-[#27C5E5] flex items-center justify-center border border-[#D7EBEF]">
              <IconWallet className="w-4 h-4" />
            </div>
          </div>
          <div className="text-2xl font-black text-[#167A93] mt-3 font-mono">
            {loading ? '...' : formatRupiah(summary?.cash_balance || 0)}
          </div>
          <div className="flex items-center gap-1.5 mt-2 text-xs font-medium text-[#577782]">
            <span>Uang siap pakai saat ini</span>
          </div>
        </div>

        {/* Card 4: Total Produk & Persediaan */}
        <div className="bg-white border border-[#D7EBEF] rounded-3xl p-5 shadow-xs hover:border-[#38BFE0]/50 card-interactive group">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-[#577782] uppercase tracking-wider">
              Barang &amp; Sisa Stok
            </span>
            <div className="w-9 h-9 rounded-xl bg-[#F0FAFC] text-[#27C5E5] flex items-center justify-center border border-[#D7EBEF]">
              <IconBox className="w-4 h-4" />
            </div>
          </div>
          <div className="text-2xl font-black text-[#10262E] mt-3 font-mono">
            {loading ? '...' : `${inventorySummary?.total_products || 0} Barang`}
          </div>
          <div className="flex items-center justify-between mt-2 text-xs">
            <span className="text-[#577782]">{inventorySummary?.total_units || 0} unit fisik</span>
            <Link to="/admin/reports/inventory" className="text-[#167A93] font-bold hover:underline">
              Cek Stok &rarr;
            </Link>
          </div>
        </div>
      </div>

      {/* Accounting Equilibrium Banner */}
      <div className="bg-white border border-[#D7EBEF] rounded-3xl p-5 shadow-xs card-interactive">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-[#EAF8FA] text-[#27C5E5] flex items-center justify-center border border-[#D7EBEF]">
              <IconBalance className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-sm font-bold text-[#10262E]">
                Keseimbangan Saldo Keuangan Toko
              </h3>
              <p className="text-xs text-[#577782]">
                Prinsip Keseimbangan: Total Aset = Total Hutang Toko + Modal Pemilik
              </p>
            </div>
          </div>

          {summary && (
            <span
              className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border ${
                summary.is_equity_balanced
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-rose-50 text-rose-700 border-rose-200'
              }`}
            >
              <span
                className={`w-2 h-2 rounded-full ${
                  summary.is_equity_balanced ? 'bg-emerald-500' : 'bg-rose-500'
                }`}
              ></span>
              {summary.is_equity_balanced ? 'Saldo Pas & Seimbang (Balance)' : 'Belum Seimbang'}
            </span>
          )}
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div className="p-3.5 rounded-2xl bg-[#F8FDFF] border border-[#EBF5F8]">
            <div className="text-[11px] font-bold uppercase tracking-wider text-[#167A93]">Total Aset Toko</div>
            <div className="text-lg font-black font-mono text-[#10262E] mt-1">
              {loading ? '...' : formatRupiah(summary?.total_assets || 0)}
            </div>
            <div className="text-[11px] text-[#8EA6AF] mt-0.5">Uang kas, rekening bank, &amp; nilai barang</div>
          </div>

          <div className="p-3.5 rounded-2xl bg-[#F8FDFF] border border-[#EBF5F8]">
            <div className="text-[11px] font-bold uppercase tracking-wider text-[#F0B75A]">Total Hutang (Kewajiban)</div>
            <div className="text-lg font-black font-mono text-[#10262E] mt-1">
              {loading ? '...' : formatRupiah(summary?.total_liabilities || 0)}
            </div>
            <div className="text-[11px] text-[#8EA6AF] mt-0.5">Hutang ke supplier &amp; operasional</div>
          </div>

          <div className="p-3.5 rounded-2xl bg-[#F8FDFF] border border-[#EBF5F8]">
            <div className="text-[11px] font-bold uppercase tracking-wider text-[#27C5E5]">Total Modal (Ekuitas)</div>
            <div className="text-lg font-black font-mono text-[#10262E] mt-1">
              {loading ? '...' : formatRupiah(summary?.total_equity || 0)}
            </div>
            <div className="text-[11px] text-[#8EA6AF] mt-0.5">Modal awal + laba bersih toko</div>
          </div>
        </div>
      </div>

      {/* Financial Analytics: Cash Flow Chart & Expense Distribution */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <FinancialChart points={chartData} title="Grafik Arus Uang (Masuk vs Keluar)" />
        </div>
        <div className="lg:col-span-1">
          <ExpenseDistributionChart distribution={distribution} />
        </div>
      </div>

      {/* Inventory Overview & Low Stock Warnings */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Inventory Valuation Card */}
        <div className="bg-white border border-[#D7EBEF] rounded-3xl p-6 shadow-xs lg:col-span-1 flex flex-col justify-between card-interactive">
          <div>
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-sm font-bold text-[#10262E] flex items-center gap-2">
                <IconBox className="w-4 h-4 text-[#27C5E5]" />
                <span>Ringkasan Stok Barang</span>
              </h3>
              <Link
                to="/admin/reports/inventory"
                className="text-xs text-[#167A93] hover:text-[#38BFE0] font-bold btn-press"
              >
                Lengkap &rarr;
              </Link>
            </div>

            <div className="space-y-2.5">
              <div className="flex items-center justify-between p-3 rounded-2xl bg-[#F8FDFF] border border-[#EBF5F8]">
                <span className="text-xs text-[#577782]">Total Jenis Barang</span>
                <span className="text-xs font-bold font-mono text-[#10262E]">
                  {inventorySummary?.total_products || 0} Barang
                </span>
              </div>

              <div className="flex items-center justify-between p-3 rounded-2xl bg-[#F8FDFF] border border-[#EBF5F8]">
                <span className="text-xs text-[#577782]">Total Kuantitas Fisik</span>
                <span className="text-xs font-bold font-mono text-[#10262E]">
                  {inventorySummary?.total_units || 0} unit
                </span>
              </div>

              <div className="flex items-center justify-between p-3 rounded-2xl bg-[#F8FDFF] border border-[#EBF5F8]">
                <span className="text-xs text-[#577782]">Nilai Aset Modal Barang</span>
                <span className="text-xs font-bold font-mono text-[#36B878]">
                  {formatRupiah(inventorySummary?.total_valuation || 0)}
                </span>
              </div>
            </div>
          </div>

          <div className="mt-4 pt-4 border-t border-[#EBF5F8] grid grid-cols-3 gap-2 text-center">
            <div className="p-2 rounded-xl bg-emerald-50 border border-emerald-100">
              <div className="text-xs font-black text-emerald-700">
                {inventorySummary?.available_count || 0}
              </div>
              <div className="text-[10px] text-[#577782] font-medium">Stok Aman</div>
            </div>
            <div className="p-2 rounded-xl bg-amber-50 border border-amber-100">
              <div className="text-xs font-black text-amber-700">
                {inventorySummary?.low_stock_count || 0}
              </div>
              <div className="text-[10px] text-[#577782] font-medium">Menipis</div>
            </div>
            <div className="p-2 rounded-xl bg-rose-50 border border-rose-100">
              <div className="text-xs font-black text-rose-700">
                {inventorySummary?.out_of_stock_count || 0}
              </div>
              <div className="text-[10px] text-[#577782] font-medium">Habis</div>
            </div>
          </div>
        </div>

        {/* Low Stock Warning Table */}
        <div className="bg-white border border-[#D7EBEF] rounded-3xl p-6 shadow-xs lg:col-span-2">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-bold text-[#10262E] flex items-center gap-2">
              <span className="p-1 rounded-md bg-amber-50 text-amber-600">⚠️</span>
              <span>Barang yang Perlu Ditambah (Stok Menipis)</span>
            </h3>
            <span className="text-xs text-[#8EA6AF]">Batas Menipis: &le; 10 unit</span>
          </div>

          {lowStock.length === 0 ? (
            <div className="text-center py-10 text-[#8EA6AF] text-xs">
              🎉 Bagus! Semua stok barang masih aman di atas 10 unit.
            </div>
          ) : (
            <div className="overflow-x-auto no-scrollbar">
              <table className="w-full text-left text-xs">
                <thead>
                  <tr className="border-b border-[#D7EBEF] text-[#8EA6AF] uppercase tracking-wider text-[10px]">
                    <th className="py-2.5 px-3">Kode SKU</th>
                    <th className="py-2.5 px-3">Nama Barang</th>
                    <th className="py-2.5 px-3">Kategori</th>
                    <th className="py-2.5 px-3 text-right">Sisa Stok</th>
                    <th className="py-2.5 px-3 text-center">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#EBF5F8]">
                  {lowStock.map((prod) => (
                    <tr key={prod.id} className="hover:bg-[#F3FBFC] transition-colors">
                      <td className="py-2.5 px-3 font-mono font-bold text-[#16323B]">
                        {prod.sku}
                      </td>
                      <td className="py-2.5 px-3 font-medium text-[#10262E]">{prod.name}</td>
                      <td className="py-2.5 px-3 text-[#577782]">{prod.category || '-'}</td>
                      <td className="py-2.5 px-3 text-right font-mono font-bold text-[#E66D79]">
                        {prod.stock} {prod.unit}
                      </td>
                      <td className="py-2.5 px-3 text-center">
                        <span
                          className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                            prod.stock <= 0
                              ? 'bg-rose-50 text-rose-700 border border-rose-200'
                              : 'bg-amber-50 text-amber-700 border border-amber-200'
                          }`}
                        >
                          {prod.status_label || (prod.stock <= 0 ? 'Habis' : 'Menipis')}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Recent Transactions Table */}
      <div className="bg-white border border-[#D7EBEF] rounded-3xl p-6 shadow-xs">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h3 className="text-sm font-bold text-[#10262E] flex items-center gap-2">
              <IconJournal className="w-4 h-4 text-[#27C5E5]" />
              <span>Catatan Transaksi Terkini</span>
            </h3>
            <p className="text-xs text-[#577782] mt-0.5">
              5 transaksi terakhir yang tercatat di toko Anda
            </p>
          </div>
          <Link
            to="/admin/reports/income-statement"
            className="text-xs font-bold text-[#167A93] hover:text-[#38BFE0] btn-press"
          >
            Lihat Semua Transaksi &rarr;
          </Link>
        </div>

        {activities.length === 0 ? (
          <div className="text-center py-8 text-[#8EA6AF] text-xs">
            Belum ada aktivitas transaksi yang tercatat.
          </div>
        ) : (
          <div className="divide-y divide-[#EBF5F8]">
            {activities.slice(0, 5).map((act) => (
              <div
                key={act.id}
                className="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-[#F3FBFC] px-3 rounded-2xl transition-colors card-interactive"
              >
                <div className="flex items-center gap-3">
                  <div className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border ${
                    act.type === 'income'
                      ? 'bg-emerald-50 text-[#36B878] border-emerald-100'
                      : act.type === 'expense'
                      ? 'bg-rose-50 text-[#E66D79] border-rose-100'
                      : 'bg-[#F0FAFC] text-[#27C5E5] border-[#D7EBEF]'
                  }`}>
                    {act.type === 'income' ? (
                      <IconTrendingUp className="w-4 h-4" />
                    ) : act.type === 'expense' ? (
                      <IconTrendingDown className="w-4 h-4" />
                    ) : (
                      <IconBox className="w-4 h-4" />
                    )}
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-xs font-bold text-[#10262E]">{act.title}</span>
                      <span
                        className={`text-[10px] px-2 py-0.5 rounded-full font-bold border ${
                          act.type === 'income'
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : act.type === 'expense'
                            ? 'bg-rose-50 text-rose-700 border-rose-200'
                            : 'bg-cyan-50 text-cyan-700 border-cyan-200'
                        }`}
                      >
                        {act.type_label}
                      </span>
                    </div>
                    <p className="text-xs text-[#577782] mt-0.5">{act.description}</p>
                  </div>
                </div>

                <div className="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center text-xs">
                  {act.amount !== null && (
                    <span
                      className={`font-mono font-bold ${
                        act.type === 'income'
                          ? 'text-[#36B878]'
                          : act.type === 'expense'
                          ? 'text-[#E66D79]'
                          : 'text-[#10262E]'
                      }`}
                    >
                      {formatRupiah(act.amount)}
                    </span>
                  )}
                  <span className="text-[11px] text-[#8EA6AF]">{act.date}</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

export default AdminDashboardPage;
