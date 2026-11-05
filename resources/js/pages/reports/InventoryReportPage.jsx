import React, { useEffect, useState } from 'react';
import categoryService from '../../services/categoryService';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';

export default function InventoryReportPage() {
  const [categories, setCategories] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [exportingPdf, setExportingPdf] = useState(false);
  const [exportingExcel, setExportingExcel] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function loadCategories() {
      try {
        const res = await categoryService.getCategories({ per_page: 100 });
        setCategories(res.data || []);
      } catch (err) {
        console.error('Gagal memuat kategori:', err);
      }
    }
    loadCategories();
  }, []);

  const fetchReport = async () => {
    setLoading(true);
    setError(null);
    try {
      const params = {};
      if (selectedCategory) params.category_id = selectedCategory;
      if (selectedStatus) params.status = selectedStatus;

      const res = await reportService.getInventoryReport(params);
      setData(res.data);
    } catch (err) {
      console.error('Gagal mengambil Laporan Stok:', err);
      setError('Gagal memuat laporan stok.');
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
      const params = {};
      if (selectedCategory) params.category_id = selectedCategory;
      if (selectedStatus) params.status = selectedStatus;
      await reportService.downloadInventoryReportPdf(params);
    } catch (err) {
      alert('Gagal mengunduh PDF Stok Persediaan.');
    } finally {
      setExportingPdf(false);
    }
  };

  const handleExportExcel = async () => {
    setExportingExcel(true);
    try {
      const params = {};
      if (selectedCategory) params.category_id = selectedCategory;
      if (selectedStatus) params.status = selectedStatus;
      await reportService.downloadInventoryReportExcel(params);
    } catch (err) {
      alert('Gagal mengunduh Excel Stok Persediaan.');
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
            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30 mb-2">
              <span className="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
              Manajemen Persediaan & Valuasi
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
              Laporan Stok & Valuasi Persediaan
            </h1>
            <p className="mt-1 text-sm text-slate-400">
              Menampilkan kuantitas fisik persediaan, harga perolehan (cost price), status ketersediaan, dan total nilai aset barang.
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
              Filter Kategori
            </label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500"
            >
              <option value="">Semua Kategori</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-xs font-medium text-slate-400 mb-1">
              Status Ketersediaan
            </label>
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value)}
              className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500"
            >
              <option value="">Semua Status</option>
              <option value="available">Stok Aman (&gt; 10)</option>
              <option value="low_stock">Stok Menipis (&le; 10)</option>
              <option value="out_of_stock">Stok Habis (= 0)</option>
            </select>
          </div>

          <button
            type="submit"
            className="px-4 py-2 rounded-xl text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-white transition-all cursor-pointer"
          >
            Terapkan Filter
          </button>
        </form>
      </div>

      {/* KPI Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl">
          <div className="text-xs font-semibold text-slate-400 uppercase">Total SKU Terdaftar</div>
          <div className="text-2xl font-bold font-mono text-white mt-2">
            {loading ? '...' : (data?.summary?.total_products || 0) + ' Produk'}
          </div>
          <div className="text-[11px] text-slate-400 mt-1">Item aktif di etalase/gudang</div>
        </div>

        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl">
          <div className="text-xs font-semibold text-slate-400 uppercase">Total Kuantitas Fisik</div>
          <div className="text-2xl font-bold font-mono text-cyan-400 mt-2">
            {loading ? '...' : (data?.summary?.total_units || 0) + ' Unit'}
          </div>
          <div className="text-[11px] text-slate-400 mt-1">Akumulasi seluruh stok barang</div>
        </div>

        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl">
          <div className="text-xs font-semibold text-slate-400 uppercase">Valuasi Persediaan (Cost)</div>
          <div className="text-2xl font-bold font-mono text-emerald-400 mt-2">
            {loading ? '...' : formatRupiah(data?.summary?.total_valuation || 0)}
          </div>
          <div className="text-[11px] text-slate-400 mt-1">Kuantitas &times; Harga Beli</div>
        </div>
      </div>

      {/* Product Stock Table Card */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl">
        {loading ? (
          <div className="text-center py-16 text-slate-400">
            <span className="text-3xl animate-spin inline-block mb-3">⏳</span>
            <p className="text-sm">Memuat data inventaris stok...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-rose-400 text-sm">{error}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="border-b border-slate-700 text-slate-400 uppercase tracking-wider text-[10px]">
                  <th className="py-3 px-3">No</th>
                  <th className="py-3 px-3">SKU</th>
                  <th className="py-3 px-3">Nama Produk</th>
                  <th className="py-3 px-3">Kategori</th>
                  <th className="py-3 px-3 text-right">Stok Fisik</th>
                  <th className="py-3 px-3 text-right">Harga Beli (Cost)</th>
                  <th className="py-3 px-3 text-right">Nilai Valuasi</th>
                  <th className="py-3 px-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800">
                {data?.items?.length === 0 ? (
                  <tr>
                    <td colSpan="8" className="py-8 text-center text-slate-400">
                      Tidak ada produk ditemukan sesuai kriteria filter.
                    </td>
                  </tr>
                ) : (
                  data?.items?.map((item, idx) => (
                    <tr key={item.id} className="hover:bg-slate-700/30 transition-colors">
                      <td className="py-3 px-3 text-slate-500">{idx + 1}</td>
                      <td className="py-3 px-3 font-mono font-semibold text-slate-300">{item.sku}</td>
                      <td className="py-3 px-3 font-medium text-white">{item.name}</td>
                      <td className="py-3 px-3 text-slate-400">{item.category || '-'}</td>
                      <td className="py-3 px-3 text-right font-mono font-bold text-white">
                        {item.stock} {item.unit}
                      </td>
                      <td className="py-3 px-3 text-right font-mono text-slate-300">
                        {formatRupiah(item.purchase_price)}
                      </td>
                      <td className="py-3 px-3 text-right font-mono font-bold text-emerald-400">
                        {formatRupiah(item.valuation)}
                      </td>
                      <td className="py-3 px-3 text-center">
                        <span
                          className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-semibold ${
                            item.status === 'out_of_stock'
                              ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                              : item.status === 'low_stock'
                              ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30'
                              : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                          }`}
                        >
                          {item.status_label}
                        </span>
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
