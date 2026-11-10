import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import inventoryValuationService from '../../services/inventoryValuationService';
import categoryService from '../../services/categoryService';
import { formatRupiah } from '../../utils/currency';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import { IconValuation, IconPurchase } from '../../components/common/Icons';

export function InventoryValuationPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const [data, setData] = useState(null);
  const [categories, setCategories] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);

  // Filters
  const [search, setSearch] = useState('');
  const [categoryIdFilter, setCategoryIdFilter] = useState('');
  const [stockStatusFilter, setStockStatusFilter] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Category breakdown toggle/view
  const [showCategoryTable, setShowCategoryTable] = useState(true);

  // Toast
  const [toast, setToast] = useState(null);
  const [exportingExcel, setExportingExcel] = useState(false);
  const [exportingPdf, setExportingPdf] = useState(false);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  useEffect(() => {
    async function loadCategories() {
      try {
        const res = await categoryService.getCategories({ all: true, is_active: true });
        if (res.success) setCategories(res.data);
      } catch (err) {
        console.error('Gagal memuat kategori:', err);
      }
    }
    loadCategories();
  }, []);

  const fetchValuation = useCallback(
    async (page = 1, query = search, catId = categoryIdFilter, status = stockStatusFilter) => {
      setIsLoading(true);
      try {
        const params = { page, search: query };
        if (catId) params.category_id = catId;
        if (status) params.stock_status = status;

        const res = await inventoryValuationService.getValuation(params);
        if (res.success) {
          setData(res.data);
          setPagination(res.pagination);
        }
      } catch (err) {
        showToast(err.response?.data?.message || 'Gagal memuat data nilai persediaan', 'error');
      } finally {
        setIsLoading(false);
      }
    },
    [search, categoryIdFilter, stockStatusFilter]
  );

  useEffect(() => {
    fetchValuation(currentPage, search, categoryIdFilter, stockStatusFilter);
  }, [currentPage, search, categoryIdFilter, stockStatusFilter, fetchValuation]);

  const handleExportExcel = async () => {
    const params = {};
    if (search) params.search = search;
    if (categoryIdFilter) params.category_id = categoryIdFilter;
    if (stockStatusFilter) params.stock_status = stockStatusFilter;

    setExportingExcel(true);
    try {
      await inventoryValuationService.downloadValuationExcel(params);
      showToast('Laporan Nilai Persediaan (Excel) berhasil diunduh.', 'success');
    } catch (err) {
      console.error('Gagal export Excel:', err);
      showToast(err.message || 'Gagal mengunduh file Excel Nilai Persediaan.', 'error');
    } finally {
      setExportingExcel(false);
    }
  };

  const handleExportPdf = async () => {
    const params = {};
    if (search) params.search = search;
    if (categoryIdFilter) params.category_id = categoryIdFilter;
    if (stockStatusFilter) params.stock_status = stockStatusFilter;

    setExportingPdf(true);
    try {
      await inventoryValuationService.downloadValuationPdf(params);
      showToast('Laporan Nilai Persediaan (PDF) berhasil diunduh.', 'success');
    } catch (err) {
      console.error('Gagal export PDF:', err);
      showToast(err.message || 'Gagal mengunduh file PDF Nilai Persediaan.', 'error');
    } finally {
      setExportingPdf(false);
    }
  };

  const purchasesLink = isAdmin ? '/admin/purchases' : '/staff/purchases';

  const columns = [
    { header: 'Kode SKU & Barcode', className: 'whitespace-nowrap w-36 text-left' },
    { header: 'Daftar Produk', className: 'min-w-[220px] text-left' },
    { header: 'Stok Saat Ini', className: 'text-right whitespace-nowrap' },
    { header: 'Harga Modal Rata-rata', className: 'text-right whitespace-nowrap' },
    { header: 'Total Nilai Persediaan', className: 'text-right whitespace-nowrap' },
    { header: 'Harga Jual Satuan', className: 'text-right whitespace-nowrap' },
    { header: 'Potensi Laba', className: 'text-right whitespace-nowrap' },
  ];

  return (
    <div className="space-y-5">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2 ${
            toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-700'
              : 'bg-emerald-50 border-emerald-200 text-emerald-700'
          }`}
        >
          <span>{toast.type === 'error' ? '⚠️' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      {/* Header Bar */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white border border-[#D7EBEF] rounded-2xl p-4 sm:p-5 shadow-xs">
        <div>
          <div className="flex items-center gap-2.5">
            <span className="p-2 bg-[#167A93] text-white rounded-xl shadow-xs">
              <IconValuation className="w-5 h-5" />
            </span>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-xl font-bold text-[#16323B]">Nilai Persediaan</h1>
                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Real-Time
                </span>
              </div>
              <p className="text-xs text-[#577782] mt-0.5">
                Valuasi nilai aset stok barang berdasarkan harga modal rata-rata.
              </p>
            </div>
          </div>
        </div>

        {/* Action & Export Buttons */}
        <div className="flex flex-wrap items-center gap-2 shrink-0">
          <Link
            to={purchasesLink}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-[#27C5E5] hover:bg-[#167A93] text-white font-semibold rounded-xl text-xs shadow-xs transition cursor-pointer"
            title="Tambah stok barang melalui menu Pembelian"
          >
            <IconPurchase className="w-3.5 h-3.5" />
            <span>+ Input Pembelian</span>
          </Link>

          <button
            type="button"
            onClick={() => fetchValuation(currentPage, search, categoryIdFilter, stockStatusFilter)}
            className="flex items-center gap-1.5 px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] hover:bg-[#EAF8FA] text-[#16323B] font-semibold rounded-xl text-xs transition cursor-pointer"
            title="Segarkan data terbaru"
          >
            <span>🔄</span>
            <span>Segarkan</span>
          </button>

          <button
            type="button"
            onClick={handleExportExcel}
            disabled={exportingExcel || isLoading}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-xs shadow-xs transition cursor-pointer disabled:opacity-50"
            title="Export data ke spreadsheet Excel"
          >
            <span>📊</span>
            <span>{exportingExcel ? 'Mengunduh...' : 'Excel'}</span>
          </button>

          <button
            type="button"
            onClick={handleExportPdf}
            disabled={exportingPdf || isLoading}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-[#167A93] hover:bg-[#105668] text-white font-semibold rounded-xl text-xs shadow-xs transition cursor-pointer disabled:opacity-50"
            title="Export data ke format PDF"
          >
            <span>📄</span>
            <span>{exportingPdf ? 'Mengunduh...' : 'PDF'}</span>
          </button>
        </div>
      </div>

      {/* 1. Panel Ringkasan Statistik Utama (5 Kartu Statistik Utama) */}
      {data?.summary && (
        <div className="bg-white border border-[#D7EBEF] rounded-2xl shadow-xs overflow-hidden">
          <div className="grid grid-cols-2 md:grid-cols-5 divide-y md:divide-y-0 md:divide-x divide-[#EAF4F6]">
            {/* Metrik 1: Total Nilai Persediaan Toko */}
            <div className="p-4 sm:p-5 col-span-2 md:col-span-1 bg-gradient-to-br from-[#F8FDFE] to-[#EAF8FA]/40">
              <span className="text-[11px] font-bold uppercase tracking-wider text-[#167A93]">
                Total Nilai Persediaan
              </span>
              <div className="text-2xl font-extrabold font-mono text-[#167A93] mt-1 tracking-tight">
                {formatRupiah(data.summary.total_valuation)}
              </div>
              <div className="text-[10px] text-[#577782] mt-0.5">
                Total modal stok aktif
              </div>
            </div>

            {/* Metrik 2: Total Unit Fisik */}
            <div className="p-4 sm:p-5">
              <span className="text-[11px] font-semibold text-[#577782] uppercase tracking-wider">
                Total Stok Fisik
              </span>
              <div className="text-xl font-bold font-mono text-[#16323B] mt-1 flex items-baseline gap-1">
                <span>{Number(data.summary.total_stock_units).toLocaleString('id-ID')}</span>
                <span className="text-xs font-sans font-semibold text-[#577782]">Unit</span>
              </div>
              <div className="text-[10px] text-[#577782] mt-0.5">
                Dari <strong>{data.summary.total_products}</strong> jenis barang
              </div>
            </div>

            {/* Metrik 3: PERLU RESTOK */}
            <div className="p-4 sm:p-5">
              <span className="text-[11px] font-bold text-rose-600 uppercase tracking-wider">
                PERLU RESTOK
              </span>
              <div className="text-xl font-bold font-mono text-rose-600 mt-1">
                {data.summary.low_stock_count + data.summary.out_of_stock_count}
              </div>
              <div className="text-[10px] text-[#577782] mt-0.5">
                Habis: <strong>{data.summary.out_of_stock_count}</strong> &bull; Menipis: <strong>{data.summary.low_stock_count}</strong>
              </div>
            </div>

            {/* Metrik 4: Potensi Omzet Penjualan */}
            <div className="p-4 sm:p-5">
              <span className="text-[11px] font-semibold text-[#577782] uppercase tracking-wider">
                Potensi Omzet
              </span>
              <div className="text-lg font-bold font-mono text-[#16323B] mt-1">
                {formatRupiah(data.summary.potential_revenue)}
              </div>
              <div className="text-[10px] text-[#577782] mt-0.5">
                Estimasi nilai jual
              </div>
            </div>

            {/* Metrik 5: Potensi Laba Kotor */}
            <div className="p-4 sm:p-5 bg-emerald-50/20">
              <span className="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">
                Potensi Laba Kotor
              </span>
              <div className="text-lg font-bold font-mono text-emerald-600 mt-1">
                {formatRupiah(data.summary.potential_profit)}
              </div>
              <div className="text-[10px] text-[#577782] mt-0.5">
                Estimasi margin kotor
              </div>
            </div>
          </div>
        </div>
      )}

      {/* 2. Komposisi Kategori (Tabel Ringkas & Minimalis) */}
      {data?.category_breakdown && data.category_breakdown.length > 0 && (
        <div className="bg-white border border-[#D7EBEF] rounded-2xl shadow-xs overflow-hidden">
          <div className="px-4 py-3 bg-[#F8FDFE] border-b border-[#EAF4F6] flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="text-xs font-bold uppercase tracking-wider text-[#16323B]">
                KOMPOSISI KATEGORI
              </span>
              <span className="text-[11px] text-[#577782]">
                ({data.category_breakdown.length} kategori)
              </span>
            </div>
            <button
              type="button"
              onClick={() => setShowCategoryTable(!showCategoryTable)}
              className="text-xs text-[#167A93] hover:text-[#16323B] font-semibold cursor-pointer"
            >
              {showCategoryTable ? 'Sembunyikan ▲' : 'Tampilkan ▼'}
            </button>
          </div>

          {showCategoryTable && (
            <div className="overflow-x-auto">
              <table className="w-full text-xs text-left border-collapse">
                <thead>
                  <tr className="bg-[#F0F9FB] border-b border-[#D7EBEF] text-[#577782] font-semibold">
                    <th className="py-2.5 px-4 whitespace-nowrap text-left">Nama Kategori</th>
                    <th className="py-2.5 px-3 text-center w-28 whitespace-nowrap">Jumlah Item</th>
                    <th className="py-2.5 px-3 text-right w-32 whitespace-nowrap">Stok Fisik</th>
                    <th className="py-2.5 px-4 text-right w-40 whitespace-nowrap">Nilai Persediaan</th>
                    <th className="py-2.5 px-4 w-48 text-right whitespace-nowrap">Porsi Nilai (%)</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#F0F9FB]">
                  {data.category_breakdown.map((cat) => {
                    const isSelected = String(categoryIdFilter) === String(cat.category_id);
                    return (
                      <tr
                        key={cat.category_id}
                        onClick={() => setCategoryIdFilter(isSelected ? '' : cat.category_id)}
                        className={`transition cursor-pointer ${
                          isSelected ? 'bg-[#EAF8FA]' : 'hover:bg-[#F8FDFE]'
                        }`}
                        title="Klik untuk memfilter daftar produk kategori ini"
                      >
                        <td className="py-2.5 px-4 font-semibold text-[#16323B] whitespace-nowrap text-left">
                          <div className="flex items-center gap-1.5">
                            <span>{isSelected ? '✓' : '•'}</span>
                            <span>{cat.category_name}</span>
                            {isSelected && (
                              <span className="text-[10px] bg-[#27C5E5] text-white px-1.5 py-0.2 rounded font-mono">
                                Filter Aktif
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="py-2.5 px-3 text-center text-[#577782] font-mono whitespace-nowrap">
                          {cat.product_count} produk
                        </td>
                        <td className="py-2.5 px-3 text-right font-mono text-[#16323B] whitespace-nowrap">
                          {Number(cat.total_stock).toLocaleString('id-ID')} unit
                        </td>
                        <td className="py-2.5 px-4 text-right font-mono font-bold text-[#167A93] whitespace-nowrap">
                          {formatRupiah(cat.total_valuation)}
                        </td>
                        <td className="py-2.5 px-4 text-right whitespace-nowrap">
                          <div className="flex items-center justify-end gap-2">
                            <span className="font-mono text-xs font-semibold text-[#577782] w-12 text-right whitespace-nowrap">
                              {cat.percentage}%
                            </span>
                            <div className="w-20 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                              <div
                                className="bg-[#167A93] h-1.5 rounded-full transition-all"
                                style={{ width: `${Math.min(100, Math.max(0, cat.percentage))}%` }}
                              ></div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* 3. Filter and Search Bar */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs flex flex-col sm:flex-row items-center gap-3 justify-between">
        <div className="w-full sm:w-80">
          <SearchInput
            value={search}
            onChange={(val) => {
              setSearch(val);
              setCurrentPage(1);
            }}
            placeholder="Cari SKU, barcode, nama produk..."
          />
        </div>

        <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
          <select
            value={categoryIdFilter}
            onChange={(e) => {
              setCategoryIdFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
          >
            <option value="">Semua Kategori</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </select>

          <select
            value={stockStatusFilter}
            onChange={(e) => {
              setStockStatusFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
          >
            <option value="">Semua Kondisi Stok</option>
            <option value="available">Tersedia Aman</option>
            <option value="low_stock">Stok Menipis</option>
            <option value="out_of_stock">Stok Habis (0)</option>
          </select>

          {(categoryIdFilter || stockStatusFilter || search) && (
            <button
              type="button"
              onClick={() => {
                setCategoryIdFilter('');
                setStockStatusFilter('');
                setSearch('');
                setCurrentPage(1);
              }}
              className="text-xs text-rose-500 hover:text-rose-700 px-2 py-1 font-semibold cursor-pointer"
              title="Reset semua filter pencarian"
            >
              Reset Filter
            </button>
          )}
        </div>
      </div>

      {/* 4. Product Table Nilai Persediaan */}
      <div className="space-y-4">
        <div className="flex items-center justify-between px-1 text-xs text-[#577782]">
          <span className="font-bold uppercase tracking-wider text-[#16323B]">
            Daftar Produk &amp; Nilai Persediaan Stok Toko
          </span>
          {pagination && (
            <span>
              Menampilkan {pagination.from || 0} - {pagination.to || 0} dari {pagination.total} produk
            </span>
          )}
        </div>

        <DataTable
          columns={columns}
          data={data?.products || []}
          isLoading={isLoading}
          emptyMessage="Tidak ada data produk persediaan yang sesuai filter."
          renderRow={(item) => {
            const stock = Number(item.stock) || 0;
            const minStock = Number(item.min_stock) || 5;
            const isOut = stock <= 0;
            const isLow = stock > 0 && stock <= minStock;
            const avg = Number(item.average_cost) > 0 ? Number(item.average_cost) : Number(item.purchase_price);
            const val = Math.max(0, stock) * avg;
            const sell = Number(item.selling_price) || 0;
            const profit = Math.max(0, stock) * (sell - avg);

            return (
              <tr key={item.id} className="hover:bg-[#F3FBFC] transition-colors border-b border-[#EBF5F8]">
                {/* Kode SKU & Barcode */}
                <td className="px-4 py-3 whitespace-nowrap align-middle">
                  <div className="flex flex-col items-start gap-0.5">
                    <span className="font-mono text-xs font-bold text-[#167A93] bg-[#EAF8FA] px-2 py-0.5 rounded border border-[#38BFE0]/30 whitespace-nowrap inline-block">
                      {item.sku}
                    </span>
                    {item.barcode && (
                      <span className="font-mono text-[10px] text-[#8FA8B0] whitespace-nowrap">
                        {item.barcode}
                      </span>
                    )}
                  </div>
                </td>

                {/* Daftar Produk (Rata Kiri, Min Width 220px) */}
                <td className="px-4 py-3 text-left min-w-[220px] align-middle">
                  <div className="font-semibold text-xs sm:text-[13px] text-[#16323B] leading-snug">
                    {item.name}
                  </div>
                  <div className="text-[11px] text-[#8FA8B0] mt-0.5 whitespace-nowrap">
                    {item.category?.name || '-'}
                  </div>
                </td>

                {/* Stok Saat Ini (Badge Seragam Rata Kanan) */}
                <td className="px-4 py-3 text-right whitespace-nowrap align-middle">
                  <div className="flex justify-end">
                    <span
                      className={`font-mono tabular-nums font-bold text-xs px-2.5 py-1 rounded-lg text-right min-w-[80px] inline-flex items-center justify-end shadow-2xs whitespace-nowrap ${
                        isOut
                          ? 'bg-rose-50 text-rose-700 border border-rose-200'
                          : isLow
                          ? 'bg-amber-50 text-amber-700 border border-amber-200'
                          : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                      }`}
                    >
                      {stock.toLocaleString('id-ID')} {item.unit?.symbol || 'pcs'}
                    </span>
                  </div>
                </td>

                {/* Harga Modal Rata-rata (Rata Kanan, Tabular Nums) */}
                <td className="px-4 py-3 text-right whitespace-nowrap align-middle">
                  <div className="font-mono tabular-nums text-xs font-semibold text-[#16323B]">
                    {formatRupiah(avg)}
                  </div>
                  <div className="text-[10px] text-[#8FA8B0] font-sans font-normal mt-0.5 whitespace-nowrap">
                    HPP Rata-rata
                  </div>
                </td>

                {/* Total Nilai Persediaan (Rata Kanan, Tabular Nums) */}
                <td className="px-4 py-3 text-right whitespace-nowrap align-middle">
                  <div className="font-mono tabular-nums text-xs sm:text-[13px] font-bold text-[#167A93]">
                    {formatRupiah(val)}
                  </div>
                  <div className="text-[10px] text-[#8FA8B0] font-sans font-normal mt-0.5 whitespace-nowrap">
                    Nilai Aset
                  </div>
                </td>

                {/* Harga Jual Satuan (Rata Kanan, Tabular Nums) */}
                <td className="px-4 py-3 text-right whitespace-nowrap align-middle">
                  <div className="font-mono tabular-nums text-xs font-semibold text-[#16323B]">
                    {formatRupiah(item.selling_price)}
                  </div>
                  <div className="text-[10px] text-[#8FA8B0] font-sans font-normal mt-0.5 whitespace-nowrap">
                    Harga Kasir
                  </div>
                </td>

                {/* Potensi Laba (Rata Kanan, Tabular Nums) */}
                <td className="px-4 py-3 text-right whitespace-nowrap align-middle">
                  <div className="font-mono tabular-nums text-xs font-bold text-emerald-600">
                    {formatRupiah(profit)}
                  </div>
                  <div className="text-[10px] text-emerald-600/70 font-sans font-normal mt-0.5 whitespace-nowrap">
                    Margin Kotor
                  </div>
                </td>
              </tr>
            );
          }}
        />

        {pagination && pagination.total > pagination.per_page && (
          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
            <Pagination
              pagination={pagination}
              onPageChange={(page) => setCurrentPage(page)}
            />
          </div>
        )}
      </div>
    </div>
  );
}

export default InventoryValuationPage;
