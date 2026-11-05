import { useCallback, useEffect, useState } from 'react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import StatusBadge from '../../components/common/StatusBadge';
import useAuth from '../../hooks/useAuth';
import categoryService from '../../services/categoryService';
import { formatDateTime } from '../../utils/currency';
import CategoryModal from './CategoryModal';

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

  // Delete Category
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
    { header: 'Nama Kategori', className: 'w-1/4' },
    { header: 'Deskripsi', className: 'w-1/3' },
    { header: 'Jml Produk', className: 'text-center' },
    { header: 'Status', className: 'text-center' },
    { header: 'Dibuat', className: 'hidden md:table-cell' },
    ...(isAdmin ? [{ header: 'Aksi', className: 'text-right' }] : []),
  ];

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`p-4 rounded-xl border flex items-center justify-between text-sm shadow-xl transition-all ${
            toast.type === 'error'
              ? 'bg-rose-500/10 border-rose-500/30 text-rose-300'
              : 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'
          }`}
        >
          <div className="flex items-center gap-2">
            <span>{toast.type === 'error' ? '⚠️' : '✅'}</span>
            <span className="font-medium">{toast.message}</span>
          </div>
          <button
            type="button"
            onClick={() => setToast(null)}
            className="text-xs text-slate-400 hover:text-white"
          >
            ✕
          </button>
        </div>
      )}

      {/* Header & Controls */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <span>🏷️</span> Manajemen Kategori
          </h1>
          <p className="text-xs text-slate-400 mt-1">
            Kelola pengelompokan produk toko agar pencatatan dan katalog lebih terstruktur
          </p>
        </div>

        {isAdmin && (
          <button
            type="button"
            onClick={() => {
              setSelectedCategory(null);
              setIsModalOpen(true);
            }}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-semibold text-sm transition-all shadow-lg shadow-emerald-900/30 cursor-pointer self-start sm:self-auto"
          >
            <span>+</span> Tambah Kategori
          </button>
        )}
      </div>

      {/* Filter and Search Bar */}
      <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
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
            <span className="text-xs text-slate-400">Status:</span>
            <select
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            >
              <option value="">Semua Status</option>
              <option value="true">Hanya Aktif</option>
              <option value="false">Hanya Nonaktif</option>
            </select>
          </div>
        )}
      </div>

      {/* Categories Data Table */}
      <DataTable
        columns={columns}
        data={categories}
        isLoading={isLoading}
        emptyMessage="Belum ada data kategori yang cocok dengan pencarian Anda."
        renderRow={(cat) => (
          <tr key={cat.id} className="hover:bg-slate-800/40 transition-colors">
            <td className="px-4 py-3.5 font-semibold text-white">
              {cat.name}
            </td>

            <td className="px-4 py-3.5 text-xs text-slate-300">
              {cat.description || <span className="text-slate-500 italic">Tidak ada deskripsi</span>}
            </td>

            <td className="px-4 py-3.5 text-center text-xs font-semibold text-slate-300">
              <span className="px-2 py-0.5 rounded-full bg-slate-800 border border-slate-700">
                {cat.products_count ?? 0} produk
              </span>
            </td>

            <td className="px-4 py-3.5 text-center">
              <StatusBadge isActive={cat.is_active} />
            </td>

            <td className="px-4 py-3.5 text-xs text-slate-400 hidden md:table-cell">
              {formatDateTime(cat.created_at)}
            </td>

            {isAdmin && (
              <td className="px-4 py-3.5 text-right whitespace-nowrap">
                <div className="inline-flex items-center gap-1.5">
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedCategory(cat);
                      setIsModalOpen(true);
                    }}
                    className="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer"
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    onClick={() => handleToggleStatus(cat)}
                    className={`px-2.5 py-1 rounded-lg text-xs font-medium border transition-colors cursor-pointer ${
                      cat.is_active
                        ? 'bg-amber-500/10 text-amber-400 border-amber-500/30 hover:bg-amber-500/20'
                        : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/20'
                    }`}
                  >
                    {cat.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>

                  <button
                    type="button"
                    onClick={() => setDeleteTarget(cat)}
                    className="px-2.5 py-1 rounded-lg text-xs font-medium bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 transition-colors cursor-pointer"
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
