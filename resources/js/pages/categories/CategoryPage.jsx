import { useCallback, useEffect, useState } from 'react';
import { Plus } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import StatusBadge from '../../components/common/StatusBadge';
import PageHeader from '../../components/ui/PageHeader';
import useAuth from '../../hooks/useAuth';
import categoryService from '../../services/categoryService';
import { formatDateTime } from '../../utils/currency';
import CategoryModal from './CategoryModal';
import { IconTag } from '../../components/common/Icons';

export function CategoryPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const [categories, setCategories] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Notifications
  const [toast, setToast] = useState(null);

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Confirm Delete Dialog
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);

  // Show Toast Helper
  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  // Fetch categories
  const fetchCategories = useCallback(async (page = 1, searchQuery = search, status = statusFilter) => {
    setIsLoading(true);
    try {
      const params = { page, search: searchQuery };
      if (status !== '') params.is_active = status;

      const response = await categoryService.getCategories(params);
      if (response.success) {
        setCategories(response.data);
        setPagination(response.pagination);
      }
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memuat daftar kategori', 'error');
    } finally {
      setIsLoading(false);
    }
  }, [search, statusFilter]);

  useEffect(() => {
    fetchCategories(currentPage, search, statusFilter);
  }, [currentPage, statusFilter, fetchCategories]);

  // Handle Search Submit / Debounce
  const handleSearchChange = (value) => {
    setSearch(value);
    setCurrentPage(1);
  };

  const handleSearchClear = () => {
    setSearch('');
    setCurrentPage(1);
    fetchCategories(1, '', statusFilter);
  };

  // Create or Update
  const handleFormSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedCategory) {
        await categoryService.updateCategory(selectedCategory.id, formData);
        showToast('Kategori berhasil diperbarui!');
      } else {
        await categoryService.createCategory(formData);
        showToast('Kategori baru berhasil ditambahkan!');
      }
      setIsModalOpen(false);
      setSelectedCategory(null);
      fetchCategories(currentPage);
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Gagal menyimpan kategori';
      const errors = error.response?.data?.errors;
      showToast(message, 'error');
      return { success: false, errors };
    } finally {
      setIsSubmitting(false);
    }
  };

  // Toggle Status
  const handleToggleStatus = async (cat) => {
    try {
      const newStatus = !cat.is_active;
      await categoryService.updateCategoryStatus(cat.id, newStatus);
      showToast(`Kategori ${cat.name} berhasil ${newStatus ? 'diaktifkan' : 'dinonaktifkan'}`);
      fetchCategories(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal mengubah status kategori', 'error');
    }
  };

  // Confirm Delete
  const handleConfirmDelete = async () => {
    if (!deleteTarget) return;
    setIsDeleting(true);
    try {
      await categoryService.deleteCategory(deleteTarget.id);
      showToast(`Kategori ${deleteTarget.name} berhasil dihapus.`);
      setDeleteTarget(null);
      fetchCategories(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal menghapus kategori', 'error');
    } finally {
      setIsDeleting(false);
    }
  };

  const columns = [
    { header: 'Nama Kategori', className: 'min-w-[180px] text-left' },
    { header: 'Deskripsi', className: 'min-w-[200px] text-left' },
    { header: 'Jumlah Produk', className: 'text-center whitespace-nowrap' },
    { header: 'Status', className: 'text-center whitespace-nowrap' },
    { header: 'Dibuat Pada', className: 'hidden md:table-cell whitespace-nowrap text-left' },
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
      <PageHeader
        title="Kategori Barang"
        subtitle="Kelola pengelompokan produk untuk pencatatan stok dan laporan keuangan."
      >
        {isAdmin && (
          <button
            type="button"
            onClick={() => {
              setSelectedCategory(null);
              setIsModalOpen(true);
            }}
            className="flex items-center gap-2 px-4 py-2 rounded-full bg-[#20282D] hover:bg-black text-white font-bold text-xs shadow-xs transition-all cursor-pointer"
          >
            <Plus className="w-4 h-4 stroke-[2.5]" />
            <span>Tambah Kategori</span>
          </button>
        )}
      </PageHeader>

      {/* Filter and Search Bar */}
      <div className="bg-white p-4 rounded-2xl border border-[#D7EBEF] shadow-xs">
        <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <form
            onSubmit={(e) => {
              e.preventDefault();
              fetchCategories(1, search, statusFilter);
            }}
            className="flex-1 max-w-md"
          >
            <SearchInput
              value={search}
              onChange={handleSearchChange}
              onClear={handleSearchClear}
              placeholder="Cari nama atau deskripsi kategori..."
            />
          </form>

          {isAdmin && (
            <div className="flex items-center gap-2">
              <span className="text-xs font-bold text-[#577782]">Status:</span>
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
            </div>
          )}
        </div>
      </div>

      {/* Categories Data Table */}
      <DataTable
        columns={columns}
        data={categories}
        isLoading={isLoading}
        emptyMessage="Belum ada data kategori yang cocok dengan pencarian Anda."
        renderRow={(cat) => (
          <tr key={cat.id} className="hover:bg-[#F3FBFC] transition-colors">
            <td className="px-4 py-3.5 font-bold text-xs sm:text-[13px] text-[#10262E] whitespace-nowrap">
              {cat.name}
            </td>

            <td className="px-4 py-3.5 text-xs text-[#577782] min-w-[200px]">
              {cat.description || <span className="text-[#8EA6AF] italic">Tidak ada deskripsi</span>}
            </td>

            <td className="px-4 py-3.5 text-center whitespace-nowrap">
              <span className="inline-flex items-center justify-center px-3 py-1 rounded-full bg-[#167A93] text-white text-xs font-bold whitespace-nowrap shadow-2xs">
                {cat.products_count ?? 0} produk
              </span>
            </td>

            <td className="px-4 py-3.5 text-center whitespace-nowrap">
              <StatusBadge isActive={cat.is_active} />
            </td>

            <td className="px-4 py-3.5 text-xs text-[#577782] hidden md:table-cell whitespace-nowrap font-medium">
              {formatDateTime(cat.created_at)}
            </td>

            {isAdmin && (
              <td className="px-4 py-3.5 text-right whitespace-nowrap min-w-[210px]">
                <div className="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedCategory(cat);
                      setIsModalOpen(true);
                    }}
                    className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white hover:bg-[#F3FBFC] text-[#16323B] border border-[#D7EBEF] transition-colors cursor-pointer shadow-2xs"
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    onClick={() => handleToggleStatus(cat)}
                    className={`px-2.5 py-1 rounded-lg text-xs font-semibold border transition-colors cursor-pointer ${
                      cat.is_active
                        ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                        : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                    }`}
                  >
                    {cat.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>

                  <button
                    type="button"
                    onClick={() => setDeleteTarget(cat)}
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

      {/* Category Create/Edit Modal */}
      <CategoryModal
        isOpen={isModalOpen}
        category={selectedCategory}
        isLoading={isSubmitting}
        onClose={() => {
          setIsModalOpen(false);
          setSelectedCategory(null);
        }}
        onSubmit={handleFormSubmit}
      />

      {/* Confirm Delete Dialog */}
      <ConfirmDialog
        isOpen={Boolean(deleteTarget)}
        title="Hapus Kategori"
        message={`Apakah Anda yakin ingin menghapus kategori "${deleteTarget?.name}"? Penghapusan akan ditolak jika masih terdapat produk yang menggunakan kategori ini.`}
        confirmText="Hapus Kategori"
        confirmColor="danger"
        isLoading={isDeleting}
        onConfirm={handleConfirmDelete}
        onCancel={() => setDeleteTarget(null)}
      />
    </div>
  );
}

export default CategoryPage;
