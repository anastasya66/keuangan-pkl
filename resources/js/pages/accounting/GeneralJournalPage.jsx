import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Plus } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import PageHeader from '../../components/ui/PageHeader';
import StatusBadge from '../../components/ui/StatusBadge';
import useAuth from '../../hooks/useAuth';
import accountingService from '../../services/accountingService';
import { formatRupiah } from '../../utils/currency';
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
  }, [currentPage, search, statusFilter, startDate, endDate, fetchJournals]);

  const handleFormSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedJournal) {
        await accountingService.updateJournal(selectedJournal.id, formData);
        showToast('Draft jurnal berhasil diperbarui.');
      } else {
        await accountingService.createJournal(formData);
        showToast('Draft jurnal berhasil dibuat.');
      }
      setIsModalOpen(false);
      setSelectedJournal(null);
      fetchJournals(currentPage);
    } catch (error) {
      const msg = error.response?.data?.message || 'Gagal menyimpan jurnal.';
      showToast(msg, 'error');
      throw error;
    } finally {
      setIsSubmitting(false);
    }
  };

  const handlePostJournal = async () => {
    if (!postTarget) return;
    setIsPosting(true);
    try {
      await accountingService.postJournal(postTarget.id);
      showToast(`Jurnal ${postTarget.journal_number} berhasil diposting ke buku besar.`);
      setPostTarget(null);
      fetchJournals(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memposting jurnal.', 'error');
    } finally {
      setIsPosting(false);
    }
  };

  const handleReverseJournal = async (e) => {
    e.preventDefault();
    if (!reverseTarget) return;
    setIsReversing(true);
    try {
      await accountingService.reverseJournal(reverseTarget.id, reverseReason);
      showToast(`Jurnal ${reverseTarget.journal_number} berhasil direverse.`);
      setReverseTarget(null);
      setReverseReason('');
      fetchJournals(currentPage);
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal mereverse jurnal.', 'error');
    } finally {
      setIsReversing(false);
    }
  };

  const getStatusBadge = (status) => {
    const badges = {
      draft: 'bg-amber-600 text-white',
      posted: 'bg-emerald-600 text-white',
      reversed: 'bg-rose-600 text-white',
    };
    return badges[status] || 'bg-slate-700 text-white';
  };

  const columns = [
    { header: 'No. Jurnal', className: 'w-36 font-mono font-bold' },
    { header: 'Tanggal', className: 'w-24 text-center' },
    { header: 'Keterangan Transaksi', className: 'w-2/5' },
    { header: 'Total Nilai (Debit)', className: 'text-right font-mono' },
    { header: 'Status', className: 'text-center' },
    { header: 'Aksi', className: 'text-right' },
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
      <PageHeader
        title="Jurnal Umum (General Journal)"
        subtitle="Daftar seluruh entri jurnal akuntansi berpasangan (double-entry bookkeeping)."
      >
        <button
          type="button"
          onClick={() => {
            setSelectedJournal(null);
            setIsModalOpen(true);
          }}
          className="flex items-center gap-2 px-4 py-2 rounded-full bg-[#20282D] hover:bg-black text-white font-bold text-xs shadow-xs transition-all cursor-pointer"
        >
          <Plus className="w-4 h-4 stroke-[2.5]" />
          <span>Buat Jurnal Baru</span>
        </button>
      </PageHeader>

      {/* Filter and Search Bar */}
      <div className="bg-white p-4 rounded-3xl border border-[#D7EBEF] shadow-xs flex flex-col lg:flex-row gap-3 items-center justify-between">
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
            className="px-3 py-2 bg-[#F8FDFF] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25"
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
            className="px-3 py-2 bg-[#F8FDFF] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25"
            title="Dari Tanggal"
          />
          <span className="text-[#8EA6AF] text-xs font-bold">s/d</span>
          <input
            type="date"
            value={endDate}
            onChange={(e) => {
              setEndDate(e.target.value);
              setCurrentPage(1);
            }}
            className="px-3 py-2 bg-[#F8FDFF] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25"
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
              className="px-2.5 py-1.5 rounded-xl text-xs text-[#577782] hover:bg-[#EAF8FA] bg-[#F3FBFC] border border-[#D7EBEF] cursor-pointer font-semibold"
            >
              Reset
            </button>
          )}
        </div>
      </div>

      {/* Data Table */}
      <DataTable
        columns={columns}
        data={journals}
        isLoading={isLoading}
        emptyMessage="Tidak ada catatan jurnal umum yang ditemukan."
        renderRow={(jrn) => (
          <tr key={jrn.id} className="hover:bg-[#F3FBFC] transition-colors">
            <td className="px-4 py-3.5">
              <Link
                to={`${basePath}/journals/${jrn.id}`}
                className="font-mono font-bold text-[#167A93] hover:underline"
              >
                {jrn.journal_number}
              </Link>
              {jrn.reference_number && (
                <p className="text-[11px] text-[#8EA6AF] font-mono mt-0.5">Ref: {jrn.reference_number}</p>
              )}
            </td>
            <td className="px-4 py-3.5 text-center text-xs text-[#577782] whitespace-nowrap">
              {jrn.journal_date}
            </td>
            <td className="px-4 py-3.5">
              <span className="text-xs text-[#10262E] font-medium line-clamp-1">{jrn.description}</span>
              <div className="flex items-center gap-2 mt-0.5">
                <span className="text-[10px] text-[#8EA6AF] uppercase font-bold">Sumber: {jrn.source_type || 'manual'}</span>
                <span className="text-[#D7EBEF]">&bull;</span>
                <span className="text-[10px] text-[#8EA6AF]">{jrn.details_count || 2} baris</span>
              </div>
            </td>
            <td className="px-4 py-3.5 text-right font-mono font-bold text-[#10262E] whitespace-nowrap">
              {formatRupiah(jrn.total_debit)}
            </td>
            <td className="px-4 py-3.5 text-center whitespace-nowrap">
              <span className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-xs whitespace-nowrap ${getStatusBadge(jrn.status)}`}>
                {jrn.status_label}
              </span>
            </td>
            <td className="px-4 py-3.5 text-right whitespace-nowrap">
              <div className="flex items-center justify-end gap-1.5">
                <Link
                  to={`${basePath}/journals/${jrn.id}`}
                  className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white hover:bg-[#F3FBFC] text-[#16323B] border border-[#D7EBEF] transition-colors cursor-pointer shadow-2xs"
                >
                  Detail
                </Link>

                {jrn.status === 'draft' && (
                  <>
                    <button
                      type="button"
                      onClick={() => {
                        setSelectedJournal(jrn);
                        setIsModalOpen(true);
                      }}
                      className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white hover:bg-[#F3FBFC] text-[#16323B] border border-[#D7EBEF] transition-colors cursor-pointer shadow-2xs"
                    >
                      Edit
                    </button>

                    {isAdmin && (
                      <button
                        type="button"
                        onClick={() => setPostTarget(jrn)}
                        className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer"
                      >
                        Posting
                      </button>
                    )}
                  </>
                )}

                {jrn.status === 'posted' && isAdmin && (
                  <button
                    type="button"
                    onClick={() => {
                      setReverseTarget(jrn);
                      setReverseReason('');
                    }}
                    className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 transition-colors cursor-pointer"
                  >
                    Reversal
                  </button>
                )}
              </div>
            </td>
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
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/35 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-md bg-white border border-[#D7EBEF] rounded-3xl shadow-2xl p-6 sm:p-8 space-y-4 animate-in zoom-in-95 duration-200">
            <h3 className="text-lg font-black text-[#10262E] flex items-center gap-2">
              <span className="text-rose-500">⚠️</span>
              <span>Reversal Jurnal Akuntansi</span>
            </h3>
            <p className="text-xs text-[#577782] leading-relaxed">
              Membuat jurnal pembalik otomatis untuk menetralkan mutasi akun pada jurnal{' '}
              <strong className="text-[#10262E]">{reverseTarget?.journal_number}</strong>.
            </p>
            <form onSubmit={handleReverseJournal} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-[#16323B] mb-1.5">
                  Alasan Reversal <span className="text-rose-500">*</span>
                </label>
                <textarea
                  required
                  rows="2"
                  value={reverseReason}
                  onChange={(e) => setReverseReason(e.target.value)}
                  placeholder="Misal: Koreksi salah catat akun transaksi..."
                  className="w-full px-3.5 py-2.5 bg-[#F8FDFF] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/25"
                />
              </div>
              <div className="flex items-center justify-end gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setReverseTarget(null)}
                  disabled={isReversing}
                  className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-bold text-[#577782] cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={isReversing}
                  className="px-4 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition-all cursor-pointer disabled:opacity-50"
                >
                  {isReversing ? 'Memproses...' : 'Proses Reversal'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default GeneralJournalPage;
