import React, { useEffect, useState } from 'react';
import reportService from '../../services/reportService';
import { formatDateTime } from '../../utils/currency';

export default function StockMovementReportPage() {
  const today = new Date().toISOString().split('T')[0];
  const firstDayOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1)
    .toISOString()
    .split('T')[0];

  const [startDate, setStartDate] = useState(firstDayOfMonth);
  const [endDate, setEndDate] = useState(today);
  const [movementType, setMovementType] = useState('');
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [exportingPdf, setExportingPdf] = useState(false);
  const [exportingExcel, setExportingExcel] = useState(false);
  const [error, setError] = useState(null);

  const fetchReport = async () => {
    setLoading(true);
    setError(null);
    try {
      const params = {
        start_date: startDate,
        end_date: endDate,
      };
      if (movementType) params.movement_type = movementType;

      const res = await reportService.getStockMovements(params);
      setData(res.data);
    } catch (err) {
      console.error('Gagal mengambil Riwayat Mutasi:', err);
      setError('Gagal memuat riwayat mutasi persediaan.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReport();
  }, []);

  const handleFilterSubmit = (e) => {
    e.preventDefault();
    fetchReport();
  };

  const handleExportPdf = async () => {
    setExportingPdf(true);
    try {
      const params = { start_date: startDate, end_date: endDate };
      if (movementType) params.movement_type = movementType;
      await reportService.downloadStockMovementsPdf(params);
    } catch (err) {
      alert('Gagal mengunduh PDF Mutasi Stok.');
    } finally {
      setExportingPdf(false);
    }
  };

  const handleExportExcel = async () => {
    setExportingExcel(true);
    try {
      const params = { start_date: startDate, end_date: endDate };
      if (movementType) params.movement_type = movementType;
      await reportService.downloadStockMovementsExcel(params);
    } catch (err) {
      alert('Gagal mengunduh Excel Mutasi Stok.');
    } finally {
      setExportingExcel(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-400 border border-purple-500/30 mb-2">
              <span className="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
              Audit Trail Persediaan
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
              Laporan Riwayat Mutasi Persediaan
            </h1>
            <p className="mt-1 text-sm text-slate-400">
              Audit jejak pergerakan stok barang: stok masuk dari supplier, stok keluar operasional, dan penyesuaian opname toko.
            </p>
          </div>

          {/* Export Buttons */}
          <div className="flex items-center gap-2">
            <button
              onClick={handleExportPdf}
              disabled={exportingPdf || loading}
              className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-600/90 hover:bg-rose-600 text-white font-semibold text-xs transition-all shadow-lg shadow-rose-900/30 disabled:opacity-50 cursor-pointer"
            >
              <span>📄</span>
              {exportingPdf ? 'Mengunduh...' : 'Ekspor PDF'}
            </button>

            <button
              onClick={handleExportExcel}
              disabled={exportingExcel || loading}
              className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600/90 hover:bg-emerald-600 text-white font-semibold text-xs transition-all shadow-lg shadow-emerald-900/30 disabled:opacity-50 cursor-pointer"
            >
              <span>📊</span>
              {exportingExcel ? 'Mengunduh...' : 'Ekspor Excel'}
            </button>
          </div>
        </div>

        {/* Filter Bar */}
        <form
          onSubmit={handleFilterSubmit}
          className="mt-6 pt-4 border-t border-slate-700/80 flex flex-wrap items-end gap-3"
        >
          <div>
            <label className="block text-xs font-medium text-slate-400 mb-1">
              Dari Tanggal
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-purple-500"
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
              className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-purple-500"
            />
          </div>

          <div>
            <label className="block text-xs font-medium text-slate-400 mb-1">
              Tipe Pergerakan
            </label>
            <select
              value={movementType}
              onChange={(e) => setMovementType(e.target.value)}
              className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-purple-500"
            >
              <option value="">Semua Pergerakan</option>
              <option value="stock_in">Stok Masuk</option>
              <option value="stock_out">Stok Keluar</option>
              <option value="adjustment_in">Opname Bertambah</option>
              <option value="adjustment_out">Opname Berkurang</option>
            </select>
          </div>

          <button
            type="submit"
            className="px-4 py-2 rounded-xl text-xs font-semibold bg-purple-600 hover:bg-purple-500 text-white transition-all cursor-pointer"
          >
            Terapkan Filter
          </button>
        </form>
      </div>

      {/* Movement Table Card */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl">
        {loading ? (
          <div className="text-center py-16 text-slate-400">
            <span className="text-3xl animate-spin inline-block mb-3">⏳</span>
            <p className="text-sm">Memuat catatan audit mutasi stok...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-rose-400 text-sm">{error}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="border-b border-slate-700 text-slate-400 uppercase tracking-wider text-[10px]">
                  <th className="py-3 px-3">Tanggal</th>
                  <th className="py-3 px-3">Kode Mutasi</th>
                  <th className="py-3 px-3">Produk</th>
                  <th className="py-3 px-3 text-center">Tipe</th>
                  <th className="py-3 px-3 text-right">Kuantitas</th>
                  <th className="py-3 px-3">Keterangan / Alasan</th>
                  <th className="py-3 px-3">Petugas</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800">
                {data?.movements?.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="py-8 text-center text-slate-400">
                      Tidak ada pergerakan stok tercatat pada periode ini.
                    </td>
                  </tr>
                ) : (
                  data?.movements?.map((m) => (
                    <tr key={m.id} className="hover:bg-slate-700/30 transition-colors">
                      <td className="py-3 px-3 text-slate-400 whitespace-nowrap">{m.date}</td>
                      <td className="py-3 px-3 font-mono font-semibold text-slate-200 whitespace-nowrap">
                        {m.code}
                      </td>
                      <td className="py-3 px-3">
                        <div className="font-medium text-white">{m.product_name}</div>
                        <div className="text-[11px] font-mono text-slate-500">{m.sku}</div>
                      </td>
                      <td className="py-3 px-3 text-center whitespace-nowrap">
                        <span
                          className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-semibold ${
                            m.type === 'stock_in'
                              ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                              : m.type === 'stock_out'
                              ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                              : m.type === 'adjustment_in'
                              ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30'
                              : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'
                          }`}
                        >
                          {m.type_label}
                        </span>
                      </td>
                      <td
                        className={`py-3 px-3 text-right font-mono font-bold whitespace-nowrap ${
                          m.type === 'stock_in' || m.type === 'adjustment_in'
                            ? 'text-emerald-400'
                            : 'text-rose-400'
                        }`}
                      >
                        {m.type === 'stock_in' || m.type === 'adjustment_in' ? '+' : '-'}
                        {m.quantity} {m.unit}
                      </td>
                      <td className="py-3 px-3 text-slate-300 max-w-xs truncate">
                        {m.notes || '-'}
                      </td>
                      <td className="py-3 px-3 text-slate-400 whitespace-nowrap">
                        {m.creator || '-'}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
