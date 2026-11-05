import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import ExpenseDistributionChart from '../../components/charts/ExpenseDistributionChart';
import FinancialChart from '../../components/charts/FinancialChart';
import useAuth from '../../hooks/useAuth';
import reportService from '../../services/reportService';
import { formatDateTime, formatRupiah } from '../../utils/currency';

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
    <div className="space-y-8">
      {/* Header & Controls */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl">
        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 mb-2">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              Level Akses: Administrator
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
              Dashboard Keuangan & Operasional
            </h1>
            <p className="mt-1 text-sm text-slate-400">
              Pantau arus kas, laba rugi akuntansi, neraca keseimbangan, dan persediaan toko secara realtime.
            </p>
          </div>

          {/* Period Selector Buttons */}
          <div className="flex flex-wrap items-center gap-2">
            {[
              { key: 'today', label: 'Hari Ini' },
              { key: 'this_week', label: 'Minggu Ini' },
              { key: 'this_month', label: 'Bulan Ini' },
              { key: 'this_year', label: 'Tahun Ini' },
              { key: 'custom', label: 'Kustom' },
            ].map((p) => (
              <button
                key={p.key}
                type="button"
                onClick={() => handlePeriodChange(p.key)}
                className={`px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer ${
                  period === p.key
                    ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/40 border border-emerald-500'
                    : 'bg-slate-700/60 text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-600/60'
                }`}
              >
                {p.label}
              </button>
            ))}
          </div>
        </div>

        {/* Custom Date Filter Panel */}
        {isCustomDate && (
          <form
            onSubmit={handleCustomFilterSubmit}
            className="mt-4 pt-4 border-t border-slate-700/80 flex flex-wrap items-end gap-3"
          >
            <div>
              <label className="block text-xs font-medium text-slate-400 mb-1">
                Dari Tanggal
              </label>
              <input
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
                required
                className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-400 mb-1">
                Sampai Tanggal
              </label>
              <input
                type="date"
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
                required
                className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
            <button
              type="submit"
              className="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition-all cursor-pointer"
            >
              Terapkan Filter
            </button>
          </form>
        )}
      </div>

      {/* KPI Financial Cards (Row 1: Operasional & Laba Bersih) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {/* Pemasukan */}
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl relative overflow-hidden group">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
              Total Pemasukan
            </span>
            <span className="text-xl">💰</span>
          </div>
          <div className="text-2xl font-black text-emerald-400 mt-2 font-mono">
            {loading ? '...' : formatRupiah(summary?.total_income || 0)}
          </div>
          <p className="text-[11px] text-slate-400 mt-1">
            Arus kas masuk ({summary?.period?.label || 'Periode terpilih'})
          </p>
        </div>

        {/* Pengeluaran */}
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl relative overflow-hidden group">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
              Total Pengeluaran
            </span>
            <span className="text-xl">💸</span>
          </div>
          <div className="text-2xl font-black text-rose-400 mt-2 font-mono">
            {loading ? '...' : formatRupiah(summary?.total_expense || 0)}
          </div>
          <p className="text-[11px] text-slate-400 mt-1">
            Biaya & pengeluaran operasional
          </p>
        </div>

        {/* Laba Bersih Akuntansi */}
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl relative overflow-hidden group">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
              Laba Bersih Akuntansi
            </span>
            <span className="text-xl">📈</span>
          </div>
          <div
            className={`text-2xl font-black mt-2 font-mono ${
              (summary?.net_profit || 0) >= 0 ? 'text-teal-300' : 'text-rose-400'
            }`}
          >
            {loading ? '...' : formatRupiah(summary?.net_profit || 0)}
          </div>
          <p className="text-[11px] text-slate-400 mt-1">
            Pendapatan - Beban (Jurnal Terposting)
          </p>
        </div>

        {/* Saldo Kas & Bank */}
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl relative overflow-hidden group">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
              Saldo Kas & Bank
            </span>
            <span className="text-xl">🏦</span>
          </div>
          <div className="text-2xl font-black text-cyan-400 mt-2 font-mono">
            {loading ? '...' : formatRupiah(summary?.cash_balance || 0)}
          </div>
          <p className="text-[11px] text-slate-400 mt-1">
            Likuiditas kas aktual per {summary?.period?.end_date || 'hari ini'}
          </p>
        </div>
      </div>

      {/* KPI Neraca Keseimbangan (Row 2: Neraca Akuntansi) */}
      <div className="bg-slate-850/60 border border-slate-800 rounded-2xl p-5 backdrop-blur">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
          <div className="flex items-center gap-2">
            <span className="text-lg">⚖️</span>
            <div>
              <h3 className="text-sm font-bold text-white">
                Posisi Keuangan & Persamaan Akuntansi (Neraca)
              </h3>
              <p className="text-xs text-slate-400">
                Prinsip Keseimbangan: Aset = Kewajiban + Ekuitas
              </p>
            </div>
          </div>
          {summary && (
            <span
              className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border ${
                summary.is_equity_balanced
                  ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
                  : 'bg-rose-500/20 text-rose-400 border-rose-500/30'
              }`}
            >
              <span
                className={`w-2 h-2 rounded-full ${
                  summary.is_equity_balanced ? 'bg-emerald-400' : 'bg-rose-400'
                }`}
              ></span>
              {summary.is_equity_balanced ? 'Neraca Seimbang (Equilibrium)' : 'Belum Seimbang'}
            </span>
          )}
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="bg-slate-900/60 border border-slate-800 p-4 rounded-xl">
            <div className="text-xs text-indigo-400 font-semibold uppercase">Total Aset</div>
            <div className="text-xl font-bold font-mono text-white mt-1">
              {loading ? '...' : formatRupiah(summary?.total_assets || 0)}
            </div>
            <div className="text-[11px] text-slate-400 mt-0.5">Kas, Piutang, & Persediaan</div>
          </div>

          <div className="bg-slate-900/60 border border-slate-800 p-4 rounded-xl">
            <div className="text-xs text-amber-400 font-semibold uppercase">Total Kewajiban (Hutang)</div>
            <div className="text-xl font-bold font-mono text-white mt-1">
              {loading ? '...' : formatRupiah(summary?.total_liabilities || 0)}
            </div>
            <div className="text-[11px] text-slate-400 mt-0.5">Hutang Usaha & Operasional</div>
          </div>

          <div className="bg-slate-900/60 border border-slate-800 p-4 rounded-xl">
            <div className="text-xs text-purple-400 font-semibold uppercase">Total Ekuitas (Modal)</div>
            <div className="text-xl font-bold font-mono text-white mt-1">
              {loading ? '...' : formatRupiah(summary?.total_equity || 0)}
            </div>
            <div className="text-[11px] text-slate-400 mt-0.5">Modal Pemilik + Laba Berjalan</div>
          </div>
        </div>
      </div>

      {/* Financial Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <FinancialChart points={chartData} title="Tren Pemasukan vs Pengeluaran" />
        </div>
        <div className="lg:col-span-1">
          <ExpenseDistributionChart distribution={distribution} />
        </div>
      </div>

      {/* Inventory & Low Stock Alerts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Inventory Summary Cards */}
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl lg:col-span-1 flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-base font-bold text-white flex items-center gap-2">
                <span>📦</span> Ringkasan Persediaan
              </h3>
              <Link
                to="/admin/reports/inventory"
                className="text-xs text-emerald-400 hover:text-emerald-300 font-semibold"
              >
                Laporan &rarr;
              </Link>
            </div>

            <div className="space-y-3">
              <div className="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                <span className="text-xs text-slate-300">Total Varian Produk</span>
                <span className="text-sm font-bold text-white font-mono">
                  {inventorySummary?.total_products || 0} SKU
                </span>
              </div>

              <div className="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                <span className="text-xs text-slate-300">Total Kuantitas Fisik</span>
                <span className="text-sm font-bold text-white font-mono">
                  {inventorySummary?.total_units || 0} unit
                </span>
              </div>

              <div className="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                <span className="text-xs text-slate-300">Valuasi Persediaan (Cost)</span>
                <span className="text-sm font-bold text-emerald-400 font-mono">
                  {formatRupiah(inventorySummary?.total_valuation || 0)}
                </span>
              </div>
            </div>
          </div>

          <div className="mt-4 pt-4 border-t border-slate-700/80 grid grid-cols-3 gap-2 text-center">
            <div className="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
              <div className="text-xs font-bold text-emerald-400">
                {inventorySummary?.available_count || 0}
              </div>
              <div className="text-[10px] text-slate-400">Stok Aman</div>
            </div>
            <div className="p-2 rounded-lg bg-amber-500/10 border border-amber-500/20">
              <div className="text-xs font-bold text-amber-400">
                {inventorySummary?.low_stock_count || 0}
              </div>
              <div className="text-[10px] text-slate-400">Menipis</div>
            </div>
            <div className="p-2 rounded-lg bg-rose-500/10 border border-rose-500/20">
              <div className="text-xs font-bold text-rose-400">
                {inventorySummary?.out_of_stock_count || 0}
              </div>
              <div className="text-[10px] text-slate-400">Habis</div>
            </div>
          </div>
        </div>

        {/* Low Stock Warning Table */}
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl lg:col-span-2">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-base font-bold text-white flex items-center gap-2">
              <span>⚠️</span> Peringatan Stok Menipis & Habis
            </h3>
            <span className="text-xs text-slate-400">Ambang Batas: &le; 10 unit</span>
          </div>

          {lowStock.length === 0 ? (
            <div className="text-center py-10 text-slate-400 text-xs">
              🎉 Semua stok produk dalam batas aman (&gt; 10 unit).
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead>
                  <tr className="border-b border-slate-700 text-slate-400 uppercase tracking-wider text-[10px]">
                    <th className="py-2.5 px-3">SKU</th>
                    <th className="py-2.5 px-3">Nama Produk</th>
                    <th className="py-2.5 px-3">Kategori</th>
                    <th className="py-2.5 px-3 text-right">Sisa Stok</th>
                    <th className="py-2.5 px-3 text-center">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800">
                  {lowStock.map((prod) => (
                    <tr key={prod.id} className="hover:bg-slate-700/40 transition-colors">
                      <td className="py-2.5 px-3 font-mono font-semibold text-slate-300">
                        {prod.sku}
                      </td>
                      <td className="py-2.5 px-3 font-medium text-white">{prod.name}</td>
                      <td className="py-2.5 px-3 text-slate-400">{prod.category || '-'}</td>
                      <td className="py-2.5 px-3 text-right font-mono font-bold text-rose-400">
                        {prod.stock} {prod.unit}
                      </td>
                      <td className="py-2.5 px-3 text-center">
                        <span
                          className={`inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold ${
                            prod.stock <= 0
                              ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                              : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'
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

      {/* Recent Activities Timeline */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-base font-bold text-white flex items-center gap-2">
            <span>⏱️</span> Aktivitas Transaksi Terkini
          </h3>
          <span className="text-xs text-slate-400">
            Transaksi Keuangan, Mutasi Stok, dan Jurnal Akuntansi
          </span>
        </div>

        {activities.length === 0 ? (
          <div className="text-center py-8 text-slate-400 text-xs">
            Belum ada aktivitas transaksi tercatat.
          </div>
        ) : (
          <div className="divide-y divide-slate-800">
            {activities.map((act) => (
              <div
                key={act.id}
                className="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-slate-700/20 px-2 rounded-xl transition-colors"
              >
                <div className="flex items-start gap-3">
                  <span className="text-lg mt-0.5">
                    {act.type === 'income'
                      ? '📥'
                      : act.type === 'expense'
                      ? '📤'
                      : act.type === 'inventory'
                      ? '📦'
                      : '📑'}
                  </span>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-xs font-bold text-white">{act.title}</span>
                      <span
                        className={`text-[10px] px-2 py-0.5 rounded-full font-semibold ${
                          act.type === 'income'
                            ? 'bg-emerald-500/20 text-emerald-400'
                            : act.type === 'expense'
                            ? 'bg-rose-500/20 text-rose-400'
                            : act.type === 'inventory'
                            ? 'bg-amber-500/20 text-amber-400'
                            : 'bg-indigo-500/20 text-indigo-400'
                        }`}
                      >
                        {act.type_label}
                      </span>
                    </div>
                    <p className="text-xs text-slate-400 mt-0.5">{act.description}</p>
                  </div>
                </div>

                <div className="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center text-xs">
                  {act.amount !== null && (
                    <span
                      className={`font-mono font-bold ${
                        act.type === 'income'
                          ? 'text-emerald-400'
                          : act.type === 'expense'
                          ? 'text-rose-400'
                          : 'text-slate-200'
                      }`}
                    >
                      {formatRupiah(act.amount)}
                    </span>
                  )}
                  <span className="text-[11px] text-slate-500">{act.date}</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Reports Quick Link Bar */}
      <div className="bg-gradient-to-r from-slate-900 via-slate-850 to-slate-900 border border-slate-800 rounded-2xl p-6">
        <h3 className="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">
          📊 Akses Laporan Toko (Tahap 7)
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <Link
            to="/admin/reports/income-statement"
            className="p-4 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700 hover:border-emerald-500/50 transition-all group"
          >
            <div className="text-xl mb-1">📑</div>
            <div className="text-sm font-bold text-white group-hover:text-emerald-400 transition-colors">
              Laporan Laba Rugi
            </div>
            <p className="text-xs text-slate-400 mt-1">
              Pendapatan, beban, dan laba bersih toko dengan ekspor PDF & Excel.
            </p>
          </Link>

          <Link
            to="/admin/reports/balance-sheet"
            className="p-4 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700 hover:border-cyan-500/50 transition-all group"
          >
            <div className="text-xl mb-1">⚖️</div>
            <div className="text-sm font-bold text-white group-hover:text-cyan-400 transition-colors">
              Laporan Neraca
            </div>
            <p className="text-xs text-slate-400 mt-1">
              Aset, kewajiban, modal ekuitas, dan cek keseimbangan akuntansi.
            </p>
          </Link>

          <Link
            to="/admin/reports/inventory"
            className="p-4 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700 hover:border-amber-500/50 transition-all group"
          >
            <div className="text-xl mb-1">📦</div>
            <div className="text-sm font-bold text-white group-hover:text-amber-400 transition-colors">
              Laporan Stok & Valuasi
            </div>
            <p className="text-xs text-slate-400 mt-1">
              Kuantitas fisik, status stok, dan nilai valuasi aset barang.
            </p>
          </Link>

          <Link
            to="/admin/reports/stock-movements"
            className="p-4 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700 hover:border-purple-500/50 transition-all group"
          >
            <div className="text-xl mb-1">🔄</div>
            <div className="text-sm font-bold text-white group-hover:text-purple-400 transition-colors">
              Riwayat Mutasi Stok
            </div>
            <p className="text-xs text-slate-400 mt-1">
              Audit trail stok masuk, stok keluar, dan opname persediaan.
            </p>
          </Link>
        </div>
      </div>
    </div>
  );
}

export default AdminDashboardPage;
