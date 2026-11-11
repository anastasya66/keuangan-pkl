import { useEffect, useState, useRef } from 'react';
import categoryService from '../../services/categoryService';
import unitService from '../../services/unitService';
import productService from '../../services/productService';
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
    min_stock: '5',
    is_active: true,
  });

  const [imageFile, setImageFile] = useState(null);
  const [imagePreview, setImagePreview] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});
  const fileInputRef = useRef(null);

  useEffect(() => {
    if (!isOpen) return;

    let isMounted = true;
    async function loadDropdowns() {
      setIsLoadingDropdowns(true);
      try {
        const [catRes, unitRes] = await Promise.all([
          categoryService.getCategories({ all: true, is_active: true }),
          unitService.getUnits({ all: true, is_active: true }),
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
        min_stock: product.min_stock ?? '5',
        is_active: product.is_active ?? true,
      });
      setImagePreview(product.image_url || product.image || null);
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
        min_stock: '5',
        is_active: true,
      });
      setImagePreview(null);
    }
    setImageFile(null);
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

    // Jika kategori berubah dan SKU masih kosong, beri rekomendasi SKU
    if (name === 'category_id' && value && !formData.sku && !product) {
      handleAutoGenerateSku(value);
    }
  };

  const handleAutoGenerateSku = async (catId) => {
    try {
      const res = await productService.getNextSku(catId || formData.category_id);
      if (res.success && res.data?.sku) {
        setFormData((prev) => ({ ...prev, sku: res.data.sku }));
      }
    } catch {
      // Abaikan jika gagal
    }
  };

  const handleImageChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      if (!file.type.startsWith('image/')) {
        setFieldErrors((prev) => ({ ...prev, image: 'Berkas harus berupa gambar (JPG, PNG, WebP).' }));
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        setFieldErrors((prev) => ({ ...prev, image: 'Ukuran foto maksimal 5 MB.' }));
        return;
      }
      setImageFile(file);
      setImagePreview(URL.createObjectURL(file));
      setFieldErrors((prev) => ({ ...prev, image: null }));
    }
  };

  const handleRemoveImage = () => {
    setImageFile(null);
    setImagePreview(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errors = {};

    if (!formData.name.trim()) errors.name = 'Nama produk wajib diisi.';
    if (!formData.category_id) errors.category_id = 'Pilih kategori produk.';
    if (!formData.unit_id) errors.unit_id = 'Pilih satuan produk.';
    if (formData.purchase_price === '' || isNaN(formData.purchase_price) || Number(formData.purchase_price) < 0) {
      errors.purchase_price = 'Harga beli dasar (HPP) harus berupa angka tidak negatif.';
    }
    if (formData.selling_price === '' || isNaN(formData.selling_price) || Number(formData.selling_price) < 0) {
      errors.selling_price = 'Harga jual kasir harus berupa angka tidak negatif.';
    }

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    // Gunakan FormData agar pengunggahan file foto tersimpan ke storage/app/public/products
    const submitData = new FormData();
    submitData.append('name', formData.name.trim());
    if (formData.sku.trim()) submitData.append('sku', formData.sku.trim());
    if (formData.barcode?.trim()) submitData.append('barcode', formData.barcode.trim());
    submitData.append('category_id', formData.category_id);
    submitData.append('unit_id', formData.unit_id);
    submitData.append('purchase_price', Number(formData.purchase_price));
    submitData.append('selling_price', Number(formData.selling_price));
    submitData.append('min_stock', Number(formData.min_stock || 0));
    if (formData.description?.trim()) submitData.append('description', formData.description.trim());
    submitData.append('is_active', formData.is_active ? '1' : '0');

    if (imageFile) {
      submitData.append('image', imageFile);
    } else if (imagePreview === null && product?.image) {
      // User menghapus foto yang sudah ada
      submitData.append('image', '');
    }

    const result = await onSubmit(submitData);
    if (result && !result.success && result.errors) {
      setFieldErrors(result.errors);
    }
  };

  const isEdit = Boolean(product?.id);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#10262E]/40 backdrop-blur-xs overflow-y-auto animate-backdrop">
      <div className="bg-white border border-[#D7EBEF] rounded-2xl sm:rounded-3xl shadow-2xl max-w-2xl w-full p-5 sm:p-7 text-[#16323B] my-6 animate-modal-pop max-h-[92vh] overflow-y-auto">
        {/* Modal Header */}
        <div className="flex items-center justify-between pb-4 border-b border-[#D7EBEF]">
          <h3 className="text-base sm:text-lg font-bold text-[#10262E] flex items-center gap-2">
            <span className="p-2 rounded-xl bg-[#EAF8FA] text-[#27C5E5]">
              <IconBox className="w-5 h-5" />
            </span>
            <span>{isEdit ? 'Ubah Data Produk' : 'Tambah Produk Baru'}</span>
          </h3>
          <button
            type="button"
            onClick={onClose}
            className="w-8 h-8 rounded-xl bg-[#F3FBFC] hover:bg-[#EAF8FA] text-[#8EA6AF] hover:text-[#16323B] transition-colors flex items-center justify-center cursor-pointer"
          >
            ✕
          </button>
        </div>

        {/* Modal Form */}
        <form onSubmit={handleSubmit} className="mt-5 space-y-4">
          {/* Row 1: Nama Produk & Foto Preview */}
          <div className="flex flex-col sm:flex-row gap-4 items-start">
            {/* Foto Produk Upload Box */}
            <div className="w-full sm:w-36 flex flex-col items-center">
              <label className="block text-[11px] font-semibold text-[#16323B] mb-1.5 self-start">
                Foto Produk
              </label>
              <div className="relative w-32 h-32 rounded-2xl border-2 border-dashed border-[#D7EBEF] hover:border-[#27C5E5] bg-[#F8FDFF] flex flex-col items-center justify-center overflow-hidden transition group">
                {imagePreview ? (
                  <>
                    <img
                      src={imagePreview}
                      alt="Preview"
                      className="w-full h-full object-cover"
                    />
                    <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                      <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        className="p-1.5 bg-white text-[#16323B] rounded-lg text-xs font-bold shadow hover:bg-[#EAF8FA]"
                        title="Ganti Foto"
                      >
                        ✏️
                      </button>
                      <button
                        type="button"
                        onClick={handleRemoveImage}
                        className="p-1.5 bg-rose-500 text-white rounded-lg text-xs font-bold shadow hover:bg-rose-600"
                        title="Hapus Foto"
                      >
                        🗑️
                      </button>
                    </div>
                  </>
                ) : (
                  <button
                    type="button"
                    onClick={() => fileInputRef.current?.click()}
                    className="w-full h-full flex flex-col items-center justify-center p-2 text-center text-[#577782] hover:text-[#167A93] cursor-pointer"
                  >
                    <span className="text-2xl mb-1">📷</span>
                    <span className="text-[10px] font-semibold leading-tight">Unggah Foto</span>
                    <span className="text-[9px] text-[#8EA6AF] mt-0.5">Maks 5MB</span>
                  </button>
                )}
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/jpeg,image/png,image/webp,image/jpg"
                  onChange={handleImageChange}
                  className="hidden"
                />
              </div>
              {fieldErrors.image && (
                <p className="mt-1 text-[10px] text-rose-500 font-medium text-center">
                  {fieldErrors.image}
                </p>
              )}
            </div>

            {/* Nama Produk & Deskripsi */}
            <div className="flex-1 space-y-3 w-full">
              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                  Nama Produk <span className="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  name="name"
                  required
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Contoh: Indomie Goreng Spesial 85g, Teh Botol 450ml"
                  className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all ${
                    fieldErrors.name ? 'border-rose-300 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#38BFE0]'
                  }`}
                />
                {fieldErrors.name && (
                  <p className="mt-1 text-[11px] text-rose-500 font-medium">
                    {Array.isArray(fieldErrors.name) ? fieldErrors.name[0] : fieldErrors.name}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                  Deskripsi / Keterangan (Opsional)
                </label>
                <input
                  type="text"
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  placeholder="Keterangan rasa, varian, atau spesifikasi barang..."
                  className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all"
                />
              </div>
            </div>
          </div>

          {/* Row 2: Kategori & Satuan Ukuran */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Kategori Produk <span className="text-rose-500">*</span>
              </label>
              <select
                name="category_id"
                required
                value={formData.category_id}
                onChange={handleChange}
                disabled={isLoadingDropdowns}
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all ${
                  fieldErrors.category_id ? 'border-rose-300 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#38BFE0]'
                }`}
              >
                <option value="">-- Pilih Kategori Produk --</option>
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
                Satuan Ukuran <span className="text-rose-500">*</span>
              </label>
              <select
                name="unit_id"
                required
                value={formData.unit_id}
                onChange={handleChange}
                disabled={isLoadingDropdowns}
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all ${
                  fieldErrors.unit_id ? 'border-rose-300 focus:border-rose-500' : 'border-[#D7EBEF] focus:border-[#38BFE0]'
                }`}
              >
                <option value="">-- Pilih Satuan Ukuran --</option>
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

          {/* Row 3: Kode SKU & Barcode */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <div className="flex items-center justify-between mb-1.5">
                <label className="text-xs font-semibold text-[#16323B]">
                  Kode SKU <span className="text-[#879197] font-normal">(Otomatis/Manual)</span>
                </label>
                <button
                  type="button"
                  onClick={() => handleAutoGenerateSku()}
                  className="text-[10px] font-semibold text-[#167A93] hover:underline"
                >
                  Generate SKU
                </button>
              </div>
              <input
                type="text"
                name="sku"
                value={formData.sku}
                onChange={handleChange}
                placeholder="Contoh: PRD-MNM-0001 (Otomatis jika kosong)"
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs font-mono uppercase focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all ${
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
                Barcode Scanner (Opsional)
              </label>
              <input
                type="text"
                name="barcode"
                value={formData.barcode}
                onChange={handleChange}
                placeholder="Scan barcode fisik produk..."
                className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all"
              />
            </div>
          </div>

          {/* Row 4: Harga Beli Dasar (HPP), Harga Jual Kasir, & Stok Minimal */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Harga Beli Dasar (HPP) <span className="text-rose-500">*</span>
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
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all ${
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
                Harga Jual Kasir (POS) <span className="text-rose-500">*</span>
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
                className={`w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border text-[#16323B] text-xs font-mono font-bold text-[#167A93] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all ${
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

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Stok Minimal (Peringatan) <span className="text-rose-500">*</span>
              </label>
              <input
                type="number"
                name="min_stock"
                min="0"
                step="any"
                required
                value={formData.min_stock}
                onChange={handleChange}
                placeholder="5"
                className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all"
              />
              <p className="mt-1 text-[10px] text-[#8EA6AF]">
                Batas peringatan stok menipis
              </p>
            </div>
          </div>

          {/* Status Aktif */}
          <div className="pt-1">
            <label className="inline-flex items-center gap-2.5 cursor-pointer select-none">
              <input
                type="checkbox"
                name="is_active"
                checked={formData.is_active}
                onChange={handleChange}
                className="w-4 h-4 rounded text-[#27C5E5] focus:ring-[#27C5E5] border-[#D7EBEF]"
              />
              <span className="text-xs font-semibold text-[#16323B]">
                Produk Aktif (Dapat dijual di Kasir POS dan dibeli dari Supplier)
              </span>
            </label>
          </div>

          {/* Modal Footer */}
          <div className="pt-4 border-t border-[#D7EBEF] flex items-center justify-end gap-3">
            <button
              type="button"
              disabled={isLoading}
              onClick={onClose}
              className="px-4 py-2.5 rounded-xl bg-white hover:bg-[#F3FBFC] border border-[#D7EBEF] text-xs font-semibold text-[#577782] transition-colors cursor-pointer disabled:opacity-50"
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
              {isEdit ? 'Simpan Perubahan' : 'Tambah Produk'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default ProductModal;
