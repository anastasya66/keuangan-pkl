import React, { useState, useEffect } from 'react';
import { X, Save, AlertCircle, Calculator, TrendingUp, Handshake, CheckCircle2, Package } from 'lucide-react';
import { formatRupiah } from '../../utils/currency';

export default function ConsignmentProductModal({
  isOpen,
  onClose,
  onSubmit,
  initialData = null,
  consignors = [],
  categories = [],
  units = [],
  isSubmitting = false,
}) {
  const [formData, setFormData] = useState({
    consignor_id: '',
    name: '',
    sku: '',
    category_id: '',
    unit_id: '',
    barcode: '',
    consignment_cost_price: 0,
    selling_price: 0,
    commission_rate: 0,
    commission_amount: 0,
    stock: 0,
    minimum_stock: 5,
    description: '',
    is_active: true,
  });

  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (initialData) {
      const cost = Number(initialData.consignment_cost_price) || 0;
      const sell = Number(initialData.selling_price) || 0;
      const commAmount = Number(initialData.commission_amount) || Math.max(0, sell - cost);
      const commRate = Number(initialData.commission_rate) || (sell > 0 ? (commAmount / sell) * 100 : 0);

      setFormData({
        consignor_id: initialData.consignor_id || '',
        name: initialData.name || '',
        sku: initialData.sku || '',
        category_id: initialData.category_id || '',
        unit_id: initialData.unit_id || '',
        barcode: initialData.barcode || '',
        consignment_cost_price: cost,
        selling_price: sell,
        commission_rate: Math.round(commRate * 100) / 100,
        commission_amount: commAmount,
        stock: Number(initialData.stock) || 0,
        minimum_stock: Number(initialData.minimum_stock) || 5,
        description: initialData.description || '',
        is_active: initialData.is_active ?? true,
      });
    } else {
      setFormData({
        consignor_id: consignors.length > 0 ? consignors[0].id : '',
        name: '',
        sku: '',
        category_id: categories.length > 0 ? categories[0].id : '',
        unit_id: units.length > 0 ? units[0].id : '',
        barcode: '',
        consignment_cost_price: 0,
        selling_price: 0,
        commission_rate: 0,
        commission_amount: 0,
        stock: 1,
        minimum_stock: 5,
        description: '',
        is_active: true,
      });
    }
    setErrors({});
  }, [initialData, isOpen, consignors, categories, units]);

  if (!isOpen) return null;

  // Handle perubahan harga dan perhitungan komisi otomatis
  const handlePriceChange = (field, value) => {
    const num = Math.max(0, Number(value) || 0);
    const updated = { ...formData, [field]: num };

    const cost = field === 'consignment_cost_price' ? num : Number(updated.consignment_cost_price) || 0;
    const sell = field === 'selling_price' ? num : Number(updated.selling_price) || 0;

    const commAmt = Math.max(0, sell - cost);
    const commPct = sell > 0 ? (commAmt / sell) * 100 : 0;

    updated.commission_amount = commAmt;
    updated.commission_rate = Math.round(commPct * 100) / 100;

    setFormData(updated);
    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: null }));
    }
  };

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
    if (!formData.consignor_id) errs.consignor_id = 'Pilih mitra konsinyasi pemilik barang';
    if (!formData.name.trim()) errs.name = 'Nama barang konsinyasi wajib diisi';
    if (Number(formData.selling_price) <= 0) errs.selling_price = 'Harga jual kasir harus lebih dari 0';
    if (Number(formData.consignment_cost_price) > Number(formData.selling_price)) {
      errs.consignment_cost_price = 'Harga mitra (setor) tidak boleh melebihi harga jual kasir';
    }

    if (Object.keys(errs).length > 0) {
      setErrors(errs);
      return;
    }

    onSubmit(formData);
  };

  const costPrice = Number(formData.consignment_cost_price) || 0;
  const sellPrice = Number(formData.selling_price) || 0;
  const marginToko = Math.max(0, sellPrice - costPrice);
  const marginPercent = sellPrice > 0 ? Math.round((marginToko / sellPrice) * 1000) / 10 : 0;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-fade-in">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-xl border border-[#E7ECEE] overflow-hidden flex flex-col max-h-[92vh]">
        {/* Modal Header */}
        <div className="px-6 py-4 border-b border-[#E7ECEE] flex items-center justify-between bg-[#F8FAFA]">
          <div>
            <div className="flex items-center gap-2">
              <div className="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                <Package className="w-4 h-4" />
              </div>
              <h3 className="text-base font-bold text-[#20282D]">
                {initialData ? 'Edit Barang Konsinyasi' : 'Tambah Barang Konsinyasi (Titip Jual)'}
              </h3>
              <span className="px-2 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 rounded-md">
                Katalog &amp; Kasir POS
              </span>
            </div>
            <p className="text-xs text-[#879197] mt-0.5">
              Barang langsung tersinkronisasi otomatis ke Katalog &amp; Kasir POS untuk siap dijual.
            </p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1.5 rounded-lg text-[#879197] hover:text-[#20282D] hover:bg-[#EAEFF1] transition cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Modal Form */}
        <form onSubmit={handleSubmit} className="p-6 overflow-y-auto space-y-4 flex-1">
          {errors.general && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-600 flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{errors.general}</span>
            </div>
          )}

          {/* 1. Pilih Mitra Konsinyasi */}
          <div>
            <label className="block text-xs font-bold text-[#20282D] mb-1">
              Pilih Mitra Konsinyasi (Penitip Barang) <span className="text-rose-500">*</span>
            </label>
            <div className="relative">
              <select
                name="consignor_id"
                value={formData.consignor_id}
                onChange={handleChange}
                className={`w-full px-3 py-2.5 text-xs border rounded-xl bg-white focus:outline-none ${
                  errors.consignor_id ? 'border-rose-500' : 'border-[#E7ECEE] focus:border-[#55BFD8]'
                }`}
              >
                <option value="">-- Pilih Mitra Konsinyasi --</option>
                {consignors.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.code} — {c.name} {c.phone ? `(${c.phone})` : ''}
                  </option>
                ))}
              </select>
            </div>
            {errors.consignor_id ? (
              <p className="text-[11px] text-rose-500 mt-1">{errors.consignor_id}</p>
            ) : (
              <p className="text-[10px] text-[#879197] mt-1">
                Pilih pihak rekanan/mitra penitip yang menyuplai produk ini.
              </p>
            )}
          </div>

          {/* 2. Nama Barang & Kode/SKU Barang */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div className="md:col-span-2">
              <label className="block text-xs font-bold text-[#20282D] mb-1">
                Nama Barang <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                name="name"
                value={formData.name}
                onChange={handleChange}
                placeholder="Ketik nama barang titipan (cth: Keripik Pisang Keju Bu Ani)"
                className={`w-full px-3 py-2 text-xs border rounded-xl focus:outline-none ${
                  errors.name ? 'border-rose-500' : 'border-[#E7ECEE] focus:border-[#55BFD8]'
                }`}
              />
              {errors.name && <p className="text-[11px] text-rose-500 mt-1">{errors.name}</p>}
            </div>

            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">
                Kode / SKU Barang <span className="text-[#879197] font-normal">(Opsional)</span>
              </label>
              <input
                type="text"
                name="sku"
                value={formData.sku}
                onChange={handleChange}
                placeholder="Otomatis (KNS-XXXX)"
                className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl font-mono focus:outline-none focus:border-[#55BFD8]"
              />
            </div>
          </div>

          {/* 3. Jumlah Stok Titipan Awal & Satuan */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">
                {initialData ? 'Sisa Stok Fisik' : 'Jumlah Stok Titipan Awal'} <span className="text-rose-500">*</span>
              </label>
              <input
                type="number"
                min="0"
                step="1"
                name="stock"
                disabled={Boolean(initialData)}
                value={formData.stock}
                onChange={handleChange}
                className={`w-full px-3 py-2 text-xs font-mono font-bold border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8] ${
                  initialData ? 'bg-gray-100 cursor-not-allowed text-[#879197]' : 'bg-white text-[#20282D]'
                }`}
              />
              <p className="text-[10px] text-[#879197] mt-1">
                {initialData
                  ? 'Stok dapat disesuaikan lewat tombol Penyesuaian Stok di tabel.'
                  : 'Jumlah fisik unit barang yang diterima awal dari mitra penitip.'}
              </p>
            </div>

            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">Satuan Ukuran</label>
              <select
                name="unit_id"
                value={formData.unit_id}
                onChange={handleChange}
                className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl bg-white focus:outline-none focus:border-[#55BFD8]"
              >
                <option value="">-- Pilih Satuan (Default Unit) --</option>
                {units.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name} ({u.symbol})
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* 4. Skema Harga & Indikator Otomatis Margin / Komisi Toko */}
          <div className="p-4 bg-gradient-to-br from-[#F8FAFA] to-[#EFF7FA] rounded-2xl border border-[#D5E3E7] space-y-3.5">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2 text-[#167A93]">
                <Calculator className="w-4 h-4" />
                <span className="text-xs font-bold uppercase tracking-wider">
                  Skema Harga &amp; Indikator Margin Bagi Hasil
                </span>
              </div>
              <span className="text-[10px] font-bold text-[#059669] bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                Otomatis Dihitung
              </span>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Harga Awal / Harga Mitra */}
              <div>
                <label className="block text-xs font-bold text-[#20282D] mb-1">
                  Harga Awal / Harga Mitra <span className="text-rose-500">*</span>
                </label>
                <div className="relative">
                  <span className="absolute left-3 top-2.5 text-xs font-bold text-[#879197]">Rp</span>
                  <input
                    type="number"
                    min="0"
                    step="100"
                    name="consignment_cost_price"
                    value={formData.consignment_cost_price}
                    onChange={(e) => handlePriceChange('consignment_cost_price', e.target.value)}
                    placeholder="0"
                    className="w-full pl-9 pr-3 py-2 text-xs font-mono font-bold border border-[#E7ECEE] rounded-xl bg-white focus:outline-none focus:border-[#55BFD8]"
                  />
                </div>
                <p className="text-[10px] text-[#879197] mt-1">
                  Harga setor wajib ke mitra penitip saat barang terjual.
                </p>
                {errors.consignment_cost_price && (
                  <p className="text-[11px] text-rose-500 mt-1">{errors.consignment_cost_price}</p>
                )}
              </div>

              {/* Harga Jual */}
              <div>
                <label className="block text-xs font-bold text-[#20282D] mb-1">
                  Harga Jual <span className="text-rose-500">*</span>
                </label>
                <div className="relative">
                  <span className="absolute left-3 top-2.5 text-xs font-bold text-[#879197]">Rp</span>
                  <input
                    type="number"
                    min="0"
                    step="100"
                    name="selling_price"
                    value={formData.selling_price}
                    onChange={(e) => handlePriceChange('selling_price', e.target.value)}
                    placeholder="0"
                    className="w-full pl-9 pr-3 py-2 text-xs font-mono font-bold border border-[#E7ECEE] rounded-xl bg-white focus:outline-none focus:border-[#55BFD8]"
                  />
                </div>
                <p className="text-[10px] text-[#879197] mt-1">
                  Harga yang tampil di kasir POS untuk dibayar pembeli.
                </p>
                {errors.selling_price && (
                  <p className="text-[11px] text-rose-500 mt-1">{errors.selling_price}</p>
                )}
              </div>
            </div>

            {/* Indikator Otomatis Margin / Komisi Toko */}
            <div className="p-3.5 bg-white rounded-xl border border-[#D5E3E7] shadow-2xs">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-2.5">
                  <div className="w-9 h-9 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center font-bold">
                    <TrendingUp className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-[#879197] block">
                      Margin / Komisi Toko (Harga Jual - Harga Awal)
                    </span>
                    <div className="text-base font-black text-[#059669] font-mono leading-tight">
                      +{formatRupiah(marginToko)}{' '}
                      <span className="text-xs font-semibold text-[#879197]">/ unit</span>
                    </div>
                  </div>
                </div>

                <div className="text-right">
                  <span className="text-[10px] font-bold uppercase tracking-wider text-[#879197] block">
                    Persentase Margin
                  </span>
                  <span className="inline-block px-2.5 py-0.5 bg-[#E5F6FA] text-[#167A93] font-mono font-black text-sm rounded-lg border border-[#CFEFF6]">
                    {marginPercent}%
                  </span>
                </div>
              </div>

              {/* Rincian Transparan */}
              <div className="mt-2.5 pt-2 border-t border-[#F1F4F5] flex items-center justify-between text-[11px] text-[#879197]">
                <span>
                  Setoran ke Mitra: <strong className="text-[#20282D]">{formatRupiah(costPrice)}</strong>
                </span>
                <span className="text-emerald-700 font-semibold">
                  Keuntungan Toko: <strong>{formatRupiah(marginToko)}</strong>
                </span>
              </div>
            </div>
          </div>

          {/* 5. Detail Pelengkap (Kategori, Barcode, Min Stock) */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">Kategori Produk</label>
              <select
                name="category_id"
                value={formData.category_id}
                onChange={handleChange}
                className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl bg-white focus:outline-none focus:border-[#55BFD8]"
              >
                <option value="">-- Pilih Kategori --</option>
                {categories.map((cat) => (
                  <option key={cat.id} value={cat.id}>
                    {cat.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">Barcode (Opsional)</label>
              <input
                type="text"
                name="barcode"
                value={formData.barcode}
                onChange={handleChange}
                placeholder="Scan / Barcode"
                className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl font-mono focus:outline-none focus:border-[#55BFD8]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#20282D] mb-1">Min. Stok Pengingat</label>
              <input
                type="number"
                min="0"
                step="1"
                name="minimum_stock"
                value={formData.minimum_stock}
                onChange={handleChange}
                className="w-full px-3 py-2 text-xs font-mono border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8]"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-[#20282D] mb-1">Catatan / Deskripsi Tambahan</label>
            <input
              type="text"
              name="description"
              value={formData.description}
              onChange={handleChange}
              placeholder="Varian rasa, tanggal kedaluwarsa, atau catatan penitipan..."
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
              className="px-5 py-2.5 text-xs font-bold text-white bg-[#20282D] hover:bg-[#167A93] rounded-xl transition flex items-center gap-2 shadow-xs cursor-pointer disabled:opacity-50"
            >
              <Save className="w-4 h-4" />
              <span>{isSubmitting ? 'Menyimpan...' : 'Simpan ke Katalog & Kasir'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
