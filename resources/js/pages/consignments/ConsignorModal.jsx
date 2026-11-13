import React, { useState, useEffect } from 'react';
import { X, Save, AlertCircle } from 'lucide-react';

export default function ConsignorModal({ isOpen, onClose, onSubmit, initialData = null, isSubmitting = false }) {
  const [formData, setFormData] = useState({
    code: '',
    name: '',
    phone: '',
    email: '',
    address: '',
    bank_name: '',
    bank_account_number: '',
    bank_account_holder: '',
    default_commission_rate: 10,
    notes: '',
    is_active: true,
  });

  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (initialData) {
      setFormData({
        code: initialData.code || '',
        name: initialData.name || '',
        phone: initialData.phone || '',
        email: initialData.email || '',
        address: initialData.address || '',
        bank_name: initialData.bank_name || '',
        bank_account_number: initialData.bank_account_number || '',
        bank_account_holder: initialData.bank_account_holder || '',
        default_commission_rate: initialData.default_commission_rate ?? 10,
        notes: initialData.notes || '',
        is_active: initialData.is_active ?? true,
      });
    } else {
      setFormData({
        code: '',
        name: '',
        phone: '',
        email: '',
        address: '',
        bank_name: '',
        bank_account_number: '',
        bank_account_holder: '',
        default_commission_rate: 10,
        notes: '',
        is_active: true,
      });
    }
    setErrors({});
  }, [initialData, isOpen]);

  if (!isOpen) return null;

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const errs = {};
    if (!formData.name.trim()) errs.name = 'Nama mitra konsinyor wajib diisi';
    if (Object.keys(errs).length > 0) {
      setErrors(errs);
      return;
    }
    onSubmit(formData);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-fade-in">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-lg border border-[#E7ECEE] overflow-hidden flex flex-col max-h-[90vh]">
        {/* Modal Header */}
        <div className="px-6 py-4 border-b border-[#E7ECEE] flex items-center justify-between bg-[#F8FAFA]">
          <div>
            <h3 className="text-base font-bold text-[#20282D]">
              {initialData ? 'Edit Data Mitra Konsinyor' : 'Tambah Mitra Konsinyor (Pemilik Barang)'}
            </h3>
            <p className="text-xs text-[#879197] mt-0.5">
              Kelola data pihak penitip barang dagangan untuk sistem konsinyasi toko.
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

        {/* Modal Body / Form */}
        <form onSubmit={handleSubmit} className="p-6 overflow-y-auto space-y-4 flex-1">
          {errors.general && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-600 flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{errors.general}</span>
            </div>
          )}

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">
                Kode Mitra <span className="text-[#879197] font-normal">(Otomatis jika kosong)</span>
              </label>
              <input
                type="text"
                name="code"
                value={formData.code}
                onChange={handleChange}
                placeholder="SUP-KNS-001"
                className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl font-mono focus:outline-none focus:border-[#55BFD8]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">
                Nama Mitra (Konsinyor) <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                name="name"
                value={formData.name}
                onChange={handleChange}
                placeholder="Contoh: Bakery Bu Ani / Snack Barokah"
                className={`w-full px-3 py-2 text-xs border rounded-xl focus:outline-none ${
                  errors.name ? 'border-rose-500' : 'border-[#E7ECEE] focus:border-[#55BFD8]'
                }`}
              />
              {errors.name && <p className="text-[11px] text-rose-500 mt-1">{errors.name}</p>}
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">No. WhatsApp / Telepon</label>
              <input
                type="text"
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                placeholder="08123456789"
                className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">Email</label>
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                placeholder="mitra@domain.com"
                className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8]"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-[#20282D] mb-1">Alamat Lengkap</label>
            <textarea
              name="address"
              value={formData.address}
              onChange={handleChange}
              rows={2}
              placeholder="Alamat tempat tinggal / workshop mitra..."
              className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8]"
            />
          </div>

          {/* Rekening Pembayaran */}
          <div className="p-3 bg-[#F8FAFA] rounded-xl border border-[#E7ECEE] space-y-3">
            <span className="text-[11px] font-bold uppercase tracking-wider text-[#167A93] block">
              Rekening Bank Penyetoran Bagi Hasil
            </span>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label className="block text-[11px] font-semibold text-[#879197] mb-1">Bank</label>
                <input
                  type="text"
                  name="bank_name"
                  value={formData.bank_name}
                  onChange={handleChange}
                  placeholder="BCA / BRI / Mandiri"
                  className="w-full px-2.5 py-1.5 text-xs border border-[#E7ECEE] rounded-lg bg-white focus:outline-none focus:border-[#55BFD8]"
                />
              </div>
              <div>
                <label className="block text-[11px] font-semibold text-[#879197] mb-1">No. Rekening</label>
                <input
                  type="text"
                  name="bank_account_number"
                  value={formData.bank_account_number}
                  onChange={handleChange}
                  placeholder="1234567890"
                  className="w-full px-2.5 py-1.5 text-xs border border-[#E7ECEE] rounded-lg bg-white font-mono focus:outline-none focus:border-[#55BFD8]"
                />
              </div>
              <div>
                <label className="block text-[11px] font-semibold text-[#879197] mb-1">Atas Nama</label>
                <input
                  type="text"
                  name="bank_account_holder"
                  value={formData.bank_account_holder}
                  onChange={handleChange}
                  placeholder="Nama Pemilik Rekening"
                  className="w-full px-2.5 py-1.5 text-xs border border-[#E7ECEE] rounded-lg bg-white focus:outline-none focus:border-[#55BFD8]"
                />
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">
                Bagi Hasil Komisi Toko Default (%)
              </label>
              <div className="relative">
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  name="default_commission_rate"
                  value={formData.default_commission_rate}
                  onChange={handleChange}
                  className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl pr-8 focus:outline-none focus:border-[#55BFD8]"
                />
                <span className="absolute right-3 top-2 text-xs font-bold text-[#879197]">%</span>
              </div>
              <p className="text-[10px] text-[#879197] mt-1">
                Persentase bagian toko saat produk dari mitra ini terjual.
              </p>
            </div>

            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">Status Keaktifan</label>
              <label className="flex items-center gap-2 mt-2.5 cursor-pointer">
                <input
                  type="checkbox"
                  name="is_active"
                  checked={formData.is_active}
                  onChange={handleChange}
                  className="w-4 h-4 rounded text-[#167A93] focus:ring-0 cursor-pointer"
                />
                <span className="text-xs font-semibold text-[#20282D]">Mitra Aktif & Berjalan</span>
              </label>
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-[#20282D] mb-1">Catatan Tambahan</label>
            <input
              type="text"
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              placeholder="Contoh: Jadwal titip setiap hari Senin pagi..."
              className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8]"
            />
          </div>

          {/* Modal Footer */}
          <div className="pt-4 border-t border-[#E7ECEE] flex items-center justify-end gap-2.5">
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
              disabled={isSubmitting}
              className="px-5 py-2 text-xs font-bold text-white bg-[#20282D] hover:bg-[#167A93] rounded-xl transition flex items-center gap-2 shadow-xs cursor-pointer disabled:opacity-50"
            >
              <Save className="w-4 h-4" />
              <span>{isSubmitting ? 'Menyimpan...' : 'Simpan Mitra'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

