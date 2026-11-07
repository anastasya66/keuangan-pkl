import React, { useState, useEffect, useCallback } from 'react';
import inventoryValuationService from '../../services/inventoryValuationService';
import categoryService from '../../services/categoryService';
import { formatRupiah } from '../../utils/currency';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import { IconValuation } from '../../components/common/Icons';

export function InventoryValuationPage() {
  const [data, setData] = useState(null);
  const [categories, setCategories] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);

  // Filters
  const [search, setSearch] = useState('');
  const [categoryIdFilter, setCategoryIdFilter] = useState('');
  const [stockStatusFilter, setStockStatusFilter] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Toast
  const [toast, setToast] = useState(null);

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

  const handleExportExcel = () => {
    const params = {};
    if (search) params.search = search;
    if (categoryIdFilter) params.category_id = categoryIdFilter;
    if (stockStatusFilter) params.stock_status = stockStatusFilter;
    window.open(inventoryValuationService.getExportExcelUrl(params), '_blank');
  };

  const handleExportPdf = () => {
    const params = {};
    if (search) params.search = search;
    if (categoryIdFilter) params.category_id = categoryIdFilter;
    if (stockStatusFilter) params.stock_status = stockStatusFilter;
    window.open(inventoryValuationService.getExportPdfUrl(params), '_blank');
  };

  const columns = [
    { header: 'Kode SKU', className: 'w-36' },
    { header: 'Nama Produk', className: 'w-1/4' },
    { header: 'Stok Fisik', className: 'text-right' },
    { header: 'HPP Rata-rata (MWA)', className: 'text-right' },
    { header: 'Total Nilai Aset (HPP)', className: 'text-right' },
    { header: 'Harga Jual', className: 'text-right' },
    { header: 'Potensi Laba', className: 'text-right' },
  ];

  return (
    <div className="space-y-6">
      {/* Toast */}
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
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-[#16323B] flex items-center gap-2.5">
            <span className="p-2 bg-[#167A93] text-white rounded-xl shadow-xs">
              <IconValuation className="w-5 h-5" />
            </span>
            Valuasi Nilai Persediaan Barang
          </h1>
          <p className="text-xs text-[#577782] mt-1">
            Penilaian aset persediaan menggunakan metode Moving Weighted Average (Rata-rata Tertimbang Bergerak)
          </p>
        </div>

        {/* Export Buttons */}
        <div className="flex items-center gap-2">
          <button
            onClick={handleExportExcel}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-xs shadow-xs transition"
          >
            <span>📊</span>
            <span>Export Excel (.xls)</span>
          </button>
          <button
            onClick={handleExportPdf}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-[#167A93] hover:bg-[#105668] text-white font-semibold rounded-xl text-xs shadow-xs transition"
          >
            <span>📄</span>
            <span>Export PDF</span>
          </button>
        </div>
      </div>

      {/* Summary Stat Cards */}
      {data?.summary && (
        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs col-span-2">
            <span className="text-[11px] font-semibold text-[#167A93]">Total Nilai Aset Persediaan (HPP)</span>
            <div className="text-2xl font-bold font-mono text-[#167A93] mt-1">
              {formatRupiah(data.summary.total_valuation)}
            </div>
            <div className="text-[10px] text-[#577782] mt-1">
              Metode: Moving Weighted Average
            </div>
          </div>

          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
            <span className="text-[11px] font-semibold text-[#577782]">Total Unit Fisik</span>
            <div className="text-xl font-bold font-mono text-[#16323B] mt-1">
              {Number(data.summary.total_stock_units).toLocaleString('id-ID')}
            </div>
            <div className="text-[10px] text-[#577782] mt-1">{data.summary.total_products} Item Produk</div>
          </div>

          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
            <span className="text-[11px] font-semibold text-rose-600">Stok Menipis/Habis</span>
            <div className="text-xl font-bold font-mono text-rose-600 mt-1">
              {data.summary.low_stock_count + data.summary.out_of_stock_count}
            </div>
            <div className="text-[10px] text-[#577782] mt-1">
              Habis: {data.summary.out_of_stock_count} &bull; Menipis: {data.summary.low_stock_count}
            </div>
          </div>

          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
            <span className="text-[11px] font-semibold text-[#577782]">Potensi Omzet Penjualan</span>
            <div className="text-lg font-bold font-mono text-[#16323B] mt-1">
              {formatRupiah(data.summary.potential_revenue)}
            </div>
            <div className="text-[10px] text-[#577782] mt-1">Berdasarkan harga jual aktif</div>
          </div>

          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
            <span className="text-[11px] font-semibold text-emerald-700">Potensi Laba Kotor</span>
            <div className="text-lg font-bold font-mono text-emerald-600 mt-1">
              {formatRupiah(data.summary.potential_profit)}
            </div>
            <div className="text-[10px] text-[#577782] mt-1">Omzet dikurangi HPP</div>
          </div>
        </div>
      )}

      {/* Category Breakdown Chips / Cards */}
      {data?.category_breakdown && data.category_breakdown.length > 0 && (
        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
          <h3 className="text-xs font-bold uppercase tracking-wider text-[#577782] mb-3">
            Komposisi Persediaan Berdasarkan Kategori
          </h3>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            {data.category_breakdown.map((cat) => (
              <div
                key={cat.category_id}
                onClick={() => setCategoryIdFilter(cat.category_id === categoryIdFilter ? '' : cat.category_id)}
                className={`p-3 rounded-xl border transition cursor-pointer ${
                  String(categoryIdFilter) === String(cat.category_id)
                    ? 'bg-[#EAF8FA] border-[#27C5E5] shadow-xs'
                    : 'bg-[#F8FDFE] border-[#EAF4F6] hover:bg-white'
                }`}
              >
                <div className="font-bold text-xs text-[#16323B] truncate">{cat.category_name}</div>
                <div className="text-xs font-mono font-bold text-[#167A93] mt-1">
                  {formatRupiah(cat.total_valuation)}
                </div>
                <div className="text-[10px] text-[#577782] mt-0.5">
                  {cat.total_stock} unit &bull; {cat.percentage}%
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Filter and Search Bar */}
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
            className="px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
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
            className="px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
          >
            <option value="">Semua Kondisi Stok</option>
            <option value="available">Tersedia Aman</option>
            <option value="low_stock">Stok Menipis</option>
            <option value="out_of_stock">Stok Habis (0)</option>
          </select>
        </div>
      </div>

      {/* Product Table */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
        <DataTable
          columns={columns}
          data={data?.products || []}
          isLoading={isLoading}
          emptyMessage="Tidak ada data produk persediaan."
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
              <tr key={item.id} className="hover:bg-[#F3FBFC] transition-colors">
                <td className="px-4 py-3.5 whitespace-nowrap">
                  <span className="font-mono text-xs font-bold text-white bg-[#167A93] px-2.5 py-0.5 rounded shadow-2xs whitespace-nowrap inline-block">
                    {item.sku}
                  </span>
                  {item.barcode && (
                    <div className="font-mono text-[10px] text-[#577782] mt-0.5">{item.barcode}</div>
                  )}
                </td>
                <td className="px-4 py-3.5">
                  <div className="font-semibold text-[#16323B]">{item.name}</div>
                  <div className="text-[11px] text-[#577782]">{item.category?.name || '-'}</div>
                </td>
                <td className="px-4 py-3.5 text-right whitespace-nowrap">
                  <span
                    className={`font-mono font-bold text-xs px-2.5 py-0.5 rounded-md shadow-xs whitespace-nowrap inline-block ${
                      isOut
                        ? 'bg-rose-600 text-white'
                        : isLow
                        ? 'bg-amber-600 text-white'
                        : 'bg-[#167A93] text-white'
                    }`}
                  >
                    {stock.toLocaleString('id-ID')} {item.unit?.symbol || 'pcs'}
                  </span>
                </td>
                <td className="px-4 py-3.5 text-right font-mono text-xs font-semibold text-[#16323B] whitespace-nowrap">
                  {formatRupiah(avg)}
                </td>
                <td className="px-4 py-3.5 text-right font-mono text-xs font-bold text-[#167A93] whitespace-nowrap">
                  {formatRupiah(val)}
                </td>
                <td className="px-4 py-3.5 text-right font-mono text-xs text-[#577782] whitespace-nowrap">
                  {formatRupiah(item.selling_price)}
                </td>
                <td className="px-4 py-3.5 text-right font-mono text-xs font-bold text-emerald-600 whitespace-nowrap">
                  {formatRupiah(profit)}
                </td>
              </tr>
            );
          }}
        />

        {pagination && pagination.total > pagination.per_page && (
          <div className="mt-4 pt-4 border-t border-[#EAF4F6]">
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

