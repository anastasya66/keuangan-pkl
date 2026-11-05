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
import { IconRuler } from '../../components/common/Icons';

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
        showToast('Satuan ukuran berhasil diperbarui!');
      } else {
        await unitService.createUnit(formData);
        showToast('Satuan ukuran baru berhasil ditambahkan!');
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
    { header: 'Simbol', className: 'text-center' },
    { header: 'Deskripsi', className: 'w-1/3' },
    { header: 'Jumlah Produk', className: 'text-center' },
    { header: 'Status', className: 'text-center' },
    { header: 'Dibuat Pada', className: 'hidden md:table-cell' },
    ...(isAdmin ? [{ header: 'Aksi', className: 'text-right' }] : []),
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
              <IconRuler className="w-5 h-5" />
            </span>
            <span>Satuan Ukuran Barang</span>
          </h1>
          <p className="text-xs text-[#577782] mt-1">
            Atur satuan ukuran kemasan barang toko (contoh: kg, pcs, botol, dus, bungkus)
          </p>
        </div>

        {isAdmin && (
          <button
            type="button"
            onClick={() => {
              setSelectedUnit(null);
              setIsModalOpen(true);
            }}
            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-semibold text-xs transition-all shadow-md shadow-[#27C5E5]/20 cursor-pointer self-start sm:self-auto btn-press"
          >
            <span>+ Tambah Satuan Baru</span>
          </button>
        )}
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-white p-4 rounded-3xl border border-[#D7EBEF] shadow-xs">
        <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
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

      {/* Units Data Table */}
      <DataTable
        columns={columns}
        data={units}
        isLoading={isLoading}
        emptyMessage="Belum ada data satuan yang cocok dengan pencarian Anda."
        renderRow={(unit) => (
          <tr key={unit.id} className="hover:bg-[#F3FBFC] transition-colors">
            <td className="px-4 py-3.5 font-bold text-[#10262E]">
              {unit.name}
            </td>

            <td className="px-4 py-3.5 text-center">
              <span className="px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-[#EAF8FA] text-[#167A93] border border-[#38BFE0]/30">
                {unit.symbol}
              </span>
            </td>

            <td className="px-4 py-3.5 text-xs text-[#577782]">
              {unit.description || <span className="text-[#8EA6AF] italic">Tidak ada deskripsi</span>}
            </td>

            <td className="px-4 py-3.5 text-center text-xs font-semibold text-[#577782]">
              <span className="px-2.5 py-0.5 rounded-full bg-[#EAF8FA] border border-[#38BFE0]/30 text-[#167A93]">
                {unit.products_count ?? 0} produk
              </span>
            </td>

            <td className="px-4 py-3.5 text-center">
              <StatusBadge isActive={unit.is_active} />
            </td>

            <td className="px-4 py-3.5 text-xs text-[#8EA6AF] hidden md:table-cell">
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
                    className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white hover:bg-[#F3FBFC] text-[#16323B] border border-[#D7EBEF] transition-colors cursor-pointer shadow-2xs"
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    onClick={() => handleToggleStatus(unit)}
                    className={`px-2.5 py-1 rounded-lg text-xs font-semibold border transition-colors cursor-pointer ${
                      unit.is_active
                        ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                        : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                    }`}
                  >
                    {unit.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>

                  <button
                    type="button"
                    onClick={() => setDeleteTarget(unit)}
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
        title="Hapus Satuan"
        message={`Apakah Anda yakin ingin menghapus satuan "${deleteTarget?.name}" (${deleteTarget?.symbol})? Penghapusan akan ditolak jika masih terdapat produk yang menggunakan satuan ini.`}
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
