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
  }, [currentPage, search, typeFilter, statusFilter, fetchAccounts]);

  const handleFormSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedAccount) {
        await accountingService.updateAccount(selectedAccount.id, formData);
        showToast('Akun berhasil diperbarui.');
      } else {
        await accountingService.createAccount(formData);
        showToast('Akun baru berhasil ditambahkan.');
      }
      setIsModalOpen(false);
      setSelectedAccount(null);
      fetchAccounts(currentPage);
    } catch (error) {
      const msg = error.response?.data?.message || 'Gagal menyimpan akun.';
      showToast(msg, 'error');
      throw error;
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleToggleStatus = async (account) => {
    try {
      const newStatus = !account.is_active;
      await accountingService.updateAccountStatus(account.id, newStatus);
      showToast(`Status akun ${account.account_code} berhasil diubah.`);
      fetchAccounts(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal mengubah status akun.', 'error');
    }
  };

  const handleConfirmDelete = async () => {
    if (!deleteTarget) return;
    setIsDeleting(true);
    try {
      await accountingService.deleteAccount(deleteTarget.id);
      showToast(`Akun ${deleteTarget.account_code} berhasil dihapus.`);
      setDeleteTarget(null);
      fetchAccounts(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal menghapus akun.', 'error');
    } finally {
      setIsDeleting(false);
    }
  };

  const getTypeBadge = (type) => {
    const badges = {
      assets: 'bg-indigo-50 text-indigo-700 border-indigo-200',
      liabilities: 'bg-amber-50 text-amber-700 border-amber-200',
      equity: 'bg-purple-50 text-purple-700 border-purple-200',
      revenue: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      expenses: 'bg-rose-50 text-rose-700 border-rose-200',
    };
    return badges[type] || 'bg-slate-100 text-slate-600 border-slate-200';
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
          className={`p-4 rounded-2xl border flex items-center justify-between text-xs font-semibold shadow-md transition-all ${
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
            className="text-xs text-[#8EA6AF] hover:text-[#16323B] cursor-pointer"
          >
            ✕
          </button>
        </div>
      )}

      {/* Header Page */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-black tracking-tight text-[#10262E] flex items-center gap-2">
            <span>📑</span> Daftar Akun (Chart of Accounts)
          </h1>
          <p className="text-xs text-[#577782] mt-1">
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
            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-[#27C5E5] to-[#38BFE0] hover:from-[#1FB2D0] hover:to-[#2FB0CF] text-white font-bold text-xs shadow-md shadow-[#38BFE0]/25 transition-all cursor-pointer self-start sm:self-auto"
          >
            <span>+</span> Tambah Akun Baru
          </button>
        )}
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-white p-4 rounded-3xl border border-[#D7EBEF] shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between">
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
          <select
            value={typeFilter}
            onChange={(e) => {
              setTypeFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-[#F8FDFF] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25"
          >
            <option value="">Semua Kategori</option>
            <option value="assets">Assets (Aset)</option>
            <option value="liabilities">Liabilities (Kewajiban)</option>
            <option value="equity">Equity (Modal)</option>
            <option value="revenue">Revenue (Pendapatan)</option>
            <option value="expenses">Expenses (Beban)</option>
          </select>

          <select
            value={statusFilter}
            onChange={(e) => {
              setStatusFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-[#F8FDFF] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25"
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
        data={accounts}
        isLoading={isLoading}
        emptyMessage="Tidak ada akun akuntansi yang sesuai kriteria pencarian."
        renderRow={(acc) => (
          <tr key={acc.id} className="hover:bg-[#F3FBFC] transition-colors">
            <td className="px-4 py-3.5 font-mono font-bold text-[#167A93]">
              {acc.account_code}
            </td>
            <td className="px-4 py-3.5">
              <span className="font-bold text-[#10262E]">{acc.account_name}</span>
              {acc.description && (
                <p className="text-xs text-[#577782] line-clamp-1 mt-0.5">{acc.description}</p>
              )}
            </td>
            <td className="px-4 py-3.5 text-center whitespace-nowrap">
              <span className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border whitespace-nowrap ${getTypeBadge(acc.account_type)}`}>
                {acc.account_type_label}
              </span>
            </td>
            <td className="px-4 py-3.5 text-center whitespace-nowrap">
              <span className="font-mono text-xs text-[#167A93] font-bold uppercase px-2 py-0.5 bg-[#EAF8FA] rounded-md border border-[#38BFE0]/30 whitespace-nowrap">
                {acc.normal_balance}
              </span>
            </td>
            <td className="px-4 py-3.5 text-center whitespace-nowrap">
              <StatusBadge isActive={acc.is_active} />
            </td>
            {isAdmin && (
              <td className="px-4 py-3.5 text-right whitespace-nowrap">
                <div className="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedAccount(acc);
                      setIsModalOpen(true);
                    }}
                    className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white hover:bg-[#F3FBFC] text-[#16323B] border border-[#D7EBEF] transition-colors cursor-pointer shadow-2xs"
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    onClick={() => handleToggleStatus(acc)}
                    className={`px-2.5 py-1 rounded-lg text-xs font-semibold border transition-colors cursor-pointer ${
                      acc.is_active
                        ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                        : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                    }`}
                  >
                    {acc.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>

                  {!acc.has_journal_entries && (
                    <button
                      type="button"
                      onClick={() => setDeleteTarget(acc)}
                      className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-600 hover:bg-rose-100 transition-colors cursor-pointer"
                    >
                      Hapus
                    </button>
                  )}
                </div>
              </td>
            )}
          </tr>
        )}
      />

      {/* Pagination */}
      {pagination && (
        <Pagination
          pagination={pagination}
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
