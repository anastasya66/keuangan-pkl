import { useCallback, useEffect, useState } from 'react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import StatusBadge from '../../components/common/StatusBadge';
import useAuth from '../../hooks/useAuth';
import categoryService from '../../services/categoryService';
import productService from '../../services/productService';
import unitService from '../../services/unitService';
import { formatRupiah } from '../../utils/currency';
import ProductModal from './ProductModal';
import { IconBox } from '../../components/common/Icons';

export function ProductPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [units, setUnits] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);

  // Filters & Search
  const [search, setSearch] = useState('');
  const [categoryIdFilter, setCategoryIdFilter] = useState('');
  const [unitIdFilter, setUnitIdFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Notification Toast
  const [toast, setToast] = useState(null);

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Delete Dialog State
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  // Load Categories & Units for filter dropdowns
  useEffect(() => {
    async function loadFilterOptions() {
      try {
        const [catRes, unitRes] = await Promise.all([
          categoryService.getCategories({ all: true }),
          unitService.getUnits({ all: true }),
        ]);
        if (catRes.success) setCategories(catRes.data);
        if (unitRes.success) setUnits(unitRes.data);
      } catch (err) {
        console.error('Gagal memuat opsi filter:', err);
      }
    }
    loadFilterOptions();
  }, []);

  // Fetch products
  const fetchProducts = useCallback(
    async (
      page = 1,
      searchQuery = search,
      catId = categoryIdFilter,
      uId = unitIdFilter,
      status = statusFilter
    ) => {
      setIsLoading(true);
      try {
        const params = { page, search: searchQuery };
        if (catId) params.category_id = catId;
        if (uId) params.unit_id = uId;
        if (status !== '') params.is_active = status;

        const response = await productService.getProducts(params);
        if (response.success) {
          setProducts(response.data);
          setPagination(response.pagination);
        }
      } catch (error) {
        showToast(error.response?.data?.message || 'Gagal memuat daftar produk', 'error');
      } finally {
        setIsLoading(false);
      }
    },
    [search, categoryIdFilter, unitIdFilter, statusFilter]
  );

  useEffect(() => {
    fetchProducts(currentPage, search, categoryIdFilter, unitIdFilter, statusFilter);
  }, [currentPage, categoryIdFilter, unitIdFilter, statusFilter, fetchProducts]);

  const handleSearchChange = (value) => {
    setSearch(value);
    setCurrentPage(1);
  };

  const handleSearchClear = () => {
    setSearch('');
    setCurrentPage(1);
    fetchProducts(1, '', categoryIdFilter, unitIdFilter, statusFilter);
  };

  const handleResetFilters = () => {
    setSearch('');
    setCategoryIdFilter('');
    setUnitIdFilter('');
    setStatusFilter('');
    setCurrentPage(1);
    fetchProducts(1, '', '', '', '');
  };

  const handleFormSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedProduct) {
        await productService.updateProduct(selectedProduct.id, formData);
        showToast('Produk berhasil diperbarui!');
      } else {
        await productService.createProduct(formData);
        showToast('Produk baru berhasil ditambahkan!');
      }
      setIsModalOpen(false);
      setSelectedProduct(null);
      fetchProducts(currentPage);
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Gagal menyimpan produk';
      const errors = error.response?.data?.errors;
      showToast(message, 'error');
      return { success: false, errors };
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleToggleStatus = async (prod) => {
    try {
      const newStatus = !prod.is_active;
      await productService.updateProductStatus(prod.id, newStatus);
      showToast(`Produk ${prod.name} berhasil ${newStatus ? 'diaktifkan' : 'dinonaktifkan'}`);
      fetchProducts(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal mengubah status produk', 'error');
    }
  };

  const handleConfirmDelete = async () => {
    if (!deleteTarget) return;
    setIsDeleting(true);
    try {
      await productService.deleteProduct(deleteTarget.id);
      showToast(`Produk ${deleteTarget.name} berhasil dihapus.`);
      setDeleteTarget(null);
      fetchProducts(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal menghapus produk', 'error');
    } finally {
      setIsDeleting(false);
    }
  };

  const columns = [
    { header: 'Kode / SKU', className: 'whitespace-nowrap' },
    { header: 'Nama Barang', className: 'min-w-[200px]' },
    { header: 'Satuan', className: 'text-center whitespace-nowrap' },
    { header: 'Harga Beli', className: 'text-right whitespace-nowrap' },
    { header: 'Harga Jual', className: 'text-right whitespace-nowrap' },
    { header: 'Status', className: 'text-center whitespace-nowrap' },
    ...(isAdmin ? [{ header: 'Aksi', className: 'text-right whitespace-nowrap min-w-[210px]' }] : []),
  ];

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`p-4 rounded-2xl border flex items-center justify-between text-xs font-semibold shadow-md transition-all animate-toast ${
            toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-700'
              : 'bg-emerald-50 border-emerald-200 text-emerald-700'
          }`}
        >
          <div className="flex items-center gap-2">
            <span>{toast.type === 'error' ? '⚠️' : '✅'}</span>
            <span>{toast.message}</span>
          </div>
          <button
            type="button"
            onClick={() => setToast(null)}
            className="text-xs text-[#8EA6AF] hover:text-[#16323B] cursor-pointer btn-press"
          >
            ✕
          </button>
        </div>
      )}

      {/* Header & Controls */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-black tracking-tight text-[#10262E] flex items-center gap-2.5">
            <span className="p-2 rounded-xl bg-white border border-[#D7EBEF] text-[#27C5E5] shadow-2xs">
              <IconBox className="w-5 h-5" />
            </span>
            <span>Katalog Barang</span>
          </h1>
          <p className="text-xs text-[#577782] mt-0.5">
            Kelola daftar produk, kategori, dan harga barang.
          </p>
        </div>

        {isAdmin && (
          <button
            type="button"
            onClick={() => {
              setSelectedProduct(null);
              setIsModalOpen(true);
            }}
            className="px-4 py-2.5 rounded-xl bg-[#27C5E5] hover:bg-[#167A93] text-white font-semibold text-xs shadow-md shadow-[#27C5E5]/20 transition-all cursor-pointer flex items-center gap-2 self-start sm:self-auto btn-press"
          >
            <span>+ Tambah Barang</span>
          </button>
        )}
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-white p-4 rounded-2xl border border-[#D7EBEF] shadow-xs">
        <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
          <form
            onSubmit={(e) => {
              e.preventDefault();
              fetchProducts(1, search, categoryIdFilter, unitIdFilter, statusFilter);
            }}
            className="flex-1 max-w-md"
          >
            <SearchInput
              value={search}
              onChange={handleSearchChange}
              onClear={handleSearchClear}
              placeholder="Cari nama, SKU, atau barcode produk..."
            />
          </form>

          {/* Filter Dropdowns */}
          <div className="flex flex-wrap items-center gap-2.5">
            <select
              value={categoryIdFilter}
              onChange={(e) => {
                setCategoryIdFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="px-3 py-2 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:ring-2 focus:ring-[#38BFE0]/25 focus:outline-none"
            >
              <option value="">Semua Kategori</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>

            <select
              value={unitIdFilter}
              onChange={(e) => {
                setUnitIdFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="px-3 py-2 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:ring-2 focus:ring-[#38BFE0]/25 focus:outline-none"
            >
              <option value="">Semua Satuan</option>
              {units.map((u) => (
                <option key={u.id} value={u.id}>
                  {u.name} ({u.symbol})
                </option>
              ))}
            </select>

            {isAdmin && (
              <select
                value={statusFilter}
                onChange={(e) => {
                  setStatusFilter(e.target.value);
                  setCurrentPage(1);
                }}
                className="px-3 py-2 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:ring-2 focus:ring-[#38BFE0]/25 focus:outline-none"
              >
                <option value="">Semua Status</option>
                <option value="true">Hanya Aktif</option>
                <option value="false">Hanya Nonaktif</option>
              </select>
            )}

            {(search || categoryIdFilter || unitIdFilter || statusFilter) && (
              <button
                type="button"
                onClick={handleResetFilters}
                className="px-3 py-2 rounded-xl bg-[#F3FBFC] hover:bg-[#EAF8FA] text-[#577782] text-xs border border-[#D7EBEF] transition-colors cursor-pointer font-semibold"
                title="Reset seluruh filter"
              >
                Reset
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Products Data Table */}
      <DataTable
        columns={columns}
        data={products}
        isLoading={isLoading}
        emptyMessage="Belum ada data produk yang cocok dengan pencarian atau filter Anda."
        renderRow={(prod) => (
          <tr key={prod.id} className="hover:bg-[#F3FBFC] transition-colors">
            {/* SKU & Barcode */}
            <td className="px-4 py-3.5 whitespace-nowrap">
              <div className="font-mono text-xs font-bold text-[#167A93]">
                {prod.sku}
              </div>
              {prod.barcode && (
                <div className="font-mono text-[10px] text-[#8EA6AF] mt-0.5">
                  🏷️ {prod.barcode}
                </div>
              )}
            </td>

            {/* Name & Category */}
            <td className="px-4 py-3.5">
              <div className="font-bold text-xs sm:text-[13px] text-[#10262E] leading-snug">
                {prod.name}
              </div>
              <div className="inline-flex items-center gap-1.5 mt-1">
                <span className="text-[10px] px-2 py-0.5 rounded-md bg-[#F3FBFC] text-[#577782] border border-[#D7EBEF] font-medium whitespace-nowrap shrink-0">
                  {prod.category?.name || 'Tanpa Kategori'}
                </span>
                {prod.description && (
                  <span
                    className="text-[11px] text-[#8EA6AF] truncate max-w-[180px]"
                    title={prod.description}
                  >
                    • {prod.description}
                  </span>
                )}
              </div>
            </td>

            {/* Unit */}
            <td className="px-4 py-3.5 text-center whitespace-nowrap">
              <span className="inline-block px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-[#167A93] text-white whitespace-nowrap shadow-2xs">
                {prod.unit?.symbol || '-'}
              </span>
            </td>

            {/* Purchase Price */}
            <td className="px-4 py-3.5 text-right font-mono text-xs text-[#577782] whitespace-nowrap">
              {formatRupiah(prod.purchase_price_numeric)}
            </td>

            {/* Selling Price */}
            <td className="px-4 py-3.5 text-right font-mono text-xs font-bold text-[#167A93] whitespace-nowrap">
              {formatRupiah(prod.selling_price_numeric)}
            </td>

            {/* Status */}
            <td className="px-4 py-3.5 text-center whitespace-nowrap">
              <StatusBadge isActive={prod.is_active} />
            </td>

            {/* Actions (Admin Only) */}
            {isAdmin && (
              <td className="px-4 py-3.5 text-right whitespace-nowrap">
                <div className="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedProduct(prod);
                      setIsModalOpen(true);
                    }}
                    className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white hover:bg-[#F3FBFC] text-[#16323B] border border-[#D7EBEF] transition-colors cursor-pointer shadow-2xs"
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    onClick={() => handleToggleStatus(prod)}
                    className={`px-2.5 py-1 rounded-lg text-xs font-semibold border transition-colors cursor-pointer ${
                      prod.is_active
                        ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                        : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                    }`}
                  >
                    {prod.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>

                  <button
                    type="button"
                    onClick={() => setDeleteTarget(prod)}
                    className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 transition-colors cursor-pointer"
                  >
                    Hapus
                  </button>
                </div>
              </td>
            )}
          </tr>
        )}
      />

      {/* Pagination */}
      <Pagination
        pagination={pagination}
        onPageChange={(page) => setCurrentPage(page)}
      />

      {/* Product Create/Edit Modal */}
      <ProductModal
        isOpen={isModalOpen}
        product={selectedProduct}
        isLoading={isSubmitting}
        onClose={() => {
          setIsModalOpen(false);
          setSelectedProduct(null);
        }}
        onSubmit={handleFormSubmit}
      />

      {/* Confirm Delete Dialog */}
      <ConfirmDialog
        isOpen={Boolean(deleteTarget)}
        title="Hapus Produk"
        message={`Apakah Anda yakin ingin menghapus produk "${deleteTarget?.name}" (SKU: ${deleteTarget?.sku})? Data akan dihapus menggunakan mekanisme Soft Delete sehingga riwayat transaksi tetap aman.`}
        confirmText="Hapus Produk"
        confirmColor="danger"
        isLoading={isDeleting}
        onConfirm={handleConfirmDelete}
        onCancel={() => setDeleteTarget(null)}
      />
    </div>
  );
}

export default ProductPage;
