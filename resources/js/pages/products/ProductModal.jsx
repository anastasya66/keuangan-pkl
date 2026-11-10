import { useEffect, useState } from 'react';
import categoryService from '../../services/categoryService';
import unitService from '../../services/unitService';
import { formatRupiah } from '../../utils/currency';
import { IconBox } from '../../components/common/Icons';

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

    if (!formData.name.trim()) errors.name = 'Nama barang wajib diisi.';
    if (!formData.sku.trim()) errors.sku = 'Kode SKU barang wajib diisi.';
    if (!formData.category_id) errors.category_id = 'Pilih kategori barang.';
    if (!formData.unit_id) errors.unit_id = 'Pilih satuan barang.';
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
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/35 backdrop-blur-xs overflow-y-auto animate-backdrop">
      <div className="bg-white border border-[#D7EBEF] rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-8 text-[#16323B] my-8 animate-modal-pop">
        <div className="flex items-center justify-between pb-4 border-b border-[#D7EBEF]">
          <h3 className="text-base sm:text-lg font-bold text-[#10262E] flex items-center gap-2">
            <span className="p-2 rounded-xl bg-[#EAF8FA] text-[#27C5E5]">
              <IconBox className="w-5 h-5" />
            </span>
            <span>{isEdit ? 'Ubah Data Barang' : 'Tambah Barang'}</span>
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
          {/* Row 1: Product Name */}
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
              Nama Barang <span className="text-rose-500">*</span>
            </label>
            <input
              type="text"
              name="name"
              required
              value={formData.name}
              onChange={handleChange}
              placeholder="Contoh: Beras Ramos 5kg, Minyak Goreng 1L"
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

          {/* Row 2: Category and Unit Dropdowns */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Kategori <span className="text-rose-500">*</span>
              </label>
              <select
                name="category_id"
                required
                value={formData.category_id}
                onChange={handleChange}
                disabled={isLoadingDropdowns}
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow ${
                  fieldErrors.category_id ? 'border-rose-300 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#38BFE0]'
                }`}
              >
                <option value="">Pilih Kategori</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
              {fieldErrors.category_id && (
                <p className="mt-1 text-[11px] text-rose-500 font-medium">
                  {Array.isArray(fieldErrors.category_id) ? fieldErrors.category_id[0] : fieldErrors.category_id}
                </p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Satuan <span className="text-rose-500">*</span>
              </label>
              <select
                name="unit_id"
                required
                value={formData.unit_id}
                onChange={handleChange}
                disabled={isLoadingDropdowns}
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow ${
                  fieldErrors.unit_id ? 'border-rose-300 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#38BFE0]'
                }`}
              >
                <option value="">Pilih Satuan</option>
                {units.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name} ({u.symbol})
                  </option>
                ))}
              </select>
              {fieldErrors.unit_id && (
                <p className="mt-1 text-[11px] text-rose-500 font-medium">
                  {Array.isArray(fieldErrors.unit_id) ? fieldErrors.unit_id[0] : fieldErrors.unit_id}
                </p>
              )}
            </div>
          </div>

          {/* Row 3: SKU and Prices */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Kode SKU <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                name="sku"
                required
                value={formData.sku}
                onChange={handleChange}
                placeholder="Contoh: BRS-001"
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs font-mono uppercase focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow ${
                  fieldErrors.sku ? 'border-rose-300 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#38BFE0]'
                }`}
              />
              {fieldErrors.sku && (
                <p className="mt-1 text-[11px] text-rose-500 font-medium">
                  {Array.isArray(fieldErrors.sku) ? fieldErrors.sku[0] : fieldErrors.sku}
                </p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Harga Beli <span className="text-rose-500">*</span>
              </label>
              <input
                type="number"
                name="purchase_price"
                min="0"
                step="any"
                required
                value={formData.purchase_price}
                onChange={handleChange}
                placeholder="0"
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow ${
                  fieldErrors.purchase_price ? 'border-rose-300 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#38BFE0]'
                }`}
              />
              {formData.purchase_price !== '' && !isNaN(formData.purchase_price) && (
                <p className="mt-1 text-[10px] text-[#5A7C85] font-mono">
                  {formatRupiah(formData.purchase_price)}
                </p>
              )}
              {fieldErrors.purchase_price && (
                <p className="mt-1 text-[11px] text-rose-500 font-medium">
                  {Array.isArray(fieldErrors.purchase_price) ? fieldErrors.purchase_price[0] : fieldErrors.purchase_price}
                </p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Harga Jual <span className="text-rose-500">*</span>
              </label>
              <input
                type="number"
                name="selling_price"
                min="0"
                step="any"
                required
                value={formData.selling_price}
                onChange={handleChange}
                placeholder="0"
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs font-mono font-bold text-[#167A93] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow ${
                  fieldErrors.selling_price ? 'border-rose-300 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#38BFE0]'
                }`}
              />
              {formData.selling_price !== '' && !isNaN(formData.selling_price) && (
                <p className="mt-1 text-[10px] text-[#167A93] font-mono font-bold">
                  {formatRupiah(formData.selling_price)}
                </p>
              )}
              {fieldErrors.selling_price && (
                <p className="mt-1 text-[11px] text-rose-500 font-medium">
                  {Array.isArray(fieldErrors.selling_price) ? fieldErrors.selling_price[0] : fieldErrors.selling_price}
                </p>
              )}
            </div>
          </div>

          {/* Row 4: Barcode & Description */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Barcode (Opsional)
              </label>
              <input
                type="text"
                name="barcode"
                value={formData.barcode}
                onChange={handleChange}
                placeholder="Contoh: 89912345678"
                className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow"
              />
            </div>
            <div className="sm:col-span-2">
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Deskripsi (Opsional)
              </label>
              <input
                type="text"
                name="description"
                value={formData.description}
                onChange={handleChange}
                placeholder="Keterangan singkat barang..."
                className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow"
              />
            </div>
          </div>

          {/* Active Status Checkbox */}
          <div className="pt-2">
            <label className="inline-flex items-center gap-2.5 cursor-pointer select-none">
              <input
                type="checkbox"
                name="is_active"
                checked={formData.is_active}
                onChange={handleChange}
                className="w-4 h-4 rounded text-[#27C5E5] focus:ring-[#27C5E5] border-[#D7EBEF]"
              />
              <span className="text-xs font-semibold text-[#16323B]">
                Status Barang Aktif
              </span>
            </label>
          </div>

          {/* Action Buttons */}
          <div className="pt-5 mt-4 border-t border-[#D7EBEF] flex items-center justify-end gap-3">
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
              className="px-6 py-2.5 rounded-full bg-[#20282D] hover:bg-black text-white font-bold text-xs shadow-xs transition-all cursor-pointer disabled:opacity-50 flex items-center gap-2"
            >
              {isLoading && (
                <span className="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              )}
              {isEdit ? 'Simpan Perubahan' : 'Simpan Barang'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default ProductModal;
