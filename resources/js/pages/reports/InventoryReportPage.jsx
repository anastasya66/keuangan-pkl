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
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-[#16323B]">
              Laporan Stok & Valuasi Persediaan
            </h1>
            <p className="mt-1 text-sm text-[#5A7C85]">
              Menampilkan kuantitas fisik persediaan, harga perolehan (cost price), status ketersediaan, dan total nilai aset barang.
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
              Filter Kategori
            </label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
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
            <label className="block text-xs font-semibold text-[#16323B] mb-1">
              Status Ketersediaan
            </label>
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value)}
              className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
            >
              <option value="">Semua Status</option>
              <option value="available">Stok Aman (&gt; 10)</option>
              <option value="low_stock">Stok Menipis (&le; 10)</option>
              <option value="out_of_stock">Stok Habis (= 0)</option>
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

      {/* KPI Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
          <div className="text-xs font-semibold text-[#5A7C85] uppercase">Total SKU Terdaftar</div>
          <div className="text-2xl font-extrabold font-mono text-[#16323B] mt-2">
            {loading ? '...' : (data?.summary?.total_products || 0) + ' Produk'}
          </div>
          <div className="text-[11px] text-[#5A7C85] mt-1">Item aktif di etalase/gudang</div>
        </div>

        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
          <div className="text-xs font-semibold text-[#5A7C85] uppercase">Total Kuantitas Fisik</div>
          <div className="text-2xl font-extrabold font-mono text-[#27C5E5] mt-2">
            {loading ? '...' : (data?.summary?.total_units || 0) + ' Unit'}
          </div>
          <div className="text-[11px] text-[#5A7C85] mt-1">Akumulasi seluruh stok barang</div>
        </div>

        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
          <div className="text-xs font-semibold text-[#5A7C85] uppercase">Valuasi Persediaan (Cost)</div>
          <div className="text-2xl font-extrabold font-mono text-[#36B878] mt-2">
            {loading ? '...' : formatRupiah(data?.summary?.total_valuation || 0)}
          </div>
          <div className="text-[11px] text-[#5A7C85] mt-1">Kuantitas &times; Harga Beli</div>
        </div>
      </div>

      {/* Product Stock Table Card */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs">
        {loading ? (
          <div className="text-center py-16 text-[#5A7C85]">
            <div className="inline-block w-8 h-8 border-2 border-[#27C5E5] border-t-transparent rounded-full animate-spin mb-3"></div>
            <p className="text-sm">Memuat data inventaris stok...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-rose-600 font-semibold text-sm">{error}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-[#F3FBFC] text-[#5A7C85] font-semibold border-b border-[#D7EBEF]">
                <tr className="uppercase tracking-wider text-[10px]">
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
              <tbody className="divide-y divide-[#D7EBEF]/60">
                {data?.items?.length === 0 ? (
                  <tr>
                    <td colSpan="8" className="py-8 text-center text-[#5A7C85]">
                      Tidak ada produk ditemukan sesuai kriteria filter.
                    </td>
                  </tr>
                ) : (
                  data?.items?.map((item, idx) => (
                    <tr key={item.id} className="hover:bg-[#F3FBFC]/50 transition-colors">
                      <td className="py-3 px-3 text-[#5A7C85]">{idx + 1}</td>
                      <td className="py-3 px-3 font-mono font-semibold text-[#27C5E5]">{item.sku}</td>
                      <td className="py-3 px-3 font-semibold text-[#16323B]">{item.name}</td>
                      <td className="py-3 px-3 text-[#5A7C85]">{item.category || '-'}</td>
                      <td className="py-3 px-3 text-right font-mono font-bold text-[#16323B] whitespace-nowrap">
                        {item.stock} {item.unit}
                      </td>
                      <td className="py-3 px-3 text-right font-mono text-[#5A7C85] whitespace-nowrap">
                        {formatRupiah(item.purchase_price)}
                      </td>
                      <td className="py-3 px-3 text-right font-mono font-bold text-[#36B878] whitespace-nowrap">
                        {formatRupiah(item.valuation)}
                      </td>
                      <td className="py-3 px-3 text-center whitespace-nowrap">
                        <span
                          className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-xs whitespace-nowrap ${
                            item.status === 'out_of_stock'
                              ? 'bg-rose-600 text-white'
                              : item.status === 'low_stock'
                              ? 'bg-amber-600 text-white'
                              : 'bg-emerald-600 text-white'
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
