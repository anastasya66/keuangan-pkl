import React, { useState, useEffect, useRef } from 'react';
import { formatRupiah } from '../../utils/currency';

export function CashPaymentModal({ grandTotal, onClose, onConfirm, isSubmitting }) {
  const [paidAmount, setPaidAmount] = useState(grandTotal);
  const inputRef = useRef(null);

  useEffect(() => {
    setPaidAmount(grandTotal);
    if (inputRef.current) {
      inputRef.current.focus();
      inputRef.current.select();
    }
  }, [grandTotal]);

  const currentPaid = Number(paidAmount) || 0;
  const changeAmount = Math.max(0, currentPaid - grandTotal);
  const isSufficient = currentPaid >= grandTotal;

  // Tombol Nominal Cepat
  const quickNominals = [
    { label: 'Uang Pas', value: grandTotal },
    { label: '10.000', value: 10000 },
    { label: '20.000', value: 20000 },
    { label: '50.000', value: 50000 },
    { label: '100.000', value: 100000 },
    { label: '200.000', value: 200000 },
  ];

  const handleAddNominal = (val) => {
    setPaidAmount((prev) => (Number(prev) || 0) + val);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!isSufficient || isSubmitting) return;
    onConfirm({
      payment_method: 'cash',
      paid_amount: currentPaid,
      change_amount: changeAmount,
    });
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/55 backdrop-blur-xs">
      <div className="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full overflow-hidden animate-in fade-in duration-200">
        {/* Header Modal */}
        <div className="p-4 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-xl">💵</span>
            <div>
              <h3 className="font-bold text-sm">Pembayaran Tunai (Cash)</h3>
              <p className="text-[11px] text-emerald-100">Hitung uang diterima & kembalian otomatis</p>
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
          {/* Tagihan Total Belanja */}
          <div className="p-3.5 bg-gray-50 border border-gray-200 rounded-xl text-center">
            <span className="text-[11px] uppercase tracking-wider font-semibold text-gray-500">
              Total Tagihan Belanja
            </span>
            <div className="text-2xl font-black font-mono text-[#20282D] mt-0.5">
              {formatRupiah(grandTotal)}
            </div>
          </div>

          {/* Input Uang Diterima */}
          <div>
            <label className="block text-xs font-bold text-gray-700 mb-1.5">
              Nominal Uang Diterima (Rp)
            </label>
            <div className="relative">
              <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-500">
                Rp
              </span>
              <input
                ref={inputRef}
                type="number"
                min="0"
                step="100"
                value={paidAmount}
                onChange={(e) => setPaidAmount(e.target.value)}
                placeholder="0"
                className="w-full pl-11 pr-4 py-2.5 bg-white border-2 border-emerald-500 focus:border-emerald-600 rounded-xl text-lg font-mono font-bold text-gray-900 text-right focus:outline-none transition shadow-xs"
              />
            </div>
          </div>

          {/* Quick Cash Buttons */}
          <div>
            <div className="text-[11px] font-semibold text-gray-500 mb-1.5">Pilihan Nominal Cepat:</div>
            <div className="grid grid-cols-3 gap-2">
              {quickNominals.map((opt, idx) => (
                <button
                  key={idx}
                  type="button"
                  onClick={() => setPaidAmount(opt.value)}
                  className={`py-1.5 px-2 rounded-lg text-xs font-mono font-bold border transition cursor-pointer ${
                    currentPaid === opt.value
                      ? 'bg-emerald-500 text-white border-emerald-500 shadow-xs'
                      : 'bg-white hover:bg-emerald-50 text-emerald-800 border-gray-200 hover:border-emerald-300'
                  }`}
                >
                  {opt.label}
                </button>
              ))}
            </div>
          </div>

          {/* Kartu Status Kembalian / Kekurangan */}
          <div
            className={`p-3 rounded-xl border flex items-center justify-between transition ${
              isSufficient
                ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
                : 'bg-rose-50 border-rose-200 text-rose-800'
            }`}
          >
            <div className="text-xs font-semibold">
              {isSufficient ? 'Uang Kembalian:' : 'Uang Kurang:'}
            </div>
            <div className="font-mono font-black text-lg">
              {isSufficient ? formatRupiah(changeAmount) : formatRupiah(grandTotal - currentPaid)}
            </div>
          </div>

          {/* Actions Footer */}
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
              disabled={!isSufficient || isSubmitting}
              className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5 cursor-pointer active:scale-95"
            >
              {isSubmitting ? (
                <>
                  <span className="animate-spin text-sm">⏳</span>
                  <span>Menyimpan...</span>
                </>
              ) : (
                <>
                  <span>✓</span>
                  <span>Bayar & Cetak Struk</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default CashPaymentModal;
