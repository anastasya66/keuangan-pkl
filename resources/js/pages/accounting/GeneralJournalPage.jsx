import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import useAuth from '../../hooks/useAuth';
import accountingService from '../../services/accountingService';
import { formatDateTime, formatRupiah } from '../../utils/currency';
import JournalModal from './JournalModal';

export function GeneralJournalPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const basePath = isAdmin ? '/admin/accounting' : '/staff/accounting';

  const [journals, setJournals] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Notifications
  const [toast, setToast] = useState(null);

  // Modal Create/Edit Draft
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedJournal, setSelectedJournal] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Post / Reverse Dialogs
  const [postTarget, setPostTarget] = useState(null);
  const [isPosting, setIsPosting] = useState(false);
  const [reverseTarget, setReverseTarget] = useState(null);
  const [reverseReason, setReverseReason] = useState('');
  const [isReversing, setIsReversing] = useState(false);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchJournals = useCallback(async (page = 1, searchQuery = search, status = statusFilter, start = startDate, end = endDate) => {
    setIsLoading(true);
    try {
      const params = { page, search: searchQuery };
      if (status && status !== 'all') params.status = status;
      if (start) params.start_date = start;
      if (end) params.end_date = end;

      const response = await accountingService.getJournals(params);
      if (response.status === 'success') {
        setJournals(response.data);
        setPagination(response.meta);
      }
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memuat daftar jurnal', 'error');
    } finally {
      setIsLoading(false);
    }
  }, [search, statusFilter, startDate, endDate]);

  useEffect(() => {
    fetchJournals(currentPage, search, statusFilter, startDate, endDate);
  }, [currentPage, statusFilter, startDate, endDate, fetchJournals]);

  const handleFormSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedJournal) {
        await accountingService.updateJournal(selectedJournal.id, formData);
        showToast('Jurnal draft berhasil diperbarui!');
      } else {
        await accountingService.createJournal(formData);
        showToast('Jurnal baru berhasil disimpan sebagai draft!');
      }
      setIsModalOpen(false);
      setSelectedJournal(null);
      fetchJournals(currentPage);
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Gagal menyimpan jurnal';
      const errors = error.response?.data?.errors;
      showToast(message, 'error');
      return { success: false, errors };
    } finally {
      setIsSubmitting(false);
    }
  };

  const handlePostJournal = async () => {
    if (!postTarget) return;
    setIsPosting(true);
    try {
      await accountingService.postJournal(postTarget.id);
      showToast(`Jurnal ${postTarget.journal_number} berhasil diposting ke buku besar!`);
      setPostTarget(null);
      fetchJournals(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memposting jurnal', 'error');
    } finally {
      setIsPosting(false);
    }
  };

  const handleReverseJournal = async () => {
    if (!reverseTarget) return;
    setIsReversing(true);
    try {
      await accountingService.reverseJournal(reverseTarget.id, reverseReason);
      showToast(`Jurnal ${reverseTarget.journal_number} berhasil dibatalkan melalui reversal!`);
      setReverseTarget(null);
      setReverseReason('');
      fetchJournals(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal mereverse jurnal', 'error');
    } finally {
      setIsReversing(false);
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'posted':
        return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
      case 'reversed':
        return 'bg-rose-500/10 text-rose-400 border-rose-500/30';
      case 'draft':
      default:
        return 'bg-amber-500/10 text-amber-400 border-amber-500/30';
    }
  };

  const columns = [
    { header: 'No. Jurnal', className: 'w-44 font-mono font-bold' },
    { header: 'Tanggal', className: 'w-28 text-center' },
    { header: 'Keterangan', className: 'w-1/3' },
    { header: 'Total Nilai', className: 'text-right font-mono' },
    { header: 'Status', className: 'text-center' },
    { header: 'Aksi', className: 'text-right' },
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
            <span>Jurnal Umum (General Journal)</span>
          </h1>
          <p className="text-sm text-slate-400 mt-0.5">
            Daftar seluruh entri jurnal akuntansi berpasangan (double-entry bookkeeping).
          </p>
        </div>

        <button
          type="button"
          onClick={() => {
            setSelectedJournal(null);
            setIsModalOpen(true);
          }}
          className="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-all cursor-pointer shadow-lg shadow-emerald-500/20 flex items-center gap-2"
        >
          <span>+ Buat Jurnal Baru</span>
        </button>
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col lg:flex-row gap-3 items-center justify-between">
        <div className="w-full lg:w-72">
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
            placeholder="Cari nomor jurnal, referensi..."
          />
        </div>

        <div className="flex flex-wrap items-center gap-2 w-full lg:w-auto">
          {/* Status Filter */}
          <select
            value={statusFilter}
            onChange={(e) => {
              setStatusFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500"
          >
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="posted">Posted</option>
            <option value="reversed">Reversed</option>
          </select>

          {/* Date Range */}
          <input
            type="date"
            value={startDate}
            onChange={(e) => {
              setStartDate(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500"
            title="Dari Tanggal"
          />
          <span className="text-slate-600 text-xs">s/d</span>
          <input
            type="date"
            value={endDate}
            onChange={(e) => {
              setEndDate(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500"
            title="Sampai Tanggal"
          />

          {(startDate || endDate) && (
            <button
              type="button"
              onClick={() => {
                setStartDate('');
                setEndDate('');
                setCurrentPage(1);
              }}
              className="px-2.5 py-1.5 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800 cursor-pointer"
            >
              Reset Tanggal
            </button>
          )}
        </div>
      </div>

      {/* Data Table */}
      <DataTable
        columns={columns}
        isLoading={isLoading}
        isEmpty={journals.length === 0}
        emptyMessage="Tidak ada catatan jurnal umum yang ditemukan."
      >
        {journals.map((jrn) => (
          <tr key={jrn.id} className="border-b border-slate-800/60 hover:bg-slate-800/30 transition-colors">
            <td className="px-4 py-3">
              <Link
                to={`${basePath}/journals/${jrn.id}`}
                className="font-mono font-bold text-emerald-400 hover:underline"
              >
                {jrn.journal_number}
              </Link>
              {jrn.reference_number && (
                <p className="text-[11px] text-slate-500 font-mono">Ref: {jrn.reference_number}</p>
              )}
            </td>
            <td className="px-4 py-3 text-center text-xs text-slate-300">
              {jrn.journal_date}
            </td>
            <td className="px-4 py-3">
              <span className="text-xs text-slate-200 font-medium line-clamp-1">{jrn.description}</span>
              <div className="flex items-center gap-2 mt-0.5">
                <span className="text-[10px] text-slate-500 uppercase">Sumber: {jrn.source_type || 'manual'}</span>
                <span className="text-slate-700">•</span>
                <span className="text-[10px] text-slate-500">{jrn.details_count || 2} baris</span>
              </div>
            </td>
            <td className="px-4 py-3 text-right font-mono font-semibold text-slate-100">
              {formatRupiah(jrn.total_debit)}
            </td>
            <td className="px-4 py-3 text-center">
              <span className={`inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ${getStatusBadge(jrn.status)}`}>
                {jrn.status_label}
              </span>
            </td>
            <td className="px-4 py-3 text-right whitespace-nowrap">
              <div className="flex items-center justify-end gap-1.5">
                <Link
                  to={`${basePath}/journals/${jrn.id}`}
                  className="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors cursor-pointer"
                >
                  Detail
                </Link>

                {/* Tombol aksi khusus status draft */}
                {jrn.status === 'draft' && (
                  <>
                    <button
                      type="button"
                      onClick={() => {
                        setSelectedJournal(jrn);
                        setIsModalOpen(true);
                      }}
                      className="px-2.5 py-1 rounded-lg text-xs font-medium border border-slate-700 text-slate-300 hover:bg-slate-800 transition-colors cursor-pointer"
                    >
                      Edit
                    </button>

                    {isAdmin && (
                      <button
                        type="button"
                        onClick={() => setPostTarget(jrn)}
                        className="px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 transition-colors cursor-pointer"
                      >
                        Posting
                      </button>
                    )}
                  </>
                )}

                {/* Tombol reversal khusus status posted (Admin only) */}
                {jrn.status === 'posted' && isAdmin && (
                  <button
                    type="button"
                    onClick={() => {
                      setReverseTarget(jrn);
                      setReverseReason('');
                    }}
                    className="px-2.5 py-1 rounded-lg text-xs font-medium bg-rose-500/10 border border-rose-500/30 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer"
                  >
                    Reversal
                  </button>
                )}
              </div>
            </td>
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

      {/* Modal Form Draft */}
      <JournalModal
        isOpen={isModalOpen}
        onClose={() => {
          setIsModalOpen(false);
          setSelectedJournal(null);
        }}
        onSubmit={handleFormSubmit}
        journal={selectedJournal}
        isSubmitting={isSubmitting}
      />

      {/* Post Confirmation Dialog */}
      <ConfirmDialog
        isOpen={Boolean(postTarget)}
        title="Posting Jurnal ke Buku Besar"
        message={`Apakah Anda yakin ingin memposting jurnal "${postTarget?.journal_number}"? Setelah diposting, transaksi ini akan permanen masuk ke buku besar dan tidak dapat diedit secara langsung (koreksi hanya dapat dilakukan via reversal).`}
        confirmText="Ya, Posting Jurnal"
        onConfirm={handlePostJournal}
        onCancel={() => setPostTarget(null)}
        isLoading={isPosting}
      />

      {/* Reversal Confirmation Modal */}
      {Boolean(reverseTarget) && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
          <div className="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden p-6 space-y-4">
            <h3 className="text-lg font-bold text-white flex items-center gap-2">
              <span className="text-rose-400">⚠️</span>
              <span>Reversal Jurnal Akuntansi</span>
            </h3>
            <p className="text-xs text-slate-400">
              Sistem akan membuat jurnal pembalik otomatis (debit menjadi kredit dan kredit menjadi debit) untuk membatalkan dampak jurnal <strong className="text-slate-200">{reverseTarget.journal_number}</strong>.
            </p>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">
                Alasan Reversal (Opsional)
              </label>
              <textarea
                value={reverseReason}
                onChange={(e) => setReverseReason(e.target.value)}
                placeholder="Contoh: Koreksi pembatalan transaksi dari pelanggan..."
                rows={3}
                className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-100 focus:outline-none focus:border-rose-500"
              />
            </div>

            <div className="flex items-center justify-end gap-3 pt-2 border-t border-slate-800">
              <button
                type="button"
                onClick={() => setReverseTarget(null)}
                disabled={isReversing}
                className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 cursor-pointer"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handleReverseJournal}
                disabled={isReversing}
                className="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-500 hover:bg-rose-400 text-white cursor-pointer shadow-lg shadow-rose-500/20"
              >
                {isReversing ? 'Memproses...' : 'Eksekusi Reversal'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default GeneralJournalPage;
