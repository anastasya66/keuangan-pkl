import { useCallback, useEffect, useState } from 'react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import StatusBadge from '../../components/common/StatusBadge';
import useAuth from '../../hooks/useAuth';
import accountingService from '../../services/accountingService';
import AccountModal from './AccountModal';

export function ChartOfAccountsPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const [accounts, setAccounts] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Notifications
  const [toast, setToast] = useState(null);

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedAccount, setSelectedAccount] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Confirm Delete Dialog
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchAccounts = useCallback(async (page = 1, searchQuery = search, type = typeFilter, status = statusFilter) => {
    setIsLoading(true);
    try {
      const params = { page, search: searchQuery };
      if (type !== '') params.account_type = type;
      if (status !== '') params.is_active = status;

      const response = await accountingService.getAccounts(params);
      if (response.status === 'success') {
        setAccounts(response.data);
        setPagination(response.meta);
      }
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memuat daftar akun', 'error');
    } finally {
      setIsLoading(false);
    }
  }, [search, typeFilter, statusFilter]);

  useEffect(() => {
    fetchAccounts(currentPage, search, typeFilter, statusFilter);
  }, [currentPage, typeFilter, statusFilter, fetchAccounts]);

  const handleFormSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedAccount) {
        await accountingService.updateAccount(selectedAccount.id, formData);
        showToast('Akun akuntansi berhasil diperbarui!');
      } else {
        await accountingService.createAccount(formData);
        showToast('Akun akuntansi baru berhasil ditambahkan!');
      }
      setIsModalOpen(false);
      setSelectedAccount(null);
      fetchAccounts(currentPage);
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Gagal menyimpan akun';
      const errors = error.response?.data?.errors;
      showToast(message, 'error');
      return { success: false, errors };
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleToggleStatus = async (acc) => {
    try {
      const newStatus = !acc.is_active;
      await accountingService.updateAccountStatus(acc.id, newStatus);
      showToast(`Akun ${acc.account_code} berhasil ${newStatus ? 'diaktifkan' : 'dinonaktifkan'}`);
      fetchAccounts(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal mengubah status akun', 'error');
    }
  };

  const handleConfirmDelete = async () => {
    if (!deleteTarget) return;
    setIsDeleting(true);
    try {
      await accountingService.deleteAccount(deleteTarget.id);
      showToast(`Akun ${deleteTarget.account_code} - ${deleteTarget.account_name} berhasil dihapus.`);
      setDeleteTarget(null);
      fetchAccounts(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal menghapus akun', 'error');
    } finally {
      setIsDeleting(false);
    }
  };

  const getTypeBadge = (type) => {
    const badges = {
      assets: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
      liabilities: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
      equity: 'bg-purple-500/10 text-purple-400 border-purple-500/30',
      revenue: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
      expenses: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
    };
    return badges[type] || 'bg-slate-800 text-slate-400 border-slate-700';
  };

  const columns = [
    { header: 'Kode', className: 'w-24 font-mono font-bold' },
    { header: 'Nama Akun', className: 'w-1/3' },
    { header: 'Kategori', className: 'text-center' },
    { header: 'Saldo Normal', className: 'text-center font-mono text-xs' },
    { header: 'Status', className: 'text-center' },
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

      {/* Header Page */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <span>Daftar Akun (Chart of Accounts)</span>
          </h1>
          <p className="text-sm text-slate-400 mt-0.5">
            Kelola kode akun akuntansi terstandar dengan prinsip double-entry bookkeeping.
          </p>
        </div>

        {isAdmin && (
          <button
            type="button"
            onClick={() => {
              setSelectedAccount(null);
              setIsModalOpen(true);
            }}
            className="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-all cursor-pointer shadow-lg shadow-emerald-500/20 flex items-center gap-2"
          >
            <span>+ Tambah Akun</span>
          </button>
        )}
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row gap-3 items-center justify-between">
        <div className="w-full md:w-72">
          <SearchInput
            value={search}
            onChange={(val) => {
              setSearch(val);
              setCurrentPage(1);
            }}
            onClear={() => {
              setSearch('');
              setCurrentPage(1);
            }}
            placeholder="Cari kode atau nama akun..."
          />
        </div>

        <div className="flex flex-wrap items-center gap-2 w-full md:w-auto">
          {/* Filter Kategori */}
          <select
            value={typeFilter}
            onChange={(e) => {
              setTypeFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500"
          >
            <option value="">Semua Kategori</option>
            <option value="assets">Assets (Aset)</option>
            <option value="liabilities">Liabilities (Kewajiban)</option>
            <option value="equity">Equity (Modal)</option>
            <option value="revenue">Revenue (Pendapatan)</option>
            <option value="expenses">Expenses (Beban)</option>
          </select>

          {/* Filter Status */}
          <select
            value={statusFilter}
            onChange={(e) => {
              setStatusFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500"
          >
            <option value="">Semua Status</option>
            <option value="true">Aktif</option>
            <option value="false">Nonaktif</option>
          </select>
        </div>
      </div>

      {/* Data Table */}
      <DataTable
        columns={columns}
        isLoading={isLoading}
        isEmpty={accounts.length === 0}
        emptyMessage="Tidak ada akun akuntansi yang sesuai kriteria pencarian."
      >
        {accounts.map((acc) => (
          <tr key={acc.id} className="border-b border-slate-800/60 hover:bg-slate-800/30 transition-colors">
            <td className="px-4 py-3 font-mono font-bold text-emerald-400">
              {acc.account_code}
            </td>
            <td className="px-4 py-3">
              <span className="font-semibold text-slate-100">{acc.account_name}</span>
              {acc.description && (
                <p className="text-xs text-slate-500 line-clamp-1">{acc.description}</p>
              )}
            </td>
            <td className="px-4 py-3 text-center">
              <span className={`inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ${getTypeBadge(acc.account_type)}`}>
                {acc.account_type_label}
              </span>
            </td>
            <td className="px-4 py-3 text-center">
              <span className="font-mono text-xs text-slate-300 uppercase px-2 py-0.5 bg-slate-950 rounded-lg border border-slate-800">
                {acc.normal_balance}
              </span>
            </td>
            <td className="px-4 py-3 text-center">
              <StatusBadge isActive={acc.is_active} />
            </td>
            {isAdmin && (
              <td className="px-4 py-3 text-right whitespace-nowrap">
                <div className="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedAccount(acc);
                      setIsModalOpen(true);
                    }}
                    className="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors cursor-pointer"
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    onClick={() => handleToggleStatus(acc)}
                    className={`px-2.5 py-1 rounded-lg text-xs font-medium border transition-colors cursor-pointer ${
                      acc.is_active
                        ? 'border-amber-500/30 text-amber-400 hover:bg-amber-500/10'
                        : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10'
                    }`}
                  >
                    {acc.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>

                  {!acc.has_journal_entries && (
                    <button
                      type="button"
                      onClick={() => setDeleteTarget(acc)}
                      className="px-2.5 py-1 rounded-lg text-xs font-medium bg-rose-500/10 border border-rose-500/30 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer"
                    >
                      Hapus
                    </button>
                  )}
                </div>
              </td>
            )}
          </tr>
        ))}
      </DataTable>

      {/* Pagination */}
      {pagination && (
        <Pagination
          currentPage={pagination.current_page}
          lastPage={pagination.last_page}
          total={pagination.total}
          onPageChange={(page) => setCurrentPage(page)}
        />
      )}

      {/* Modal Form */}
      <AccountModal
        isOpen={isModalOpen}
        onClose={() => {
          setIsModalOpen(false);
          setSelectedAccount(null);
        }}
        onSubmit={handleFormSubmit}
        account={selectedAccount}
        isSubmitting={isSubmitting}
      />

      {/* Delete Confirmation */}
      <ConfirmDialog
        isOpen={Boolean(deleteTarget)}
        title="Hapus Akun Akuntansi"
        message={`Apakah Anda yakin ingin menghapus akun "${deleteTarget?.account_code} - ${deleteTarget?.account_name}"? Akun hanya dapat dihapus jika belum pernah digunakan dalam jurnal.`}
        confirmText="Ya, Hapus Akun"
        onConfirm={handleConfirmDelete}
        onCancel={() => setDeleteTarget(null)}
        isLoading={isDeleting}
      />
    </div>
  );
}

export default ChartOfAccountsPage;
