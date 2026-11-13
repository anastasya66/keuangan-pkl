import React, { useState, useEffect } from 'react';
import { X, CheckCircle2, AlertCircle, Banknote, Building2 } from 'lucide-react';
import { formatRupiah } from '../../utils/currency';

export default function ConsignmentSettlementModal({
  isOpen,
  onClose,
  onSubmit,
  consignor = null,
  isSubmitting = false,
}) {
  const [formData, setFormData] = useState({
    settlement_date: new Date().toISOString().slice(0, 10),
    payment_method: 'transfer',
    reference_number: '',
    notes: '',
  });

  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (consignor) {
      setFormData({
        settlement_date: new Date().toISOString().slice(0, 10),
        payment_method: consignor.bank_account_number ? 'transfer' : 'cash',
        reference_number: '',
        notes: `Penyetoran bagi hasil penjualan konsinyasi periode s/d ${new Date().toLocaleDateString('id-ID')}`,
      });
      setErrors({});
    }
  }, [consignor, isOpen]);

  if (!isOpen || !consignor) return null;

  const unsettledAmount = Number(consignor.unsettled_amount) || 0;

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (unsettledAmount <= 0) {
      setErrors({ general: 'Mitra ini tidak memiliki saldo tagihan penjualan konsinyasi yang perlu disetor.' });
      return;
    }

    onSubmit({
      consignor_id: consignor.id,
      settlement_date: formData.settlement_date,
      payment_method: formData.payment_method,
      reference_number: formData.reference_number,
      notes: formData.notes,
    });
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-fade-in">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-lg border border-[#E7ECEE] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="px-6 py-4 border-b border-[#E7ECEE] flex items-center justify-between bg-[#F8FAFA]">
          <div>
            <div className="flex items-center gap-2">
              <h3 className="text-base font-bold text-[#20282D]">Penyelesaian Setoran (Settlement)</h3>
              <span className="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-800 rounded-md">
                Bagi Hasil
              </span>
            </div>
            <p className="text-xs text-[#879197] mt-0.5">
              Proses penyetoran hak bagi hasil barang konsinyasi yang telah laku terjual di kasir.
            </p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1.5 rounded-lg text-[#879197] hover:text-[#20282D] hover:bg-[#EAEFF1] transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {errors.general && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-600 flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{errors.general}</span>
            </div>
          )}

          {/* Info Mitra & Rekening */}
          <div className="p-4 bg-gradient-to-br from-[#F8FAFA] to-[#EFF7FA] rounded-2xl border border-[#D5E3E7] space-y-2.5">
            <div className="flex items-center justify-between">
              <div>
                <span className="text-[10px] font-mono text-[#879197] uppercase">PENERIMA SETORAN</span>
                <h4 className="text-sm font-bold text-[#20282D]">
                  {consignor.name} <span className="text-xs font-mono text-[#879197]">({consignor.code})</span>
                </h4>
              </div>
              <span className="px-2.5 py-1 text-xs font-bold bg-amber-100 text-amber-800 rounded-xl">
                {consignor.unsettled_items_count || 0} Item Transaksi
              </span>
            </div>

            {consignor.bank_account_number ? (
              <div className="p-2.5 bg-white rounded-xl border border-[#D5E3E7] flex items-center gap-3">
                <Building2 className="w-5 h-5 text-[#167A93] shrink-0" />
                <div className="text-xs">
                  <span className="font-bold text-[#20282D]">
                    {consignor.bank_name || 'Bank'} — {consignor.bank_account_number}
                  </span>
                  <span className="block text-[11px] text-[#879197]">
                    a.n. {consignor.bank_account_holder || consignor.name}
                  </span>
                </div>
              </div>
            ) : (
              <div className="text-[11px] text-[#879197] italic">
                Mitra belum mendaftarkan nomor rekening bank (pembayaran tunai langsung).
              </div>
            )}

            {/* Total Nominal Setor */}
            <div className="pt-2 border-t border-[#D5E3E7] flex items-center justify-between">
              <span className="text-xs font-bold text-[#20282D]">Total Hak Mitra yang Disetor:</span>
              <span className="text-base font-black text-[#167A93] font-mono">
                {formatRupiah(unsettledAmount)}
              </span>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">Tanggal Setor</label>
              <input
                type="date"
                name="settlement_date"
                value={formData.settlement_date}
                onChange={handleChange}
                className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">Metode Penyetoran</label>
              <select
                name="payment_method"
                value={formData.payment_method}
                onChange={handleChange}
                className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl bg-white focus:outline-none focus:border-[#55BFD8]"
              >
                <option value="transfer">Transfer Bank</option>
                <option value="cash">Tunai / Kasir</option>
                <option value="qris">QRIS / E-Wallet</option>
              </select>
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-[#20282D] mb-1">
              Nomor Referensi / No. Bukti Transfer (Opsional)
            </label>
            <input
              type="text"
              name="reference_number"
              value={formData.reference_number}
              onChange={handleChange}
              placeholder="Contoh: TRF-20261111-98765"
              className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl font-mono focus:outline-none focus:border-[#55BFD8]"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-[#20282D] mb-1">Catatan Tambahan</label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              rows={2}
              className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8]"
            />
          </div>

          {/* Footer */}
          <div className="pt-3 border-t border-[#E7ECEE] flex items-center justify-end gap-2.5">
            <button
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
              className="px-4 py-2 text-xs font-semibold text-[#879197] hover:text-[#20282D] hover:bg-[#EAEFF1] rounded-xl transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isSubmitting || unsettledAmount <= 0}
              className="px-5 py-2 text-xs font-bold text-white bg-[#059669] hover:bg-emerald-700 rounded-xl transition flex items-center gap-2 shadow-xs cursor-pointer disabled:opacity-50"
            >
              <CheckCircle2 className="w-4 h-4" />
              <span>{isSubmitting ? 'Memproses Setoran...' : 'Konfirmasi Selesai & Bayar'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

