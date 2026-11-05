import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import useAuth from '../../hooks/useAuth';
import accountingService from '../../services/accountingService';
import { formatDateTime, formatRupiah } from '../../utils/currency';
import JournalModal from './JournalModal';

export function JournalDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const basePath = isAdmin ? '/admin/accounting' : '/staff/accounting';

  const [journal, setJournal] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [toast, setToast] = useState(null);

  // Edit Modal State
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Actions
  const [isPosting, setIsPosting] = useState(false);
  const [isConfirmPostOpen, setIsConfirmPostOpen] = useState(false);

  const [isReverseModalOpen, setIsReverseModalOpen] = useState(false);
  const [reverseReason, setReverseReason] = useState('');
  const [isReversing, setIsReversing] = useState(false);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchJournalDetail = async () => {
    setIsLoading(true);
    try {
      const response = await accountingService.getJournal(id);
      if (response.status === 'success') {
        setJournal(response.data);
      }
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memuat detail jurnal', 'error');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchJournalDetail();
  }, [id]);

  const handlePostJournal = async () => {
    setIsPosting(true);
    try {
      await accountingService.postJournal(journal.id);
      showToast(`Jurnal ${journal.journal_number} berhasil diposting!`);
      setIsConfirmPostOpen(false);
      fetchJournalDetail();
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memposting jurnal', 'error');
    } finally {
      setIsPosting(false);
    }
  };

  const handleReverseJournal = async () => {
    setIsReversing(true);
    try {
      await accountingService.reverseJournal(journal.id, reverseReason);
      showToast(`Jurnal ${journal.journal_number} berhasil dibatalkan melalui reversal!`);
      setIsReverseModalOpen(false);
      fetchJournalDetail();
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal mereverse jurnal', 'error');
    } finally {
      setIsReversing(false);
    }
  };

  const handleEditSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      await accountingService.updateJournal(journal.id, formData);
      showToast('Jurnal draft berhasil diperbarui!');
      setIsEditModalOpen(false);
      fetchJournalDetail();
      return { success: true };
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memperbarui jurnal', 'error');
      return { success: false, errors: error.response?.data?.errors };
    } finally {
      setIsSubmitting(false);
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

  if (isLoading) {
    return (
      <div className="py-20 text-center text-slate-400">
        <div className="inline-block w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mb-3"></div>
        <p className="text-sm">Memuat detail jurnal akuntansi...</p>
      </div>
    );
  }

  if (!journal) {
    return (
      <div className="py-20 text-center space-y-4">
        <p className="text-rose-400 text-base">Jurnal akuntansi tidak ditemukan.</p>
        <Link
          to={`${basePath}/journals`}
          className="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-slate-300 hover:text-white"
        >
          Kembali ke Daftar Jurnal
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
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

      {/* Top Navigation */}
      <div className="flex items-center justify-between">
        <Link
          to={`${basePath}/journals`}
          className="text-xs font-semibold text-slate-400 hover:text-white flex items-center gap-1.5 transition-colors"
        >
          <span>← Kembali ke Daftar Jurnal</span>
        </Link>

        {/* Action Buttons */}
        <div className="flex items-center gap-2">
          {journal.status === 'draft' && (
            <>
              <button
                type="button"
                onClick={() => setIsEditModalOpen(true)}
                className="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-all cursor-pointer"
              >
                Edit Draft
              </button>

              {isAdmin && (
                <button
                  type="button"
                  onClick={() => setIsConfirmPostOpen(true)}
                  className="px-4 py-1.5 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-all cursor-pointer shadow-lg shadow-emerald-500/20"
                >
                  Posting Jurnal
                </button>
              )}
            </>
          )}

          {journal.status === 'posted' && isAdmin && (
            <button
              type="button"
              onClick={() => {
                setReverseReason('');
                setIsReverseModalOpen(true);
              }}
              className="px-4 py-1.5 rounded-xl text-xs font-semibold bg-rose-500/10 border border-rose-500/30 text-rose-400 hover:bg-rose-500/20 transition-all cursor-pointer"
            >
              Reversal Jurnal
            </button>
          )}
        </div>
      </div>

      {/* Header Card */}
      <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-5">
          <div>
            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-extrabold tracking-tight text-white font-mono">
                {journal.journal_number}
              </h1>
              <span className={`px-3 py-0.5 rounded-full text-xs font-semibold border ${getStatusBadge(journal.status)}`}>
                {journal.status_label}
              </span>
            </div>
            <p className="text-sm text-slate-300 mt-1">{journal.description}</p>
          </div>

          <div className="flex flex-col sm:text-right">
            <span className="text-xs text-slate-500">Tanggal Jurnal</span>
            <span className="text-base font-semibold text-slate-200 font-mono">
              {journal.journal_date}
            </span>
          </div>
        </div>

        {/* Metadata Grid */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div className="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl">
            <span className="text-slate-500 block mb-0.5">No. Referensi</span>
            <span className="font-semibold text-slate-200 font-mono">
              {journal.reference_number || '-'}
            </span>
          </div>

          <div className="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl">
            <span className="text-slate-500 block mb-0.5">Tipe Sumber</span>
            <span className="font-semibold text-slate-200 uppercase">
              {journal.source_type || 'Manual'}
            </span>
          </div>

          <div className="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl">
            <span className="text-slate-500 block mb-0.5">Dibuat Oleh</span>
            <span className="font-semibold text-slate-200">
              {journal.created_by?.name || 'Sistem'}
            </span>
            <span className="text-[10px] text-slate-500 block">{journal.created_at}</span>
          </div>

          <div className="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl">
            <span className="text-slate-500 block mb-0.5">Status Posting</span>
            {journal.posted_by ? (
              <>
                <span className="font-semibold text-emerald-400">
                  {journal.posted_by.name}
                </span>
                <span className="text-[10px] text-slate-500 block">{journal.posted_at}</span>
              </>
            ) : (
              <span className="text-amber-400 font-medium">Belum diposting</span>
            )}
          </div>
        </div>

        {/* If reversed info */}
        {journal.status === 'reversed' && (
          <div className="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            <div className="font-bold flex items-center gap-1.5">
              <span>⚠️ Jurnal ini telah dibatalkan melalui Reversal</span>
            </div>
            <p>
              Dibalik oleh: <strong>{journal.reversed_by?.name || 'Administrator'}</strong> pada {journal.reversed_at}.
              {journal.reversal_journal && (
                <>
                  {' '}Nomor jurnal pembalik:{' '}
                  <Link
                    to={`${basePath}/journals/${journal.reversal_journal.id}`}
                    className="underline font-mono font-bold text-rose-200"
                  >
                    {journal.reversal_journal.journal_number}
                  </Link>
                </>
              )}
            </p>
          </div>
        )}
      </div>

      {/* Journal Details Table */}
      <div className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div className="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
          <h2 className="text-base font-bold text-white">Baris Transaksi Akuntansi</h2>
          <span className="text-xs text-slate-400">{journal.details?.length || 0} baris</span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-950 text-slate-400 font-semibold border-b border-slate-800">
              <tr>
                <th className="px-5 py-3 w-32 font-mono">Kode Akun</th>
                <th className="px-5 py-3">Nama Akun & Keterangan Baris</th>
                <th className="px-5 py-3 w-40 text-right font-mono">Debit (Rp)</th>
                <th className="px-5 py-3 w-40 text-right font-mono">Kredit (Rp)</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/60">
              {journal.details?.map((detail) => (
                <tr key={detail.id} className="hover:bg-slate-800/20">
                  <td className="px-5 py-3 font-mono font-bold text-emerald-400">
                    {detail.account?.account_code}
                  </td>
                  <td className="px-5 py-3">
                    <span className="font-semibold text-slate-200 text-sm">
                      {detail.account?.account_name}
                    </span>
                    <span className="text-[11px] text-slate-500 block uppercase">
                      Kategori: {detail.account?.account_type} (Normal: {detail.account?.normal_balance})
                    </span>
                    {detail.description && (
                      <p className="text-xs text-slate-400 mt-1 italic">{detail.description}</p>
                    )}
                  </td>
                  <td className="px-5 py-3 text-right font-mono text-sm text-emerald-400 font-semibold">
                    {detail.debit > 0 ? formatRupiah(detail.debit) : '-'}
                  </td>
                  <td className="px-5 py-3 text-right font-mono text-sm text-cyan-400 font-semibold">
                    {detail.credit > 0 ? formatRupiah(detail.credit) : '-'}
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-slate-950 border-t-2 border-slate-800 font-bold">
              <tr>
                <td colSpan={2} className="px-5 py-4 text-right text-slate-300 text-sm">
                  Total Transaksi:
                </td>
                <td className="px-5 py-4 text-right font-mono text-emerald-400 text-sm">
                  {formatRupiah(journal.total_debit)}
                </td>
                <td className="px-5 py-4 text-right font-mono text-cyan-400 text-sm">
                  {formatRupiah(journal.total_credit)}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      {/* Edit Modal */}
      <JournalModal
        isOpen={isEditModalOpen}
        onClose={() => setIsEditModalOpen(false)}
        onSubmit={handleEditSubmit}
        journal={journal}
        isSubmitting={isSubmitting}
      />

      {/* Post Confirm Dialog */}
      <ConfirmDialog
        isOpen={isConfirmPostOpen}
        title="Posting Jurnal ke Buku Besar"
        message={`Apakah Anda yakin ingin memposting jurnal ${journal.journal_number}? Transaksi akan dicatat secara permanen di buku besar.`}
        confirmText="Ya, Posting Jurnal"
        onConfirm={handlePostJournal}
        onCancel={() => setIsConfirmPostOpen(false)}
        isLoading={isPosting}
      />

      {/* Reversal Modal */}
      {isReverseModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
          <div className="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 space-y-4">
            <h3 className="text-lg font-bold text-white flex items-center gap-2">
              <span className="text-rose-400">⚠️</span>
              <span>Reversal Jurnal</span>
            </h3>
            <p className="text-xs text-slate-400">
              Sistem akan membuat jurnal pembalik baru untuk membatalkan dampak akuntansi dari jurnal ini.
            </p>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">
                Alasan Pembatalan / Reversal
              </label>
              <textarea
                value={reverseReason}
                onChange={(e) => setReverseReason(e.target.value)}
                placeholder="Tuliskan alasan pembatalan..."
                rows={3}
                className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-100 focus:outline-none focus:border-rose-500"
              />
            </div>

            <div className="flex items-center justify-end gap-3 pt-2 border-t border-slate-800">
              <button
                type="button"
                onClick={() => setIsReverseModalOpen(false)}
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
                {isReversing ? 'Memproses...' : 'Konfirmasi Reversal'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default JournalDetailPage;

