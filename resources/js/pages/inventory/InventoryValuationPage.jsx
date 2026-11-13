import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import {
  Coins,
  Package,
  AlertTriangle,
  TrendingUp,
  RefreshCw,
  FileSpreadsheet,
  FileText,
  Plus,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import inventoryValuationService from '../../services/inventoryValuationService';
import categoryService from '../../services/categoryService';
import { formatRupiah } from '../../utils/currency';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import PageHeader from '../../components/ui/PageHeader';
import StatCard from '../../components/ui/StatCard';
import StatusBadge from '../../components/ui/StatusBadge';

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

  // Category breakdown toggle
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
        if (res.success && res.data) setCategories(res.data);
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
        if (res.success && res.data) {
          setData(res.data);
          setPagination(res.pagination || null);
        }
      } catch (err) {
        console.error('Gagal memuat data nilai persediaan:', err);
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

  const summary = data?.summary || {};
  const totalValuation = Number(summary.total_valuation) || 0;
  const totalUnits = Number(summary.total_stock_units) || 0;
  const totalProducts = Number(summary.total_products) || 0;
  const lowStock = (Number(summary.low_stock_count) || 0) + (Number(summary.out_of_stock_count) || 0);
  const potentialProfit = Number(summary.potential_profit) || 0;

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2 ${
            toast.type === 'error'
              ? 'bg-[#FDF3F3] border-[#E47777]/30 text-[#E47777]'
              : 'bg-[#EBF7F2] border-[#48B58B]/30 text-[#48B58B]'
          }`}
        >
          <span>{toast.type === 'error' ? '⚠️' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      {/* Header Bar */}
      <PageHeader
        title="Nilai Persediaan (Valuasi Stok)"
        subtitle="Valuasi aset persediaan berbasis metode Moving Average (Harga Modal Rata-rata)."
      >
        <div className="flex flex-wrap items-center gap-2">
          <Link
            to={purchasesLink}
            className="flex items-center gap-1.5 px-4 py-2 bg-[#20282D] hover:bg-black text-white font-bold rounded-full text-xs shadow-xs transition cursor-pointer"
          >
            <Plus className="w-3.5 h-3.5 stroke-[2.5]" />
            <span>Input Pembelian</span>
          </Link>

          <button
            type="button"
            onClick={() => fetchValuation(currentPage, search, categoryIdFilter, stockStatusFilter)}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[#E7ECEE] hover:bg-[#F1F4F5] text-[#20282D] font-bold rounded-full text-xs transition cursor-pointer shadow-xs"
            title="Segarkan data terbaru"
          >
            <RefreshCw className="w-3.5 h-3.5" />
            <span>Segarkan</span>
          </button>

          <button
            type="button"
            onClick={handleExportExcel}
            disabled={exportingExcel || isLoading}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-[#EBF7F2] text-[#48B58B] hover:bg-[#D5EFE3] font-bold rounded-full text-xs transition cursor-pointer disabled:opacity-50"
            title="Export Excel"
          >
            <FileSpreadsheet className="w-3.5 h-3.5" />
            <span>{exportingExcel ? 'Mengunduh...' : 'Excel'}</span>
          </button>

          <button
            type="button"
            onClick={handleExportPdf}
            disabled={exportingPdf || isLoading}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-[#E5F6FA] text-[#37AFCB] hover:bg-[#D0F1F8] font-bold rounded-full text-xs transition cursor-pointer disabled:opacity-50"
            title="Export PDF"
          >
            <FileText className="w-3.5 h-3.5" />
            <span>{exportingPdf ? 'Mengunduh...' : 'PDF'}</span>
          </button>
        </div>
      </PageHeader>

      {/* 1. Four Summary Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <StatCard
          icon={Coins}
          iconBg="bg-[#E5F6FA]"
          iconColor="text-[#37AFCB]"
          label="Total Nilai Persediaan"
          value={isLoading ? '...' : formatRupiah(totalValuation)}
          trend="Nilai Aset Modal"
          trendDirection="up"
          trendPeriod="real-time"
        />

        <StatCard
          icon={Package}
          iconBg="bg-[#F8FAFA]"
          iconColor="text-[#20282D]"
          label="Total Stok Fisik"
          value={isLoading ? '...' : `${totalUnits.toLocaleString('id-ID')} Unit`}
          trend={`${totalProducts} jenis barang`}
          trendDirection="up"
          trendPeriod="katalog aktif"
        />

        <StatCard
          icon={AlertTriangle}
          iconBg="bg-[#FDF3F3]"
          iconColor="text-[#E47777]"
          label="Perlu Restok (Habis/Menipis)"
          value={isLoading ? '...' : `${lowStock} Barang`}
          trend={`${summary.out_of_stock_count || 0} habis &bull; ${summary.low_stock_count || 0} menipis`}
          trendDirection="down"
          trendPeriod="status stok"
        />

        <StatCard
          icon={TrendingUp}
          iconBg="bg-[#EBF7F2]"
          iconColor="text-[#48B58B]"
          label="Potensi Laba Kotor"
          value={isLoading ? '...' : formatRupiah(potentialProfit)}
          trend="Estimasi Margin"
          trendDirection="up"
          trendPeriod="jika terjual semua"
        />
      </div>

      {/* 2. Category Composition Breakdown */}
      {data?.category_breakdown && data.category_breakdown.length > 0 && (
        <div className="bg-white border border-[#E7ECEE] rounded-2xl shadow-[0_2px_10px_rgba(0,0,0,0.02)] overflow-hidden">
          <div className="px-5 py-4 bg-[#F8FAFA] border-b border-[#E7ECEE] flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="text-xs font-bold uppercase tracking-wider text-[#20282D]">
                Komposisi Kategori Persediaan
              </span>
              <span className="text-xs text-[#879197]">
                ({data.category_breakdown.length} kategori)
              </span>
            </div>
            <button
              type="button"
              onClick={() => setShowCategoryTable(!showCategoryTable)}
              className="text-xs text-[#37AFCB] hover:text-[#20282D] font-bold cursor-pointer"
            >
              {showCategoryTable ? 'Sembunyikan ▲' : 'Tampilkan ▼'}
            </button>
          </div>

          {showCategoryTable && (
            <div className="overflow-x-auto no-scrollbar">
              <table className="w-full text-xs text-left">
                <thead>
                  <tr className="bg-[#F8FAFA] border-b border-[#E7ECEE] text-[#879197] font-semibold text-[11px] uppercase tracking-wider">
                    <th className="py-3 px-5 text-left font-medium">Nama Kategori</th>
                    <th className="py-3 px-4 text-center font-medium">Jumlah Item</th>
                    <th className="py-3 px-4 text-right font-medium">Stok Fisik</th>
                    <th className="py-3 px-5 text-right font-medium">Nilai Persediaan</th>
                    <th className="py-3 px-5 text-right font-medium">Porsi Nilai (%)</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#F1F4F5]">
                  {data.category_breakdown.map((cat) => {
                    const isSelected = String(categoryIdFilter) === String(cat.category_id);
                    return (
                      <tr
                        key={cat.category_id}
                        onClick={() => setCategoryIdFilter(isSelected ? '' : cat.category_id)}
                        className={`transition cursor-pointer ${
                          isSelected ? 'bg-[#E5F6FA]' : 'hover:bg-[#F8FAFA]'
                        }`}
                        title="Klik untuk memfilter daftar produk kategori ini"
                      >
                        <td className="py-3 px-5 font-bold text-[#20282D]">
                          <div className="flex items-center gap-2">
                            <span>{cat.category_name}</span>
                            {isSelected && (
                              <span className="text-[10px] bg-[#20282D] text-white px-2 py-0.5 rounded-full font-bold">
                                Filter Aktif
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="py-3 px-4 text-center text-[#879197]">
                          {cat.product_count} produk
                        </td>
                        <td className="py-3 px-4 text-right font-medium text-[#20282D]">
                          {Number(cat.total_stock).toLocaleString('id-ID')} unit
                        </td>
                        <td className="py-3 px-5 text-right font-mono font-bold text-[#20282D]">
                          {formatRupiah(cat.total_valuation)}
                        </td>
                        <td className="py-3 px-5 text-right">
                          <div className="flex items-center justify-end gap-2.5">
                            <span className="font-semibold text-xs text-[#879197] w-12 text-right">
                              {cat.percentage}%
                            </span>
                            <div className="w-24 bg-[#F1F4F5] rounded-full h-2 overflow-hidden">
                              <div
                                className="bg-[#55BFD8] h-full rounded-full transition-all"
                                style={{ width: `${Math.min(100, Math.max(0, cat.percentage))}%` }}
                              />
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
      <div className="bg-white border border-[#E7ECEE] rounded-2xl p-5 shadow-[0_2px_10px_rgba(0,0,0,0.02)] flex flex-col sm:flex-row items-center gap-3 justify-between">
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
            className="px-3.5 py-2 bg-[#F8FAFA] border border-[#E7ECEE] rounded-full text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
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
            className="px-3.5 py-2 bg-[#F8FAFA] border border-[#E7ECEE] rounded-full text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
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
              className="text-xs text-[#E47777] hover:underline px-2 py-1 font-bold cursor-pointer"
              title="Reset semua filter pencarian"
            >
              Reset Filter
            </button>
          )}
        </div>
      </div>

      {/* 4. Product Table Nilai Persediaan */}
      <div className="space-y-4">
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
            const avg = Number(item.average_cost) > 0 ? Number(item.average_cost) : Number(item.purchase_price || 0);
            const val = Math.max(0, stock) * avg;
            const sell = Number(item.selling_price) || 0;
            const profit = Math.max(0, stock) * (sell - avg);

            return (
              <tr key={item.id} className="hover:bg-[#F8FAFA] transition-colors border-b border-[#F1F4F5]">
                {/* Kode SKU & Barcode - Solid clean badge */}
                <td className="px-5 py-3.5 whitespace-nowrap align-middle">
                  <div className="flex flex-col items-start gap-1">
                    <span className="font-mono text-xs font-bold text-[#20282D] bg-[#F1F4F5] px-2.5 py-1 rounded-md border border-[#E7ECEE] whitespace-nowrap inline-block">
                      {item.sku}
                    </span>
                    {item.barcode && (
                      <span className="font-mono text-[10px] text-[#879197] whitespace-nowrap">
                        {item.barcode}
                      </span>
                    )}
                  </div>
                </td>

                {/* Daftar Produk */}
                <td className="px-5 py-3.5 text-left min-w-[220px] align-middle">
                  <div className="flex items-center gap-1.5 flex-wrap">
                    <span className="font-bold text-xs sm:text-sm text-[#20282D]">{item.name}</span>
                    {Boolean(item.is_consignment) && (
                      <span className="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200 whitespace-nowrap">
                        Konsinyasi (Titip Jual)
                      </span>
                    )}
                  </div>
                  <div className="text-[11px] text-[#879197] mt-0.5 whitespace-nowrap">
                    {item.category?.name || '-'}
                  </div>
                </td>

                {/* Stok Saat Ini - Solid clean status badge */}
                <td className="px-5 py-3.5 text-right whitespace-nowrap align-middle">
                  <div className="flex justify-end">
                    <span
                      className={`font-mono font-bold text-xs px-3 py-1 rounded-full whitespace-nowrap ${
                        isOut
                          ? 'bg-[#FDF3F3] text-[#E47777] border border-[#E47777]/30'
                          : isLow
                          ? 'bg-[#FEF7EB] text-[#E9B45D] border border-[#E9B45D]/30'
                          : 'bg-[#EBF7F2] text-[#48B58B] border border-[#48B58B]/30'
                      }`}
                    >
                      {stock.toLocaleString('id-ID')} {item.unit?.symbol || 'pcs'}
                    </span>
                  </div>
                </td>

                {/* Harga Modal Rata-rata */}
                <td className="px-5 py-3.5 text-right whitespace-nowrap align-middle">
                  <div className="font-mono text-xs font-semibold text-[#20282D]">
                    {formatRupiah(avg)}
                  </div>
                  <div className="text-[10px] text-[#879197] font-normal mt-0.5 whitespace-nowrap">
                    HPP Rata-rata
                  </div>
                </td>

                {/* Total Nilai Persediaan */}
                <td className="px-5 py-3.5 text-right whitespace-nowrap align-middle">
                  <div className="font-mono text-xs sm:text-sm font-black text-[#20282D]">
                    {formatRupiah(val)}
                  </div>
                  <div className="text-[10px] text-[#879197] font-normal mt-0.5 whitespace-nowrap">
                    Nilai Aset
                  </div>
                </td>

                {/* Harga Jual Satuan */}
                <td className="px-5 py-3.5 text-right whitespace-nowrap align-middle">
                  <div className="font-mono text-xs font-semibold text-[#20282D]">
                    {formatRupiah(sell)}
                  </div>
                </td>

                {/* Potensi Laba */}
                <td className="px-5 py-3.5 text-right whitespace-nowrap align-middle">
                  <div
                    className={`font-mono text-xs font-bold ${
                      profit > 0 ? 'text-[#48B58B]' : profit < 0 ? 'text-[#E47777]' : 'text-[#879197]'
                    }`}
                  >
                    {formatRupiah(profit)}
                  </div>
                </td>
              </tr>
            );
          }}
        />

        {pagination && pagination.total > pagination.per_page && (
          <Pagination
            currentPage={pagination.current_page}
            lastPage={pagination.last_page}
            total={pagination.total}
            perPage={pagination.per_page}
            onPageChange={(page) => setCurrentPage(page)}
          />
        )}
      </div>
    </div>
  );
}

export default InventoryValuationPage;
