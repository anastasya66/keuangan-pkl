import React, { useState, useEffect } from 'react';
import posService from '../../services/posService';
import { formatRupiah } from '../../utils/currency';

export function BankTransferModal({ grandTotal, onClose, onConfirm, isSubmitting }) {
  const [banks, setBanks] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedBank, setSelectedBank] = useState(null);
  const [referenceNumber, setReferenceNumber] = useState('');
  const [copiedBankId, setCopiedBankId] = useState(null);

  useEffect(() => {
    async function loadBanks() {
      setIsLoading(true);
      try {
        const res = await posService.getBankAccounts();
        if (res.success && res.data) {
          setBanks(res.data);
          if (res.data.length > 0) {
            setSelectedBank(res.data[0]);
          }
        }
      } catch (err) {
        console.error('Gagal memuat rekening bank:', err);
      } finally {
        setIsLoading(false);
      }
    }
    loadBanks();
  }, []);

  const handleCopyAccount = (bank) => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(bank.account_number);
      setCopiedBankId(bank.id);
      setTimeout(() => setCopiedBankId(null), 2000);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (isSubmitting) return;

    onConfirm({
      payment_method: 'bank_transfer',
      paid_amount: grandTotal,
      notes: referenceNumber ? `Transfer ${selectedBank?.bank || 'Bank'} Ref: ${referenceNumber}` : `Transfer ${selectedBank?.bank || 'Bank'}`,
    });
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/55 backdrop-blur-xs">
      <div className="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full overflow-hidden animate-in fade-in duration-200">
        {/* Header Modal */}
        <div className="p-4 bg-gradient-to-r from-slate-800 to-slate-900 text-white flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-xl">🏦</span>
            <div>
              <h3 className="font-bold text-sm">Transfer Bank Manual</h3>
              <p className="text-[11px] text-slate-300">Rekening Resmi Toko FinToko</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-white/80 hover:text-white p-1 rounded-lg text-lg cursor-pointer"
          >
            ✕
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-5 space-y-4">
          {/* Total Tagihan */}
          <div className="p-3.5 bg-gray-50 border border-gray-200 rounded-xl text-center">
            <span className="text-[11px] uppercase tracking-wider font-semibold text-gray-500">
              Total Pembayaran Transfer
            </span>
            <div className="text-2xl font-black font-mono text-gray-900 mt-0.5">
              {formatRupiah(grandTotal)}
            </div>
          </div>

          {/* Pilihan Rekening Bank */}
          <div>
            <label className="block text-xs font-bold text-gray-700 mb-2">
              Pilih Rekening Tujuan:
            </label>
            {isLoading ? (
              <div className="py-4 text-center text-xs text-gray-400">Memuat rekening bank...</div>
            ) : (
              <div className="space-y-2">
                {banks.map((b) => {
                  const isSelected = selectedBank?.id === b.id;
                  const isCopied = copiedBankId === b.id;

                  return (
                    <div
                      key={b.id}
                      onClick={() => setSelectedBank(b)}
                      className={`p-3 rounded-xl border transition-all cursor-pointer flex items-center justify-between ${
                        isSelected
                          ? 'border-[#55BFD8] bg-[#EAF8FA] shadow-xs'
                          : 'border-gray-200 hover:border-gray-300 bg-white'
                      }`}
                    >
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="px-2 py-0.5 bg-gray-800 text-white text-[10px] font-bold rounded">
                            {b.bank}
                          </span>
                          <span className="text-xs font-bold text-gray-800 font-mono">
                            {b.account_number}
                          </span>
                        </div>
                        <div className="text-[11px] text-gray-500 mt-0.5">a.n. {b.account_name}</div>
                      </div>

                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          handleCopyAccount(b);
                        }}
                        className={`px-2.5 py-1 text-[10px] font-bold rounded-lg border transition ${
                          isCopied
                            ? 'bg-emerald-500 text-white border-emerald-500'
                            : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300'
                        }`}
                      >
                        {isCopied ? '✓ Tersalin' : 'Salin'}
                      </button>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          {/* Nomor Referensi / Catatan */}
          <div>
            <label className="block text-xs font-bold text-gray-700 mb-1">
              Nomor Referensi Transfer / 4 Digit Terakhir (Opsional)
            </label>
            <input
              type="text"
              value={referenceNumber}
              onChange={(e) => setReferenceNumber(e.target.value)}
              placeholder="Contoh: REF-881920 atau nama pengirim"
              className="w-full px-3 py-2 bg-white border border-gray-300 focus:border-[#55BFD8] rounded-xl text-xs text-gray-900 focus:outline-none transition"
            />
          </div>

          {/* Action Footer */}
          <div className="pt-2 flex items-center justify-end gap-2">
            <button
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
              className="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 rounded-xl hover:bg-gray-100 transition cursor-pointer"
            >
              Batal
            </button>

            <button
              type="submit"
              disabled={isSubmitting}
              className="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl shadow-md transition disabled:opacity-40 flex items-center gap-1.5 cursor-pointer active:scale-95"
            >
              {isSubmitting ? (
                <>
                  <span className="animate-spin text-sm">⏳</span>
                  <span>Menyimpan...</span>
                </>
              ) : (
                <>
                  <span>✓</span>
                  <span>Konfirmasi Transfer & Selesai</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default BankTransferModal;
