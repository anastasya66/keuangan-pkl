import React, { useState, useEffect } from 'react';

export function SupplierModal({ isOpen, onClose, onSubmit, supplier = null, isLoading = false }) {
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    is_active: true,
  });

  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (supplier) {
      setFormData({
        name: supplier.name || '',
        code: supplier.code || '',
        contact_person: supplier.contact_person || '',
        phone: supplier.phone || '',
        email: supplier.email || '',
        address: supplier.address || '',
        is_active: supplier.is_active ?? true,
      });
    } else {
      setFormData({
        name: '',
        code: '',
        contact_person: '',
        phone: '',
        email: '',
        address: '',
        is_active: true,
      });
    }
    setErrors({});
  }, [supplier, isOpen]);

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
    if (!formData.name.trim()) {
      setErrors({ name: 'Nama supplier wajib diisi' });
      return;
    }
    onSubmit(formData);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/40 backdrop-blur-xs animate-backdrop">
      <div className="bg-white rounded-2xl border border-[#D7EBEF] shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
        {/* Modal Header */}
        <div className="px-6 py-4 border-b border-[#EAF4F6] flex items-center justify-between bg-[#F8FDFE]">
          <div>
            <h3 className="text-base font-bold text-[#16323B]">
              {supplier ? 'Edit Data Supplier' : 'Tambah Supplier Baru'}
            </h3>
            <p className="text-xs text-[#577782]">
              {supplier ? 'Perbarui informasi detail mitra pemasok toko' : 'Daftarkan mitra pemasok / distributor barang toko'}
            </p>
          </div>
          <button
            onClick={onClose}
            type="button"
            className="text-[#577782] hover:text-[#16323B] p-1.5 rounded-lg hover:bg-[#EAF8FA] transition"
          >
            ✕
          </button>
        </div>

        {/* Modal Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4 overflow-y-auto">
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1">
              Nama Supplier / Distributor <span className="text-rose-500">*</span>
            </label>
            <input
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              placeholder="Contoh: PT Sumber Pangan Makmur"
              className={`w-full px-3.5 py-2.5 bg-[#F8FDFE] border ${
                errors.name ? 'border-rose-400 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#27C5E5]'
              } rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none transition`}
            />
            {errors.name && <p className="mt-1 text-xs text-rose-500">{errors.name}</p>}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Kode Supplier (Otomatis jika kosong)
              </label>
              <input
                type="text"
                name="code"
                value={formData.code}
                onChange={handleChange}
                placeholder="SUP-0001"
                className="w-full px-3.5 py-2.5 bg-[#F8FDFE] border border-[#D7EBEF] focus:border-[#27C5E5] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none transition"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Nama Kontak Person (PIC)
              </label>
              <input
                type="text"
                name="contact_person"
                value={formData.contact_person}
                onChange={handleChange}
                placeholder="Contoh: Budi Santoso (Sales)"
                className="w-full px-3.5 py-2.5 bg-[#F8FDFE] border border-[#D7EBEF] focus:border-[#27C5E5] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none transition"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Nomor Telepon / WhatsApp
              </label>
              <input
                type="text"
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                placeholder="08123456789"
                className="w-full px-3.5 py-2.5 bg-[#F8FDFE] border border-[#D7EBEF] focus:border-[#27C5E5] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none transition"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Email
              </label>
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                placeholder="sales@supplier.com"
                className="w-full px-3.5 py-2.5 bg-[#F8FDFE] border border-[#D7EBEF] focus:border-[#27C5E5] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none transition"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1">
              Alamat Lengkap / Gudang
            </label>
            <textarea
              name="address"
              rows="3"
              value={formData.address}
              onChange={handleChange}
              placeholder="Jl. Pergudangan Indah No. 12, Surabaya..."
              className="w-full px-3.5 py-2.5 bg-[#F8FDFE] border border-[#D7EBEF] focus:border-[#27C5E5] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none transition"
            />
          </div>

          <div className="flex items-center gap-2 pt-1">
            <input
              type="checkbox"
              id="is_active"
              name="is_active"
              checked={formData.is_active}
              onChange={handleChange}
              className="w-4 h-4 text-[#27C5E5] border-[#D7EBEF] rounded focus:ring-[#27C5E5]"
            />
            <label htmlFor="is_active" className="text-xs font-medium text-[#16323B] cursor-pointer">
              Status Aktif (dapat dipilih pada transaksi pembelian)
            </label>
          </div>

          {/* Modal Footer */}
          <div className="pt-4 border-t border-[#EAF4F6] flex items-center justify-end gap-3">
            <button
              type="button"
              onClick={onClose}
              disabled={isLoading}
              className="px-4 py-2 rounded-xl text-xs font-semibold text-[#577782] hover:bg-[#F0F9FB] transition"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isLoading}
              className="px-5 py-2 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#167A93] text-white shadow-xs transition disabled:opacity-50"
            >
              {isLoading ? 'Menyimpan...' : supplier ? 'Simpan Perubahan' : 'Tambah Supplier'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default SupplierModal;

