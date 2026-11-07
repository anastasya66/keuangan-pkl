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
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-[#16323B]">
              Laporan Riwayat Mutasi Persediaan
            </h1>
            <p className="mt-1 text-sm text-[#5A7C85]">
              Audit jejak pergerakan stok barang: stok masuk dari supplier, stok keluar operasional, dan penyesuaian opname toko.
            </p>
          </div>

          {/* Export Buttons */}
          <div className="flex items-center gap-2">
            <button
              onClick={handleExportPdf}
              disabled={exportingPdf || loading}
              className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-xs transition-all shadow-xs disabled:opacity-50 cursor-pointer"
            >
              <span>📄</span>
              {exportingPdf ? 'Mengunduh...' : 'Ekspor PDF'}
            </button>

            <button
              onClick={handleExportExcel}
              disabled={exportingExcel || loading}
              className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-semibold text-xs transition-all shadow-xs disabled:opacity-50 cursor-pointer"
            >
              <span>📊</span>
              {exportingExcel ? 'Mengunduh...' : 'Ekspor Excel'}
            </button>
          </div>
        </div>

        {/* Filter Bar */}
        <form
          onSubmit={handleFilterSubmit}
          className="mt-6 pt-4 border-t border-[#D7EBEF] flex flex-wrap items-end gap-3"
        >
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1">
              Dari Tanggal
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1">
              Sampai Tanggal
            </label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1">
              Tipe Pergerakan
            </label>
            <select
              value={movementType}
              onChange={(e) => setMovementType(e.target.value)}
              className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
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
            className="px-4 py-2 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#38BFE0] text-white transition-all cursor-pointer shadow-md shadow-[#27C5E5]/20"
          >
            Terapkan Filter
          </button>
        </form>
      </div>

      {/* Movement Table Card */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs">
        {loading ? (
          <div className="text-center py-16 text-[#5A7C85]">
            <div className="inline-block w-8 h-8 border-2 border-[#27C5E5] border-t-transparent rounded-full animate-spin mb-3"></div>
            <p className="text-sm">Memuat catatan audit mutasi stok...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-rose-600 font-semibold text-sm">{error}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-[#F3FBFC] text-[#5A7C85] font-semibold border-b border-[#D7EBEF]">
                <tr className="uppercase tracking-wider text-[10px]">
                  <th className="py-3 px-3">Tanggal</th>
                  <th className="py-3 px-3">Kode Mutasi</th>
                  <th className="py-3 px-3">Produk</th>
                  <th className="py-3 px-3 text-center">Tipe</th>
                  <th className="py-3 px-3 text-right">Kuantitas</th>
                  <th className="py-3 px-3">Keterangan / Alasan</th>
                  <th className="py-3 px-3">Petugas</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#D7EBEF]/60">
                {data?.movements?.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="py-8 text-center text-[#5A7C85]">
                      Tidak ada pergerakan stok tercatat pada periode ini.
                    </td>
                  </tr>
                ) : (
                  data?.movements?.map((m) => (
                    <tr key={m.id} className="hover:bg-[#F3FBFC]/50 transition-colors">
                      <td className="py-3 px-3 text-[#5A7C85] whitespace-nowrap">{m.date}</td>
                      <td className="py-3 px-3 font-mono font-semibold text-[#27C5E5] whitespace-nowrap">
                        {m.code}
                      </td>
                      <td className="py-3 px-3">
                        <div className="font-semibold text-[#16323B]">{m.product_name}</div>
                        <div className="text-[11px] font-mono text-[#5A7C85]">{m.sku}</div>
                      </td>
                      <td className="py-3 px-3 text-center whitespace-nowrap">
                        <span
                          className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-xs whitespace-nowrap ${
                            m.type === 'stock_in'
                              ? 'bg-emerald-600 text-white'
                              : m.type === 'stock_out'
                              ? 'bg-rose-600 text-white'
                              : m.type === 'adjustment_in'
                              ? 'bg-teal-600 text-white'
                              : 'bg-amber-600 text-white'
                          }`}
                        >
                          {m.type_label}
                        </span>
                      </td>
                      <td
                        className={`py-3 px-3 text-right font-mono font-bold whitespace-nowrap ${
                          m.type === 'stock_in' || m.type === 'adjustment_in'
                            ? 'text-[#36B878]'
                            : 'text-[#E66D79]'
                        }`}
                      >
                        {m.type === 'stock_in' || m.type === 'adjustment_in' ? '+' : '-'}
                        {m.quantity} {m.unit}
                      </td>
                      <td className="py-3 px-3 text-[#16323B] max-w-xs truncate">
                        {m.notes || '-'}
                      </td>
                      <td className="py-3 px-3 text-[#5A7C85] whitespace-nowrap">
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
