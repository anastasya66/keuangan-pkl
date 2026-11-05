import { useEffect, useState } from 'react';

export function UnitModal({
  isOpen,
  unit = null,
  isLoading = false,
  onClose,
  onSubmit,
}) {
  const [formData, setFormData] = useState({
    name: '',
    symbol: '',
    description: '',
    is_active: true,
  });
  const [fieldErrors, setFieldErrors] = useState({});

  useEffect(() => {
    if (unit) {
      setFormData({
        name: unit.name || '',
        symbol: unit.symbol || '',
        description: unit.description || '',
        is_active: unit.is_active ?? true,
      });
    } else {
      setFormData({
        name: '',
        symbol: '',
        description: '',
        is_active: true,
      });
    }
    setFieldErrors({});
  }, [unit, isOpen]);

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
    const errors = {};
    if (!formData.name.trim()) errors.name = 'Nama satuan wajib diisi.';
    if (!formData.symbol.trim()) errors.symbol = 'Simbol satuan wajib diisi.';

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    const result = await onSubmit(formData);
    if (result && !result.success && result.errors) {
      setFieldErrors(result.errors);
    }
  };

  const isEdit = Boolean(unit?.id);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
      <div className="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 text-slate-100">
        <div className="flex items-center justify-between pb-4 border-b border-slate-800">
          <h3 className="text-lg font-bold text-white flex items-center gap-2">
            <span>📏</span> {isEdit ? 'Ubah Data Satuan' : 'Tambah Satuan Baru'}
          </h3>
          <button
            type="button"
            onClick={onClose}
            className="text-slate-400 hover:text-white transition-colors cursor-pointer p-1"
          >
            ✕
          </button>
        </div>

        <form onSubmit={handleSubmit} className="mt-5 space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {/* Name Field */}
            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                Nama Satuan <span className="text-rose-400">*</span>
              </label>
              <input
                type="text"
                name="name"
                required
                value={formData.name}
                onChange={handleChange}
                placeholder="Contoh: Kilogram, Pieces"
                className={`w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all ${
                  fieldErrors.name ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700'
                }`}
              />
              {fieldErrors.name && (
                <p className="mt-1 text-xs text-rose-400 font-medium">
                  {Array.isArray(fieldErrors.name) ? fieldErrors.name[0] : fieldErrors.name}
                </p>
              )}
            </div>

            {/* Symbol Field */}
            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                Simbol Satuan <span className="text-rose-400">*</span>
              </label>
              <input
                type="text"
                name="symbol"
                required
                value={formData.symbol}
                onChange={handleChange}
                placeholder="Contoh: kg, pcs, L, pack"
                className={`w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all ${
                  fieldErrors.symbol ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700'
                }`}
              />
              {fieldErrors.symbol && (
                <p className="mt-1 text-xs text-rose-400 font-medium">
                  {Array.isArray(fieldErrors.symbol) ? fieldErrors.symbol[0] : fieldErrors.symbol}
                </p>
              )}
            </div>
          </div>

          {/* Description Field */}
          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1.5">
              Deskripsi (Opsional)
            </label>
            <textarea
              name="description"
              rows={3}
              value={formData.description}
              onChange={handleChange}
              placeholder="Keterangan mengenai standar takaran atau kemasan satuan ini"
              className="w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all resize-none"
            />
          </div>

          {/* Status Checkbox */}
          <div className="flex items-center gap-3 pt-2">
            <input
              type="checkbox"
              id="is_active_unit"
              name="is_active"
              checked={formData.is_active}
              onChange={handleChange}
              className="w-4 h-4 rounded text-emerald-600 bg-slate-800 border-slate-700 focus:ring-emerald-500 cursor-pointer"
            />
            <label htmlFor="is_active_unit" className="text-sm font-medium text-slate-300 cursor-pointer">
              Satuan Aktif (Dapat dipilih pada produk baru)
            </label>
          </div>

          {/* Action Buttons */}
          <div className="mt-6 pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
            <button
              type="button"
              disabled={isLoading}
              onClick={onClose}
              className="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-sm font-medium text-slate-300 transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isLoading}
              className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white text-sm font-semibold shadow-lg shadow-emerald-900/30 transition-all cursor-pointer flex items-center gap-2 disabled:opacity-50"
            >
              {isLoading && (
                <span className="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
              )}
              {isEdit ? 'Simpan Perubahan' : 'Tambah Satuan'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default UnitModal;

