import { useCallback, useEffect, useState } from 'react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import StatusBadge from '../../components/common/StatusBadge';
import useAuth from '../../hooks/useAuth';
import unitService from '../../services/unitService';
import { formatDateTime } from '../../utils/currency';
import UnitModal from './UnitModal';

export function UnitPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const [units, setUnits] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Notification Toast
  const [toast, setToast] = useState(null);

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedUnit, setSelectedUnit] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Delete Dialog State
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchUnits = useCallback(async (page = 1, searchQuery = search, status = statusFilter) => {
    setIsLoading(true);
    try {
      const params = { page, search: searchQuery };
      if (status !== '') params.is_active = status;

      const response = await unitService.getUnits(params);
      if (response.success) {
        setUnits(response.data);
        setPagination(response.pagination);
      }
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memuat daftar satuan', 'error');
    } finally {
      setIsLoading(false);
    }
  }, [search, statusFilter]);

  useEffect(() => {
    fetchUnits(currentPage, search, statusFilter);
  }, [currentPage, statusFilter, fetchUnits]);

  const handleSearchChange = (value) => {
    setSearch(value);
    setCurrentPage(1);
  };

  const handleSearchClear = () => {
    setSearch('');
    setCurrentPage(1);
    fetchUnits(1, '', statusFilter);
  };

  const handleFormSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedUnit) {
        await unitService.updateUnit(selectedUnit.id, formData);
        showToast('Satuan produk berhasil diperbarui!');
      } else {
        await unitService.createUnit(formData);
        showToast('Satuan baru berhasil ditambahkan!');
      }
      setIsModalOpen(false);
      setSelectedUnit(null);
      fetchUnits(currentPage);
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Gagal menyimpan satuan';
      const errors = error.response?.data?.errors;
      showToast(message, 'error');
      return { success: false, errors };
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleToggleStatus = async (unit) => {
    try {
      const newStatus = !unit.is_active;
      await unitService.updateUnitStatus(unit.id, newStatus);
      showToast(`Satuan ${unit.name} berhasil ${newStatus ? 'diaktifkan' : 'dinonaktifkan'}`);
      fetchUnits(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal mengubah status satuan', 'error');
    }
  };

  const handleConfirmDelete = async () => {
    if (!deleteTarget) return;
    setIsDeleting(true);
    try {
      await unitService.deleteUnit(deleteTarget.id);
      showToast(`Satuan ${deleteTarget.name} berhasil dihapus.`);
      setDeleteTarget(null);
      fetchUnits(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal menghapus satuan', 'error');
    } finally {
      setIsDeleting(false);
    }
  };

  const columns = [
    { header: 'Nama Satuan', className: 'w-1/4' },
    { header: 'Simbol', className: 'w-24 text-center' },
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
            <span>📏</span> Manajemen Satuan Produk
          </h1>
          <p className="text-xs text-slate-400 mt-1">
            Standarisasi ukuran dan satuan kemasan produk toko (pcs, kg, liter, botol, pack, dus)
          </p>
        </div>

        {isAdmin && (
          <button
            type="button"
            onClick={() => {
              setSelectedUnit(null);
              setIsModalOpen(true);
            }}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-semibold text-sm transition-all shadow-lg shadow-emerald-900/30 cursor-pointer self-start sm:self-auto"
          >
            <span>+</span> Tambah Satuan
          </button>
        )}
      </div>

      {/* Filter and Search Bar */}
      <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
        <form
          onSubmit={(e) => {
            e.preventDefault();
            fetchUnits(1, search, statusFilter);
          }}
          className="flex-1 max-w-md"
        >
          <SearchInput
            value={search}
            onChange={handleSearchChange}
            onClear={handleSearchClear}
            placeholder="Cari nama atau simbol satuan..."
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

      {/* Units Data Table */}
      <DataTable
        columns={columns}
        data={units}
        isLoading={isLoading}
        emptyMessage="Belum ada data satuan yang cocok dengan pencarian Anda."
        renderRow={(unit) => (
          <tr key={unit.id} className="hover:bg-slate-800/40 transition-colors">
            <td className="px-4 py-3.5 font-semibold text-white">
              {unit.name}
            </td>

            <td className="px-4 py-3.5 text-center">
              <span className="px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/30">
                {unit.symbol}
              </span>
            </td>

            <td className="px-4 py-3.5 text-xs text-slate-300">
              {unit.description || <span className="text-slate-500 italic">Tidak ada deskripsi</span>}
            </td>

            <td className="px-4 py-3.5 text-center text-xs font-semibold text-slate-300">
              <span className="px-2 py-0.5 rounded-full bg-slate-800 border border-slate-700">
                {unit.products_count ?? 0} produk
              </span>
            </td>

            <td className="px-4 py-3.5 text-center">
              <StatusBadge isActive={unit.is_active} />
            </td>

            <td className="px-4 py-3.5 text-xs text-slate-400 hidden md:table-cell">
              {formatDateTime(unit.created_at)}
            </td>

            {isAdmin && (
              <td className="px-4 py-3.5 text-right whitespace-nowrap">
                <div className="inline-flex items-center gap-1.5">
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedUnit(unit);
                      setIsModalOpen(true);
                    }}
                    className="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer"
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    onClick={() => handleToggleStatus(unit)}
                    className={`px-2.5 py-1 rounded-lg text-xs font-medium border transition-colors cursor-pointer ${
                      unit.is_active
                        ? 'bg-amber-500/10 text-amber-400 border-amber-500/30 hover:bg-amber-500/20'
                        : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/20'
                    }`}
                  >
                    {unit.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>

                  <button
                    type="button"
                    onClick={() => setDeleteTarget(unit)}
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

      {/* Unit Create/Edit Modal */}
      <UnitModal
        isOpen={isModalOpen}
        unit={selectedUnit}
        isLoading={isSubmitting}
        onClose={() => {
          setIsModalOpen(false);
          setSelectedUnit(null);
        }}
        onSubmit={handleFormSubmit}
      />

      {/* Confirm Delete Dialog */}
      <ConfirmDialog
        isOpen={Boolean(deleteTarget)}
        title="Hapus Satuan Produk"
        message={`Apakah Anda yakin ingin menghapus satuan "${deleteTarget?.name} (${deleteTarget?.symbol})"? Penghapusan akan ditolak jika masih terdapat produk yang menggunakan satuan ini.`}
        confirmText="Hapus Satuan"
        confirmColor="danger"
        isLoading={isDeleting}
        onConfirm={handleConfirmDelete}
        onCancel={() => setDeleteTarget(null)}
      />
    </div>
  );
}

export default UnitPage;

