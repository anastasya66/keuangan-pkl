import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import useAuth from '../../hooks/useAuth';
import accountingService from '../../services/accountingService';
import { formatRupiah } from '../../utils/currency';
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
        return 'bg-emerald-600 text-white';
      case 'reversed':
        return 'bg-rose-600 text-white';
      case 'draft':
      default:
        return 'bg-amber-600 text-white';
    }
  };

  if (isLoading) {
    return (
      <div className="py-20 text-center text-[#5A7C85]">
        <div className="inline-block w-8 h-8 border-2 border-[#27C5E5] border-t-transparent rounded-full animate-spin mb-3"></div>
        <p className="text-sm">Memuat detail jurnal akuntansi...</p>
      </div>
    );
  }

  if (!journal) {
    return (
      <div className="py-20 text-center space-y-4">
        <p className="text-rose-600 text-base font-semibold">Jurnal akuntansi tidak ditemukan.</p>
        <Link
          to={`${basePath}/journals`}
          className="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-[#D7EBEF] text-[#16323B] hover:bg-[#F3FBFC]"
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
          className={`p-4 rounded-xl border flex items-center justify-between text-sm shadow-md transition-all ${
            toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-800'
              : 'bg-emerald-50 border-emerald-200 text-emerald-800'
          }`}
        >
          <div className="flex items-center gap-2">
            <span>{toast.type === 'error' ? '⚠️' : '✅'}</span>
            <span className="font-medium">{toast.message}</span>
          </div>
          <button
            type="button"
            onClick={() => setToast(null)}
            className="text-xs text-[#5A7C85] hover:text-[#16323B] cursor-pointer"
          >
            ✕
          </button>
        </div>
      )}

      {/* Top Navigation */}
      <div className="flex items-center justify-between">
        <Link
          to={`${basePath}/journals`}
          className="text-xs font-semibold text-[#5A7C85] hover:text-[#16323B] flex items-center gap-1.5 transition-colors"
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
                className="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-[#F3FBFC] text-[#16323B] border border-[#D7EBEF] transition-all cursor-pointer shadow-xs"
              >
                Edit Draft
              </button>

              {isAdmin && (
                <button
                  type="button"
                  onClick={() => setIsConfirmPostOpen(true)}
                  className="px-4 py-2 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#38BFE0] text-white transition-all cursor-pointer shadow-md shadow-[#27C5E5]/20"
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
              className="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 transition-all cursor-pointer shadow-xs"
            >
              Reversal Jurnal
            </button>
          )}
        </div>
      </div>

      {/* Header Card */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 space-y-6 shadow-xs">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#D7EBEF] pb-5">
          <div>
            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-extrabold tracking-tight text-[#16323B] font-mono">
                {journal.journal_number}
              </h1>
              <span className={`px-3 py-0.5 rounded-full text-xs font-bold shadow-xs whitespace-nowrap ${getStatusBadge(journal.status)}`}>
                {journal.status_label}
              </span>
            </div>
            <p className="text-sm text-[#5A7C85] mt-1">{journal.description}</p>
          </div>

          <div className="flex flex-col sm:text-right">
            <span className="text-xs text-[#5A7C85]">Tanggal Jurnal</span>
            <span className="text-base font-bold text-[#16323B] font-mono">
              {journal.journal_date}
            </span>
          </div>
        </div>

        {/* Metadata Grid */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div className="p-3.5 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl">
            <span className="text-[#5A7C85] block mb-0.5">No. Referensi</span>
            <span className="font-semibold text-[#16323B] font-mono">
              {journal.reference_number || '-'}
            </span>
          </div>

          <div className="p-3.5 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl">
            <span className="text-[#5A7C85] block mb-0.5">Tipe Sumber</span>
            <span className="font-semibold text-[#16323B] uppercase">
              {journal.source_type || 'Manual'}
            </span>
          </div>

          <div className="p-3.5 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl">
            <span className="text-[#5A7C85] block mb-0.5">Dibuat Oleh</span>
            <span className="font-semibold text-[#16323B]">
              {journal.created_by?.name || 'Sistem'}
            </span>
            <span className="text-[10px] text-[#5A7C85] block mt-0.5">{journal.created_at}</span>
          </div>

          <div className="p-3.5 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl">
            <span className="text-[#5A7C85] block mb-0.5">Status Posting</span>
            {journal.posted_by ? (
              <>
                <span className="font-semibold text-[#36B878]">
                  {journal.posted_by.name}
                </span>
                <span className="text-[10px] text-[#5A7C85] block mt-0.5">{journal.posted_at}</span>
              </>
            ) : (
              <span className="text-amber-700 font-medium">Belum diposting</span>
            )}
          </div>
        </div>

        {/* If reversed info */}
        {journal.status === 'reversed' && (
          <div className="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
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
                    className="underline font-mono font-bold text-rose-700"
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
      <div className="bg-white border border-[#D7EBEF] rounded-2xl overflow-hidden shadow-xs">
        <div className="px-6 py-4 border-b border-[#D7EBEF] bg-[#F3FBFC] flex items-center justify-between">
          <h2 className="text-sm font-bold text-[#16323B]">Baris Transaksi Akuntansi</h2>
          <span className="text-xs text-[#5A7C85] font-medium">{journal.details?.length || 0} baris</span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-[#F3FBFC] text-[#5A7C85] font-semibold border-b border-[#D7EBEF]">
              <tr>
                <th className="px-5 py-3 w-32 font-mono">Kode Akun</th>
                <th className="px-5 py-3">Nama Akun & Keterangan Baris</th>
                <th className="px-5 py-3 w-40 text-right font-mono">Debit (Rp)</th>
                <th className="px-5 py-3 w-40 text-right font-mono">Kredit (Rp)</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#D7EBEF]/60">
              {journal.details?.map((detail) => (
                <tr key={detail.id} className="hover:bg-[#F3FBFC]/50 transition-colors">
                  <td className="px-5 py-3 font-mono font-bold text-[#27C5E5]">
                    {detail.account?.account_code}
                  </td>
                  <td className="px-5 py-3">
                    <span className="font-semibold text-[#16323B] text-sm">
                      {detail.account?.account_name}
                    </span>
                    <span className="text-[11px] text-[#5A7C85] block uppercase mt-0.5">
                      Kategori: {detail.account?.account_type} (Normal: {detail.account?.normal_balance})
                    </span>
                    {detail.description && (
                      <p className="text-xs text-[#5A7C85] mt-1 italic">{detail.description}</p>
                    )}
                  </td>
                  <td className="px-5 py-3 text-right font-mono text-sm text-[#36B878] font-bold">
                    {detail.debit > 0 ? formatRupiah(detail.debit) : '-'}
                  </td>
                  <td className="px-5 py-3 text-right font-mono text-sm text-[#27C5E5] font-bold">
                    {detail.credit > 0 ? formatRupiah(detail.credit) : '-'}
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-[#F3FBFC] border-t-2 border-[#D7EBEF] font-bold">
              <tr>
                <td colSpan={2} className="px-5 py-4 text-right text-[#16323B] text-sm">
                  Total Transaksi:
                </td>
                <td className="px-5 py-4 text-right font-mono text-[#36B878] text-sm font-extrabold">
                  {formatRupiah(journal.total_debit)}
                </td>
                <td className="px-5 py-4 text-right font-mono text-[#27C5E5] text-sm font-extrabold">
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
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/35 backdrop-blur-sm animate-fade-in">
          <div className="relative w-full max-w-md bg-white border border-[#D7EBEF] rounded-2xl shadow-xl p-6 space-y-4">
            <h3 className="text-lg font-bold text-[#16323B] flex items-center gap-2">
              <span className="text-rose-500">⚠️</span>
              <span>Reversal Jurnal</span>
            </h3>
            <p className="text-xs text-[#5A7C85]">
              Sistem akan membuat jurnal pembalik baru untuk membatalkan dampak akuntansi dari jurnal ini.
            </p>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Alasan Pembatalan / Reversal
              </label>
              <textarea
                value={reverseReason}
                onChange={(e) => setReverseReason(e.target.value)}
                placeholder="Tuliskan alasan pembatalan..."
                rows={3}
                className="w-full px-3 py-2 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] placeholder-[#5A7C85]/60 focus:bg-white focus:outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition-all"
              />
            </div>

            <div className="flex items-center justify-end gap-3 pt-2 border-t border-[#D7EBEF]">
              <button
                type="button"
                onClick={() => setIsReverseModalOpen(false)}
                disabled={isReversing}
                className="px-4 py-2 rounded-xl text-xs font-semibold text-[#5A7C85] hover:text-[#16323B] bg-white border border-[#D7EBEF] cursor-pointer transition-colors"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handleReverseJournal}
                disabled={isReversing}
                className="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-500 hover:bg-rose-600 text-white cursor-pointer shadow-md shadow-rose-500/20 transition-colors"
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
