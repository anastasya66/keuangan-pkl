import { useEffect, useState } from 'react';
import categoryService from '../../services/categoryService';
import unitService from '../../services/unitService';
import { formatRupiah } from '../../utils/currency';

export function ProductModal({
  isOpen,
  product = null,
  isLoading = false,
  onClose,
  onSubmit,
}) {
  const [categories, setCategories] = useState([]);
  const [units, setUnits] = useState([]);
  const [isLoadingDropdowns, setIsLoadingDropdowns] = useState(false);

  const [formData, setFormData] = useState({
    category_id: '',
    unit_id: '',
    sku: '',
    barcode: '',
    name: '',
    description: '',
    purchase_price: '',
    selling_price: '',
    is_active: true,
  });

  const [fieldErrors, setFieldErrors] = useState({});

  // Fetch active categories and units for select dropdowns
  useEffect(() => {
    if (!isOpen) return;

    let isMounted = true;
    async function loadDropdowns() {
      setIsLoadingDropdowns(true);
      try {
        const [catRes, unitRes] = await Promise.all([
          categoryService.getCategories({ all: true }),
          unitService.getUnits({ all: true }),
        ]);

        if (isMounted) {
          if (catRes.success) setCategories(catRes.data);
          if (unitRes.success) setUnits(unitRes.data);
        }
      } catch (err) {
        console.error('Gagal memuat kategori atau satuan:', err);
      } finally {
        if (isMounted) setIsLoadingDropdowns(false);
      }
    }

    loadDropdowns();
    return () => {
      isMounted = false;
    };
  }, [isOpen]);

  // Set form data on modal open / product change
  useEffect(() => {
    if (product) {
      setFormData({
        category_id: product.category_id || product.category?.id || '',
        unit_id: product.unit_id || product.unit?.id || '',
        sku: product.sku || '',
        barcode: product.barcode || '',
        name: product.name || '',
        description: product.description || '',
        purchase_price: product.purchase_price_numeric ?? product.purchase_price ?? '',
        selling_price: product.selling_price_numeric ?? product.selling_price ?? '',
        is_active: product.is_active ?? true,
      });
    } else {
      setFormData({
        category_id: '',
        unit_id: '',
        sku: '',
        barcode: '',
        name: '',
        description: '',
        purchase_price: '',
        selling_price: '',
        is_active: true,
      });
    }
    setFieldErrors({});
  }, [product, isOpen]);

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

    if (!formData.name.trim()) errors.name = 'Nama produk wajib diisi.';
    if (!formData.sku.trim()) errors.sku = 'SKU produk wajib diisi.';
    if (!formData.category_id) errors.category_id = 'Pilih kategori produk.';
    if (!formData.unit_id) errors.unit_id = 'Pilih satuan produk.';
    if (formData.purchase_price === '' || isNaN(formData.purchase_price) || Number(formData.purchase_price) < 0) {
      errors.purchase_price = 'Harga beli harus berupa angka tidak negatif.';
    }
    if (formData.selling_price === '' || isNaN(formData.selling_price) || Number(formData.selling_price) < 0) {
      errors.selling_price = 'Harga jual harus berupa angka tidak negatif.';
    }

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    const payload = {
      ...formData,
      purchase_price: Number(formData.purchase_price),
      selling_price: Number(formData.selling_price),
      barcode: formData.barcode?.trim() || null,
    };

    const result = await onSubmit(payload);
    if (result && !result.success && result.errors) {
      setFieldErrors(result.errors);
    }
  };

  const isEdit = Boolean(product?.id);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto animate-fade-in">
      <div className="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 text-slate-100 my-8">
        <div className="flex items-center justify-between pb-4 border-b border-slate-800">
          <h3 className="text-lg font-bold text-white flex items-center gap-2">
            <span>📦</span> {isEdit ? 'Ubah Informasi Produk' : 'Tambah Produk Baru'}
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
          {/* Row 1: Name & SKU */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="sm:col-span-2">
              <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                Nama Produk <span className="text-rose-400">*</span>
              </label>
              <input
                type="text"
                name="name"
                required
                value={formData.name}
                onChange={handleChange}
                placeholder="Contoh: Beras Rojolele 5kg"
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

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                Kode / SKU <span className="text-rose-400">*</span>
              </label>
              <input
                type="text"
                name="sku"
                required
                value={formData.sku}
                onChange={handleChange}
                placeholder="PRD-001"
                className={`w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all ${
                  fieldErrors.sku ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700'
                }`}
              />
              {fieldErrors.sku && (
                <p className="mt-1 text-xs text-rose-400 font-medium">
                  {Array.isArray(fieldErrors.sku) ? fieldErrors.sku[0] : fieldErrors.sku}
                </p>
              )}
            </div>
          </div>

          {/* Row 2: Category, Unit, Barcode */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                Kategori <span className="text-rose-400">*</span>
              </label>
              <select
                name="category_id"
                required
                value={formData.category_id}
                onChange={handleChange}
                disabled={isLoadingDropdowns}
                className={`w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all ${
                  fieldErrors.category_id ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700'
                }`}
              >
                <option value="">-- Pilih Kategori --</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name} {!c.is_active ? '(Nonaktif)' : ''}
                  </option>
                ))}
              </select>
              {fieldErrors.category_id && (
                <p className="mt-1 text-xs text-rose-400 font-medium">
                  {Array.isArray(fieldErrors.category_id) ? fieldErrors.category_id[0] : fieldErrors.category_id}
                </p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                Satuan <span className="text-rose-400">*</span>
              </label>
              <select
                name="unit_id"
                required
                value={formData.unit_id}
                onChange={handleChange}
                disabled={isLoadingDropdowns}
                className={`w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all ${
                  fieldErrors.unit_id ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700'
                }`}
              >
                <option value="">-- Pilih Satuan --</option>
                {units.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name} ({u.symbol}) {!u.is_active ? '(Nonaktif)' : ''}
                  </option>
                ))}
              </select>
              {fieldErrors.unit_id && (
                <p className="mt-1 text-xs text-rose-400 font-medium">
                  {Array.isArray(fieldErrors.unit_id) ? fieldErrors.unit_id[0] : fieldErrors.unit_id}
                </p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                Barcode (Opsional)
              </label>
              <input
                type="text"
                name="barcode"
                value={formData.barcode}
                onChange={handleChange}
                placeholder="Contoh: 8991234560011"
                className={`w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all ${
                  fieldErrors.barcode ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700'
                }`}
              />
              {fieldErrors.barcode && (
                <p className="mt-1 text-xs text-rose-400 font-medium">
                  {Array.isArray(fieldErrors.barcode) ? fieldErrors.barcode[0] : fieldErrors.barcode}
                </p>
              )}
            </div>
          </div>

          {/* Row 3: Purchase Price & Selling Price */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-800/50 p-4 rounded-xl border border-slate-700/60">
            <div>
              <div className="flex items-center justify-between mb-1.5">
                <label className="text-xs font-semibold text-slate-300">
                  Harga Beli (Modal) <span className="text-rose-400">*</span>
                </label>
                <span className="text-[11px] font-semibold text-emerald-400">
                  {formatRupiah(formData.purchase_price)}
                </span>
              </div>
              <div className="relative">
                <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">
                  Rp
                </span>
                <input
                  type="number"
                  min="0"
                  step="any"
                  name="purchase_price"
                  required
                  value={formData.purchase_price}
                  onChange={handleChange}
                  placeholder="0"
                  className={`w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-800/90 border text-white text-sm font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all ${
                    fieldErrors.purchase_price ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700'
                  }`}
                />
              </div>
              {fieldErrors.purchase_price && (
                <p className="mt-1 text-xs text-rose-400 font-medium">
                  {Array.isArray(fieldErrors.purchase_price) ? fieldErrors.purchase_price[0] : fieldErrors.purchase_price}
                </p>
              )}
            </div>

            <div>
              <div className="flex items-center justify-between mb-1.5">
                <label className="text-xs font-semibold text-slate-300">
                  Harga Jual (Toko) <span className="text-rose-400">*</span>
                </label>
                <span className="text-[11px] font-semibold text-cyan-400">
                  {formatRupiah(formData.selling_price)}
                </span>
              </div>
              <div className="relative">
                <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">
                  Rp
                </span>
                <input
                  type="number"
                  min="0"
                  step="any"
                  name="selling_price"
                  required
                  value={formData.selling_price}
                  onChange={handleChange}
                  placeholder="0"
                  className={`w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-800/90 border text-white text-sm font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all ${
                    fieldErrors.selling_price ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700'
                  }`}
                />
              </div>
              {fieldErrors.selling_price && (
                <p className="mt-1 text-xs text-rose-400 font-medium">
                  {Array.isArray(fieldErrors.selling_price) ? fieldErrors.selling_price[0] : fieldErrors.selling_price}
                </p>
              )}
            </div>
          </div>

          {/* Description */}
          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1.5">
              Deskripsi Produk (Opsional)
            </label>
            <textarea
              name="description"
              rows={2}
              value={formData.description}
              onChange={handleChange}
              placeholder="Keterangan tambahan spesifikasi atau merk produk"
              className="w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all resize-none"
            />
          </div>

          {/* Active Status */}
          <div className="flex items-center gap-3 pt-1">
            <input
              type="checkbox"
              id="is_active_prod"
              name="is_active"
              checked={formData.is_active}
              onChange={handleChange}
              className="w-4 h-4 rounded text-emerald-600 bg-slate-800 border-slate-700 focus:ring-emerald-500 cursor-pointer"
            />
            <label htmlFor="is_active_prod" className="text-sm font-medium text-slate-300 cursor-pointer">
              Produk Aktif (Tersedia untuk transaksi kasir)
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
              {isEdit ? 'Simpan Perubahan' : 'Tambah Produk'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default ProductModal;

