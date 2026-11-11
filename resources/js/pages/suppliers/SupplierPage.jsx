import React, { useState, useEffect, useCallback } from 'react';
import { Plus } from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import supplierService from '../../services/supplierService';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import StatusBadge from '../../components/common/StatusBadge';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import PageHeader from '../../components/ui/PageHeader';
import SupplierModal from './SupplierModal';
import { IconSupplier } from '../../components/common/Icons';

export function SupplierPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const [suppliers, setSuppliers] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedSupplier, setSelectedSupplier] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Delete State
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);

  // Notification Toast
  const [toast, setToast] = useState(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchSuppliers = useCallback(
    async (page = 1, searchQuery = search, status = statusFilter) => {
      setIsLoading(true);
      try {
        const params = { page, search: searchQuery };
        if (status !== '') params.is_active = status;

        const response = await supplierService.getSuppliers(params);
        if (response.success) {
          setSuppliers(response.data);
          setPagination(response.pagination);
        }
      } catch (error) {
        showToast(error.response?.data?.message || 'Gagal memuat data supplier', 'error');
      } finally {
        setIsLoading(false);
      }
    },
    [search, statusFilter]
  );

  useEffect(() => {
    fetchSuppliers(currentPage, search, statusFilter);
  }, [currentPage, search, statusFilter, fetchSuppliers]);

  const handleOpenAddModal = () => {
    setSelectedSupplier(null);
    setIsModalOpen(true);
  };

  const handleOpenEditModal = (supplier) => {
    setSelectedSupplier(supplier);
    setIsModalOpen(true);
  };

  const handleSubmitModal = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedSupplier) {
        await supplierService.updateSupplier(selectedSupplier.id, formData);
        showToast('Data supplier berhasil diperbarui');
      } else {
        await supplierService.createSupplier(formData);
        showToast('Supplier baru berhasil ditambahkan');
      }
      setIsModalOpen(false);
      fetchSuppliers(currentPage, search, statusFilter);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal menyimpan supplier', 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDeleteConfirm = async () => {
    if (!deleteTarget) return;
    setIsDeleting(true);
    try {
      await supplierService.deleteSupplier(deleteTarget.id);
      showToast('Supplier berhasil dihapus');
      setDeleteTarget(null);
      fetchSuppliers(currentPage, search, statusFilter);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal menghapus supplier', 'error');
    } finally {
      setIsDeleting(false);
    }
  };

  const columns = [
    {
      key: 'code',
      label: 'Kode',
      className: 'whitespace-nowrap',
      render: (item) => (
        <span className="font-mono text-xs font-bold text-white bg-[#167A93] px-2.5 py-0.5 rounded-md shadow-2xs whitespace-nowrap inline-block">
          {item.code}
        </span>
      ),
    },
    {
      key: 'name',
      label: 'Nama Supplier',
      className: 'min-w-[220px]',
      render: (item) => (
        <div className="min-w-[200px] space-y-0.5">
          <div className="font-semibold text-xs sm:text-[13px] text-[#16323B] leading-snug">
            {item.name}
          </div>
          {item.contact_person && (
            <div className="text-[11px] text-[#577782] font-medium leading-tight">
              PIC: {item.contact_person}
            </div>
          )}
        </div>
      ),
    },
    {
      key: 'phone',
      label: 'Kontak',
      className: 'min-w-[190px]',
      render: (item) => (
        <div className="text-xs space-y-1">
          {item.phone && (
            <div className="flex items-center gap-1.5 whitespace-nowrap text-[#16323B]">
              <span>📞</span>
              <span>{item.phone}</span>
            </div>
          )}
          {item.email && (
            <div className="flex items-center gap-1.5 whitespace-nowrap text-[#577782]">
              <span>✉️</span>
              <span>{item.email}</span>
            </div>
          )}
          {!item.phone && !item.email && <span className="text-[#8FA8B0]">-</span>}
        </div>
      ),
    },
    {
      key: 'address',
      label: 'Alamat',
      className: 'max-w-[200px]',
      render: (item) => (
        <div className="text-xs text-[#577782] max-w-[200px] truncate" title={item.address}>
          {item.address || '-'}
        </div>
      ),
    },
    {
      key: 'purchases_count',
      label: 'Transaksi',
      align: 'center',
      className: 'whitespace-nowrap text-center',
      render: (item) => (
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full bg-[#EAF8FA] text-[#167A93] border border-[#38BFE0]/30 text-xs font-bold whitespace-nowrap shadow-2xs">
          {item.purchases_count ?? 0} transaksi
        </span>
      ),
    },
    {
      key: 'is_active',
      label: 'Status',
      className: 'whitespace-nowrap',
      render: (item) => <StatusBadge isActive={item.is_active} />,
    },
    {
      key: 'actions',
      label: 'Aksi',
      align: 'right',
      className: 'min-w-[130px] text-right whitespace-nowrap',
      render: (item) => (
        <div className="flex items-center justify-end gap-1.5 whitespace-nowrap">
          {isAdmin && (
            <>
              <button
                onClick={() => handleOpenEditModal(item)}
                className="px-2.5 py-1 text-xs font-semibold text-[#167A93] bg-[#F0F9FB] hover:bg-[#EAF8FA] rounded-lg border border-[#D7EBEF]/60 transition cursor-pointer"
              >
                Edit
              </button>
              <button
                onClick={() => setDeleteTarget(item)}
                className="px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50/70 hover:bg-rose-100/70 rounded-lg border border-rose-200/60 transition cursor-pointer"
              >
                Hapus
              </button>
            </>
          )}
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2 animate-bounce-short ${
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
      <PageHeader
        title="Data Supplier"
        subtitle="Kelola daftar pemasok dan kontak distributor toko."
      >
        {isAdmin && (
          <button
            onClick={handleOpenAddModal}
            className="flex items-center gap-2 px-4 py-2 bg-[#20282D] hover:bg-black text-white font-bold rounded-full text-xs shadow-xs transition cursor-pointer"
          >
            <Plus className="w-4 h-4 stroke-[2.5]" />
            <span>+ Tambah Supplier</span>
          </button>
        )}
      </PageHeader>

      {/* Filter and Search */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs flex flex-col sm:flex-row items-center gap-3 justify-between">
        <div className="w-full sm:w-72">
          <SearchInput
            value={search}
            onChange={(val) => {
              setSearch(val);
              setCurrentPage(1);
            }}
            placeholder="Cari nama, kode, kontak..."
          />
        </div>

        <div className="flex items-center gap-2 w-full sm:w-auto">
          <select
            value={statusFilter}
            onChange={(e) => {
              setStatusFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="w-full sm:w-40 px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
          >
            <option value="">Semua Status</option>
            <option value="true">Aktif</option>
            <option value="false">Nonaktif</option>
          </select>
        </div>
      </div>

      {/* Data Table */}
      <div className="space-y-4">
        <DataTable
          columns={columns}
          data={suppliers}
          isLoading={isLoading}
          emptyMessage="Belum ada data supplier yang terdaftar."
        />

        {pagination && pagination.total > pagination.per_page && (
          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
            <Pagination
              currentPage={pagination.current_page}
              lastPage={pagination.last_page}
              total={pagination.total}
              perPage={pagination.per_page}
              onPageChange={(page) => setCurrentPage(page)}
            />
          </div>
        )}
      </div>

      {/* Modal Add / Edit */}
      <SupplierModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSubmit={handleSubmitModal}
        supplier={selectedSupplier}
        isLoading={isSubmitting}
      />

      {/* Confirm Delete Dialog */}
      <ConfirmDialog
        isOpen={Boolean(deleteTarget)}
        title="Hapus Data Supplier"
        message={`Apakah Anda yakin ingin menghapus supplier "${deleteTarget?.name}"? Tindakan ini tidak dapat dibatalkan.`}
        confirmText="Hapus Supplier"
        confirmVariant="danger"
        isLoading={isDeleting}
        onConfirm={handleDeleteConfirm}
        onCancel={() => setDeleteTarget(null)}
      />
    </div>
  );
}

export default SupplierPage;
