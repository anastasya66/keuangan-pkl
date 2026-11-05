import { useEffect, useState } from 'react';
import { IconTag } from '../../components/common/Icons';

export function CategoryModal({
  isOpen,
  category = null,
  isLoading = false,
  onClose,
  onSubmit,
}) {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    is_active: true,
  });
  const [fieldErrors, setFieldErrors] = useState({});

  useEffect(() => {
    if (category) {
      setFormData({
        name: category.name || '',
        description: category.description || '',
        is_active: category.is_active ?? true,
      });
    } else {
      setFormData({
        name: '',
        description: '',
        is_active: true,
      });
    }
    setFieldErrors({});
  }, [category, isOpen]);

  if (!isOpen) return null;

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
    setFieldErrors((prev) => ({ ...prev, [name]: null }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.name.trim()) {
      setFieldErrors({ name: 'Nama kategori wajib diisi.' });
      return;
    }

    const result = await onSubmit(formData);
    if (result && !result.success && result.errors) {
      setFieldErrors(result.errors);
    }
  };

  const isEdit = Boolean(category?.id);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/35 backdrop-blur-xs animate-backdrop">
      <div className="bg-white border border-[#D7EBEF] rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 text-[#16323B] animate-modal-pop">
        <div className="flex items-center justify-between pb-4 border-b border-[#D7EBEF]">
          <h3 className="text-base sm:text-lg font-bold text-[#10262E] flex items-center gap-2">
            <span className="p-2 rounded-xl bg-[#EAF8FA] text-[#27C5E5]">
              <IconTag className="w-5 h-5" />
            </span>
            <span>{isEdit ? 'Ubah Kategori Barang' : 'Tambah Kategori Baru'}</span>
          </h3>
          <button
            type="button"
            onClick={onClose}
            className="w-8 h-8 rounded-xl bg-[#F3FBFC] hover:bg-[#EAF8FA] text-[#8EA6AF] hover:text-[#16323B] transition-colors flex items-center justify-center cursor-pointer btn-press"
          >
            ✕
          </button>
        </div>

        <form onSubmit={handleSubmit} className="mt-5 space-y-4">
          {/* Name Field */}
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
              Nama Kategori <span className="text-rose-500">*</span>
            </label>
            <input
              type="text"
              name="name"
              required
              value={formData.name}
              onChange={handleChange}
              placeholder="Contoh: Sembako, Minuman, Alat Tulis"
              className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow ${
                fieldErrors.name ? 'border-rose-300 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#38BFE0]'
              }`}
            />
            {fieldErrors.name && (
              <p className="mt-1 text-[11px] text-rose-500 font-medium">
                {Array.isArray(fieldErrors.name) ? fieldErrors.name[0] : fieldErrors.name}
              </p>
            )}
          </div>

          {/* Description Field */}
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
              Catatan Kategori (Opsional)
            </label>
            <textarea
              name="description"
              rows="3"
              value={formData.description}
              onChange={handleChange}
              placeholder="Keterangan singkat mengenai kelompok barang ini..."
              className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow"
            />
          </div>

          {/* Active Status Checkbox */}
          <div>
            <label className="inline-flex items-center gap-2.5 cursor-pointer select-none">
              <input
                type="checkbox"
                name="is_active"
                checked={formData.is_active}
                onChange={handleChange}
                className="w-4 h-4 rounded text-[#27C5E5] focus:ring-[#27C5E5] border-[#D7EBEF]"
              />
              <span className="text-xs font-semibold text-[#16323B]">
                Kategori Aktif (Dapat langsung digunakan saat menambah barang)
              </span>
            </label>
          </div>

          {/* Action Buttons */}
          <div className="pt-4 border-t border-[#D7EBEF] flex items-center justify-end gap-3">
            <button
              type="button"
              disabled={isLoading}
              onClick={onClose}
              className="px-4 py-2.5 rounded-xl bg-white hover:bg-[#F3FBFC] border border-[#D7EBEF] text-xs font-semibold text-[#577782] transition-colors cursor-pointer disabled:opacity-50 btn-press"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isLoading}
              className="px-5 py-2.5 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-semibold text-xs shadow-md shadow-[#27C5E5]/20 transition-all cursor-pointer disabled:opacity-50 flex items-center gap-2 btn-press"
            >
              {isLoading && (
                <span className="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              )}
              {isEdit ? 'Simpan Perubahan' : 'Tambah Kategori'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default CategoryModal;
