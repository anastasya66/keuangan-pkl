import React, { useState, useEffect } from 'react';
import { X, ArrowDownLeft, ArrowUpRight, Save, AlertCircle } from 'lucide-react';

export default function ConsignmentStockModal({
  isOpen,
  onClose,
  onSubmit,
  product = null,
  isSubmitting = false,
}) {
  const [formData, setFormData] = useState({
    type: 'in', // 'in' (tambah stok titipan) atau 'return' (retur sisa ke mitra)
    quantity: 1,
    notes: '',
  });

  const [errors, setErrors] = useState({});
  const [serverError, setServerError] = useState(null);

  useEffect(() => {
    if (product) {
      setFormData({
        type: 'in',
        quantity: 1,
        notes: '',
      });
      setErrors({});
      setServerError(null);
    }
  }, [product, isOpen]);

  if (!isOpen || !product) return null;

  const currentStock = Number(product.stock) || 0;

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
    if (serverError) setServerError(null);
  };

  const handleSubmit = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setServerError(null);
    const qty = Number(formData.quantity);
    const errs = {};

    if (!qty || qty <= 0) {
      errs.quantity = 'Jumlah unit yang disesuaikan harus lebih dari 0';
    } else if (formData.type === 'return' && qty > currentStock) {
      errs.quantity = `Jumlah retur tidak boleh melebihi sisa stok fisik saat ini (${currentStock} ${product.unit?.symbol || 'unit'})`;
    }

    if (Object.keys(errs).length > 0) {
      setErrors(errs);
      return;
    }

    try {
      if (typeof onSubmit === 'function') {
        await onSubmit({
          product_id: product.id,
          type: formData.type,
          quantity: qty,
          notes: formData.notes,
        });
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Gagal menyimpan penyesuaian stok konsinyasi';
      setServerError(msg);
    }
  };

  const calculatedNewStock =
    formData.type === 'in'
      ? currentStock + (Number(formData.quantity) || 0)
      : Math.max(0, currentStock - (Number(formData.quantity) || 0));

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-fade-in">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-md border border-[#E7ECEE] overflow-hidden flex flex-col">
        {/* Modal Header */}
        <div className="px-6 py-4 border-b border-[#E7ECEE] flex items-center justify-between bg-[#F8FAFA]">
          <div>
            <h3 className="text-base font-bold text-[#20282D]">Penyesuaian Stok Konsinyasi</h3>
            <p className="text-xs text-[#879197] mt-0.5">
              Kelola penambahan barang titipan atau retur sisa ke mitra pemilik.
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

        {/* Modal Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {serverError && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 flex items-start gap-2">
              <AlertCircle className="w-4 h-4 shrink-0 text-rose-600 mt-0.5" />
              <span>{serverError}</span>
            </div>
          )}

          {/* Info Produk Konsinyasi */}
          <div className="p-3.5 bg-[#F8FAFA] rounded-xl border border-[#E7ECEE] flex items-center justify-between gap-3">
            <div className="min-w-0 flex-1">
              <div className="flex items-center gap-1.5 flex-wrap">
                <span className="text-[10px] font-mono text-[#879197] uppercase">PRODUK KONSINYASI</span>
                {product.sku && (
                  <span className="font-mono text-[10px] font-bold px-1.5 py-0.5 bg-[#E5F6FA] text-[#167A93] rounded border border-[#CFEFF6]">
                    {product.sku}
                  </span>
                )}
                {product.category?.name && (
                  <span className="text-[10px] px-1.5 py-0.5 bg-gray-100 text-[#577782] rounded">
                    {product.category.name}
                  </span>
                )}
              </div>
              <h4 className="text-sm font-bold text-[#20282D] truncate mt-1" title={product.name}>
                {product.name || product.product_name || 'Barang Konsinyasi'}
              </h4>
              <div className="text-[11px] text-[#879197] mt-0.5">
                Mitra: <span className="font-bold text-[#167A93]">{product.consignor?.name || '-'}</span>
              </div>
            </div>
            <div className="text-right shrink-0">
              <span className="text-[10px] text-[#879197] block">Sisa Stok Fisik</span>
              <span className="text-base font-black text-[#20282D] font-mono">
                {currentStock} <span className="text-xs font-normal text-[#879197]">{product.unit?.symbol || 'unit'}</span>
              </span>
            </div>
          </div>

          {/* Opsi Jenis Penyesuaian */}
          <div>
            <label className="block text-xs font-bold text-[#20282D] mb-1.5">Jenis Operasi Stok</label>
            <div className="grid grid-cols-2 gap-2.5">
              <button
                type="button"
                onClick={() => setFormData((p) => ({ ...p, type: 'in' }))}
                className={`p-3 rounded-xl border text-left flex items-start gap-2.5 transition cursor-pointer ${
                  formData.type === 'in'
                    ? 'border-[#059669] bg-emerald-50/70 text-emerald-900 shadow-2xs'
                    : 'border-[#E7ECEE] bg-white text-[#879197] hover:border-gray-300'
                }`}
              >
                <div
                  className={`p-1.5 rounded-lg shrink-0 ${
                    formData.type === 'in' ? 'bg-[#059669] text-white' : 'bg-gray-100 text-gray-500'
                  }`}
                >
                  <ArrowDownLeft className="w-4 h-4" />
                </div>
                <div>
                  <div className="text-xs font-bold leading-tight">Tambah Titipan</div>
                  <span className="text-[10px] opacity-80 mt-0.5 block leading-tight">
                    Kiriman stok baru dari mitra
                  </span>
                </div>
              </button>

              <button
                type="button"
                onClick={() => setFormData((p) => ({ ...p, type: 'return' }))}
                className={`p-3 rounded-xl border text-left flex items-start gap-2.5 transition cursor-pointer ${
                  formData.type === 'return'
                    ? 'border-amber-600 bg-amber-50/70 text-amber-900 shadow-2xs'
                    : 'border-[#E7ECEE] bg-white text-[#879197] hover:border-gray-300'
                }`}
              >
                <div
                  className={`p-1.5 rounded-lg shrink-0 ${
                    formData.type === 'return' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-500'
                  }`}
                >
                  <ArrowUpRight className="w-4 h-4" />
                </div>
                <div>
                  <div className="text-xs font-bold leading-tight">Retur ke Mitra</div>
                  <span className="text-[10px] opacity-80 mt-0.5 block leading-tight">
                    Pengembalian sisa barang
                  </span>
                </div>
              </button>
            </div>
          </div>

          {/* Input Qty */}
          <div>
            <label className="block text-xs font-bold text-[#20282D] mb-1">
              Jumlah Unit {formData.type === 'return' ? 'yang Diretur' : 'yang Ditambahkan'} <span className="text-rose-500">*</span>
            </label>
            <input
              type="number"
              min="1"
              step="1"
              name="quantity"
              value={formData.quantity}
              onChange={handleChange}
              className={`w-full px-3 py-2 text-xs font-mono font-bold border rounded-xl focus:outline-none ${
                errors.quantity ? 'border-rose-500' : 'border-[#E7ECEE] focus:border-[#55BFD8]'
              }`}
            />
            {errors.quantity && <p className="text-[11px] text-rose-500 mt-1">{errors.quantity}</p>}
          </div>

          {/* Preview Hasil Stok */}
          <div className="p-3 bg-white rounded-xl border border-[#E7ECEE] flex items-center justify-between">
            <span className="text-xs text-[#879197]">Estimasi Stok Setelah Proses:</span>
            <span className="text-sm font-extrabold text-[#167A93] font-mono">
              {calculatedNewStock} {product.unit?.symbol || 'unit'}
            </span>
          </div>

          {/* Keterangan */}
          <div>
            <label className="block text-xs font-bold text-[#20282D] mb-1">Catatan / Alasan</label>
            <input
              type="text"
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              placeholder={
                formData.type === 'return'
                  ? 'Contoh: Masa kedaluwarsa mendekat / ditarik oleh pemilik'
                  : 'Contoh: Titipan pengiriman tambahan minggu ini'
              }
              className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8]"
            />
          </div>

          {/* Modal Footer */}
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
              onClick={handleSubmit}
              disabled={isSubmitting}
              className="px-5 py-2 text-xs font-bold text-white bg-[#20282D] hover:bg-[#167A93] rounded-xl transition flex items-center gap-2 shadow-xs cursor-pointer disabled:opacity-50"
            >
              <Save className="w-4 h-4" />
              <span>{isSubmitting ? 'Memproses...' : 'Simpan Penyesuaian'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
