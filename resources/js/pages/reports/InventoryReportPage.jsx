import React, { useEffect, useState, useMemo } from 'react';
import { RefreshCw, FileText, FileSpreadsheet } from 'lucide-react';
import categoryService from '../../services/categoryService';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';
import PageHeader from '../../components/ui/PageHeader';
import StatusBadge from '../../components/ui/StatusBadge';

export default function InventoryReportPage() {
  const [categories, setCategories] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [exportingPdf, setExportingPdf] = useState(false);
  const [exportingExcel, setExportingExcel] = useState(false);
  const [error, setError] = useState(null);
  const [lastRefreshed, setLastRefreshed] = useState(null);

  // Load master categories once
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

  // Fetch inventory report data from backend
  const fetchReport = async () => {
    setLoading(true);
    setError(null);
    try {
      const params = {};
      if (selectedCategory) params.category_id = selectedCategory;
      if (selectedStatus) params.stock_status = selectedStatus;

      const res = await reportService.getInventoryReport(params);
      setData(res.data);
      setLastRefreshed(new Date());
    } catch (err) {
      console.error('Gagal mengambil data Sisa Stok Toko:', err);
      setError('Gagal memuat laporan stok persediaan toko.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReport();
  }, [selectedCategory, selectedStatus]);

  // Export handlers
  const handleExportPdf = async () => {
    setExportingPdf(true);
    try {
      const params = {};
      if (selectedCategory) params.category_id = selectedCategory;
      if (selectedStatus) params.stock_status = selectedStatus;
      if (searchQuery) params.search = searchQuery;
      await reportService.downloadInventoryReportPdf(params);
    } catch (err) {
      alert(err.message || 'Gagal mengunduh file PDF Laporan Stok.');
    } finally {
      setExportingPdf(false);
    }
  };

  const handleExportExcel = async () => {
    setExportingExcel(true);
    try {
      const params = {};
      if (selectedCategory) params.category_id = selectedCategory;
      if (selectedStatus) params.stock_status = selectedStatus;
      if (searchQuery) params.search = searchQuery;
      await reportService.downloadInventoryReportExcel(params);
    } catch (err) {
      alert(err.message || 'Gagal mengunduh file Excel Laporan Stok.');
    } finally {
      setExportingExcel(false);
    }
  };

  // Extract products array safely
  const rawProducts = useMemo(() => {
    if (!data) return [];
    return data.items || data.products || [];
  }, [data]);

  // Client-side quick search filtering for instant responsiveness
  const filteredProducts = useMemo(() => {
    if (!searchQuery.trim()) return rawProducts;
    const query = searchQuery.toLowerCase().trim();
    return rawProducts.filter((p) => {
      const name = (p.name || '').toLowerCase();
      const sku = (p.sku || '').toLowerCase();
      const barcode = (p.barcode || '').toLowerCase();
      const category = (p.category || p.category_name || '').toLowerCase();
      return (
        name.includes(query) ||
        sku.includes(query) ||
        barcode.includes(query) ||
        category.includes(query)
      );
    });
  }, [rawProducts, searchQuery]);

  // Dynamic calculations for displayed rows
  const totals = useMemo(() => {
    return filteredProducts.reduce(
      (acc, item) => {
        acc.totalIn += Number(item.total_in || 0);
        acc.totalOut += Number(item.total_out || 0);
        acc.currentStock += Number(item.stock || 0);
        return acc;
      },
      { totalIn: 0, totalOut: 0, currentStock: 0 }
    );
  }, [filteredProducts]);

  return (
    <div className="space-y-5">
      {/* 1. Header Section */}
      <PageHeader
        title="Sisa Stok Toko"
        subtitle="Informasi pergerakan inventaris fisik dari pembelian dan penjualan kasir."
      >
        <div className="flex flex-wrap items-center gap-2">
          <button
            onClick={fetchReport}
            disabled={loading}
            title="Sinkronisasi data stok terbaru"
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-white border border-[#E7ECEE] hover:bg-[#F1F4F5] text-[#20282D] font-bold text-xs transition shadow-xs cursor-pointer disabled:opacity-50"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin text-[#55BFD8]' : ''}`} />
            <span>{loading ? 'Menyinkronkan...' : 'Sinkronkan Data'}</span>
          </button>

          <button
            onClick={handleExportPdf}
            disabled={exportingPdf || loading}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-[#E5F6FA] text-[#37AFCB] hover:bg-[#D0F1F8] font-bold text-xs transition shadow-xs disabled:opacity-50 cursor-pointer"
          >
            <FileText className="w-3.5 h-3.5" />
            <span>{exportingPdf ? 'Mengunduh...' : 'PDF'}</span>
          </button>

          <button
            onClick={handleExportExcel}
            disabled={exportingExcel || loading}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-[#EBF7F2] text-[#48B58B] hover:bg-[#D5EFE3] font-bold text-xs transition shadow-xs disabled:opacity-50 cursor-pointer"
          >
            <FileSpreadsheet className="w-3.5 h-3.5" />
            <span>{exportingExcel ? 'Mengunduh...' : 'Excel'}</span>
          </button>
        </div>
      </PageHeader>

        {/* Real-time sync note */}
        <div className="mt-3 pt-3 border-t border-[#D7EBEF]/60 flex items-center justify-between text-[11px] text-[#577782]">
          <div className="flex items-center gap-2">
            <span className="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Sinkronisasi otomatis aktif.</span>
          </div>
          {lastRefreshed && (
            <span className="text-[10px] text-[#8EA6AF]">
              Update terakhir: {lastRefreshed.toLocaleTimeString('id-ID')} WIB
            </span>
          )}
        </div>

      {/* 2. Ringkasan / Summary Bar yang Rapi & Minimalis */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-0 md:divide-x md:divide-[#D7EBEF]">
          {/* Card 1: Total Jenis SKU */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">Total Jenis Produk</span>
            <div className="text-xl sm:text-2xl font-extrabold font-mono text-[#16323B] mt-0.5">
              {loading ? '...' : (data?.summary?.total_products || 0)}{' '}
              <span className="text-xs font-normal text-[#577782]">Item</span>
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Produk aktif</span>
          </div>

          {/* Card 2: Total Unit Fisik Seluruh Toko */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">Total Sisa Unit Fisik</span>
            <div className="text-xl sm:text-2xl font-extrabold font-mono text-[#167A93] mt-0.5">
              {loading ? '...' : Number(data?.summary?.total_units || data?.summary?.total_stock_units || 0).toLocaleString('id-ID')}{' '}
              <span className="text-xs font-normal text-[#167A93]">Unit</span>
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Total unit tersedia</span>
          </div>

          {/* Card 3: Akumulasi Arus Masuk vs Keluar */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">Arus Masuk / Keluar</span>
            <div className="flex items-center gap-1.5 mt-1">
              <span className="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold font-mono text-emerald-700 bg-emerald-50 border border-emerald-200">
                +{Number(data?.summary?.total_in || 0).toLocaleString('id-ID')}
              </span>
              <span className="text-xs text-[#8EA6AF]">/</span>
              <span className="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold font-mono text-rose-700 bg-rose-50 border border-rose-200">
                -{Number(data?.summary?.total_out || 0).toLocaleString('id-ID')}
              </span>
            </div>
            <span className="text-[10px] text-[#8EA6AF] mt-1 block">Pembelian (+) vs Penjualan (-)</span>
          </div>

          {/* Card 4: Status Kritis (Habis / Menipis) */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">Peringatan Ketersediaan</span>
            <div className="flex items-center gap-1.5 mt-1">
              {(data?.summary?.out_of_stock_count || 0) > 0 ? (
                <span className="px-2 py-0.5 rounded-md text-xs font-bold font-mono bg-rose-100 text-rose-800 border border-rose-200">
                  {data?.summary?.out_of_stock_count} Habis
                </span>
              ) : null}
              {(data?.summary?.low_stock_count || 0) > 0 ? (
                <span className="px-2 py-0.5 rounded-md text-xs font-bold font-mono bg-amber-100 text-amber-800 border border-amber-200">
                  {data?.summary?.low_stock_count} Menipis
                </span>
              ) : null}
              {(!data?.summary?.out_of_stock_count && !data?.summary?.low_stock_count) && (
                <span className="px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  ✓ Semua Stok Aman
                </span>
              )}
            </div>
            <span className="text-[10px] text-[#8EA6AF] mt-1 block">Stok habis/kritis</span>
          </div>
        </div>
      </div>

      {/* 3. Search & Filter Bar */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
        <div className="flex flex-col md:flex-row items-center gap-3">
          {/* Quick Search Input */}
          <div className="relative flex-1 w-full">
            <span className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#577782]">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Cari cepat Kode SKU, Barcode, atau Nama Produk..."
              className="w-full pl-9 pr-8 py-2 text-xs bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-[#16323B] placeholder-[#8EA6AF] focus:bg-white focus:outline-none focus:border-[#27C5E5] focus:ring-2 focus:ring-[#27C5E5]/20 transition-all"
            />
            {searchQuery && (
              <button
                type="button"
                onClick={() => setSearchQuery('')}
                className="absolute inset-y-0 right-0 pr-2.5 flex items-center text-[#8EA6AF] hover:text-[#16323B] cursor-pointer"
              >
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            )}
          </div>

          {/* Filter Category */}
          <div className="w-full md:w-48">
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="w-full py-2 px-3 text-xs bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-[#16323B] focus:bg-white focus:outline-none focus:border-[#27C5E5] focus:ring-2 focus:ring-[#27C5E5]/20 transition-all cursor-pointer"
            >
              <option value="">Semua Kategori</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
          </div>

          {/* Filter Stock Status */}
          <div className="w-full md:w-48">
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value)}
              className="w-full py-2 px-3 text-xs bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-[#16323B] focus:bg-white focus:outline-none focus:border-[#27C5E5] focus:ring-2 focus:ring-[#27C5E5]/20 transition-all cursor-pointer"
            >
              <option value="">Semua Status Stok</option>
              <option value="available">Tersedia (&gt; 10)</option>
              <option value="low_stock">Menipis (&le; 10)</option>
              <option value="out_of_stock">Habis (0)</option>
            </select>
          </div>

          {/* Result counter */}
          <div className="text-xs text-[#577782] whitespace-nowrap self-end md:self-center px-1 font-medium">
            {filteredProducts.length} produk
          </div>
        </div>
      </div>

      {/* 4. Tabel Sisa Stok Toko */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl shadow-xs overflow-hidden">
        {loading ? (
          <div className="text-center py-16 text-[#577782]">
            <div className="inline-block w-8 h-8 border-3 border-[#27C5E5] border-t-transparent rounded-full animate-spin mb-3"></div>
            <p className="text-xs font-semibold text-[#16323B]">Memuat data sisa stok toko...</p>
            <p className="text-[11px] text-[#8EA6AF] mt-0.5">Menghubungkan arus masuk &amp; keluar mutasi</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 px-4">
            <p className="text-rose-600 font-semibold text-xs mb-3">{error}</p>
            <button
              onClick={fetchReport}
              className="px-3.5 py-1.5 rounded-xl bg-[#27C5E5] hover:bg-[#167A93] text-white text-xs font-semibold transition-all cursor-pointer shadow-xs"
            >
              Coba Lagi
            </button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-[#F8FDFE] text-[#577782] font-semibold border-b border-[#D7EBEF]">
                <tr className="uppercase tracking-wider text-[10px]">
                  <th className="py-3 px-3 text-center w-12 whitespace-nowrap">No</th>
                  <th className="py-3 px-3 w-32 whitespace-nowrap">Kode / SKU</th>
                  <th className="py-3 px-3 min-w-[220px]">Nama Produk</th>
                  <th className="py-3 px-3 w-32 whitespace-nowrap">Kategori</th>
                  <th className="py-3 px-3 text-right w-36 whitespace-nowrap">Harga Beli (HPP)</th>
                  <th className="py-3 px-3 text-right w-36 whitespace-nowrap">Harga Jual</th>
                  <th className="py-3 px-3 text-right w-28 whitespace-nowrap bg-emerald-50/50 text-emerald-800">
                    Masuk (+)
                  </th>
                  <th className="py-3 px-3 text-right w-28 whitespace-nowrap bg-rose-50/50 text-rose-800">
                    Keluar (-)
                  </th>
                  <th className="py-3 px-3 text-right w-32 whitespace-nowrap bg-[#D7EBEF]/30 text-[#16323B]">
                    Sisa Stok
                  </th>
                  <th className="py-3 px-3 text-center w-28 whitespace-nowrap">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#D7EBEF]/60">
                {filteredProducts.length === 0 ? (
                  <tr>
                    <td colSpan="10" className="py-12 text-center text-[#577782]">
                      <svg className="w-10 h-10 mx-auto text-[#8EA6AF]/60 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                      </svg>
                      <p className="font-semibold text-xs text-[#16323B]">Tidak ada barang ditemukan</p>
                      <p className="text-[11px] text-[#8EA6AF] mt-0.5">Coba ubah kata kunci pencarian atau reset filter</p>
                    </td>
                  </tr>
                ) : (
                  filteredProducts.map((item, idx) => {
                    const stock = Number(item.stock || 0);
                    const unit = item.unit || item.unit_symbol || 'unit';
                    const isOutOfStock = stock <= 0;
                    const isLowStock = stock > 0 && stock <= 10;

                    return (
                      <tr key={item.id} className="hover:bg-[#F8FDFE] transition-colors">
                        {/* No */}
                        <td className="py-3 px-3 text-center text-[#8EA6AF] font-mono text-[11px]">
                          {idx + 1}
                        </td>

                        {/* Kode / SKU */}
                        <td className="py-3 px-3 font-mono font-semibold text-[#167A93] whitespace-nowrap">
                          {item.sku}
                        </td>

                        {/* Nama Produk */}
                        <td className="py-3 px-3 min-w-[220px]">
                          <div className="font-semibold text-[#16323B]">{item.name}</div>
                          {item.barcode && (
                            <div className="text-[10px] text-[#8EA6AF] font-mono mt-0.5">
                              Barcode: {item.barcode}
                            </div>
                          )}
                        </td>

                        {/* Kategori */}
                        <td className="py-3 px-3 text-[#577782] whitespace-nowrap">
                          <span className="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-[#D7EBEF]/40 text-[#16323B] whitespace-nowrap">
                            {item.category || item.category_name || '-'}
                          </span>
                        </td>

                        {/* Harga Beli Rata-rata (HPP) */}
                        <td className="py-3 px-3 text-right font-mono tabular-nums text-[#577782] whitespace-nowrap">
                          {formatRupiah(item.purchase_price || item.average_cost || 0)}
                        </td>

                        {/* Harga Jual */}
                        <td className="py-3 px-3 text-right font-mono tabular-nums font-semibold text-[#16323B] whitespace-nowrap">
                          {formatRupiah(item.selling_price || 0)}
                        </td>

                        {/* Total Barang Masuk (Pembelian Stok) */}
                        <td className="py-3 px-3 text-right font-mono tabular-nums font-bold text-emerald-700 bg-emerald-50/30 whitespace-nowrap">
                          +{Number(item.total_in || 0).toLocaleString('id-ID')}
                        </td>

                        {/* Total Barang Keluar (Terjual) */}
                        <td className="py-3 px-3 text-right font-mono tabular-nums font-bold text-rose-700 bg-rose-50/30 whitespace-nowrap">
                          -{Number(item.total_out || 0).toLocaleString('id-ID')}
                        </td>

                        {/* Sisa Stok Saat Ini */}
                        <td className="py-3 px-3 text-right font-mono tabular-nums font-extrabold text-[#16323B] bg-[#D7EBEF]/15 whitespace-nowrap text-sm">
                          {stock.toLocaleString('id-ID')}{' '}
                          <span className="text-[10px] font-normal text-[#577782]">{unit}</span>
                        </td>

                        {/* Status Badge */}
                        <td className="py-3 px-3 text-center whitespace-nowrap">
                          {isOutOfStock ? (
                            <span className="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200 shadow-2xs whitespace-nowrap">
                              Habis
                            </span>
                          ) : isLowStock ? (
                            <span className="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shadow-2xs whitespace-nowrap">
                              Menipis
                            </span>
                          ) : (
                            <span className="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs whitespace-nowrap">
                              Tersedia
                            </span>
                          )}
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>

              {/* Table Footer / Grand Total Summary */}
              {filteredProducts.length > 0 && (
                <tfoot className="bg-[#F8FDFE] border-t-2 border-[#D7EBEF] font-semibold text-xs text-[#16323B]">
                  <tr>
                    <td colSpan="6" className="py-3 px-3 text-right uppercase tracking-wider text-[11px] text-[#577782]">
                      Total ({filteredProducts.length} Produk Ditampilkan):
                    </td>
                    <td className="py-3 px-3 text-right font-mono tabular-nums font-bold text-emerald-700 bg-emerald-50/60 whitespace-nowrap">
                      +{totals.totalIn.toLocaleString('id-ID')}
                    </td>
                    <td className="py-3 px-3 text-right font-mono tabular-nums font-bold text-rose-700 bg-rose-50/60 whitespace-nowrap">
                      -{totals.totalOut.toLocaleString('id-ID')}
                    </td>
                    <td className="py-3 px-3 text-right font-mono tabular-nums font-extrabold text-[#167A93] bg-[#D7EBEF]/40 whitespace-nowrap text-sm">
                      {totals.currentStock.toLocaleString('id-ID')} Unit
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              )}
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
