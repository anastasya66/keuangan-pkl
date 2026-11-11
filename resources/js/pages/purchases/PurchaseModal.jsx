import React, { useState, useEffect } from 'react';
import supplierService from '../../services/supplierService';
import productService from '../../services/productService';
import purchaseService from '../../services/purchaseService';
import { formatRupiah } from '../../utils/currency';

export function PurchaseModal({ isOpen, onClose, onSubmit, isLoading = false }) {
  const [suppliers, setSuppliers] = useState([]);
  const [products, setProducts] = useState([]);

  const todayStr = new Date().toISOString().split('T')[0];

  const [formData, setFormData] = useState({
    supplier_id: '',
    invoice_number: '',
    purchase_date: todayStr,
    status: 'received', // 'received' (Diterima) atau 'draft' (Pending)
    payment_method: 'cash', // 'cash', 'transfer', 'tempo'
    discount_amount: 0,
    tax_amount: 0,
    notes: '',
  });

  const [items, setItems] = useState([
    { product_id: '', quantity: 1, purchase_price: 0, subtotal: 0 },
  ]);

  const [errors, setErrors] = useState({});
  const [isManualInvoice, setIsManualInvoice] = useState(false);

  // Load suppliers and products when modal opens
  useEffect(() => {
    if (isOpen) {
      async function loadOptions() {
        try {
          const [supRes, prodRes] = await Promise.all([
            supplierService.getActiveSuppliers(),
            productService.getProducts({ all: true, is_active: true }),
          ]);
          if (supRes.success && supRes.data) {
            setSuppliers(supRes.data);
            if (supRes.data.length > 0) {
              setFormData((prev) => ({
                ...prev,
                supplier_id: prev.supplier_id || String(supRes.data[0].id),
              }));
            }
          }
          if (prodRes.success && prodRes.data) {
            setProducts(prodRes.data);
          }
        } catch (err) {
          console.error('Gagal memuat data supplier/produk:', err);
        }
      }
      loadOptions();

      // Reset form
      setFormData({
        supplier_id: '',
        invoice_number: '',
        purchase_date: todayStr,
        status: 'received',
        payment_method: 'cash',
        discount_amount: 0,
        tax_amount: 0,
        notes: '',
      });
      setItems([{ product_id: '', quantity: 1, purchase_price: 0, subtotal: 0 }]);
      setErrors({});
      setIsManualInvoice(false);

      // Generate invoice number otomatis format PO-YYYYMMDD-XXXX
      fetchNextInvoice(todayStr);
    }
  }, [isOpen]);

  // Fetch next invoice number jika tanggal berubah dan user belum manual edit
  const fetchNextInvoice = async (dateStr) => {
    try {
      const res = await purchaseService.getNextInvoiceNumber(dateStr);
      if (res.success && res.data?.invoice_number) {
        setFormData((prev) => ({
          ...prev,
          invoice_number: res.data.invoice_number,
        }));
      }
    } catch {
      // Fallback lokal format PO-YYYYMMDD-0001
      const cleanDate = (dateStr || todayStr).replace(/-/g, '');
      setFormData((prev) => ({
        ...prev,
        invoice_number: prev.invoice_number || `PO-${cleanDate}-0001`,
      }));
    }
  };

  const handleDateChange = (newDate) => {
    setFormData((prev) => ({ ...prev, purchase_date: newDate }));
    if (!isManualInvoice) {
      fetchNextInvoice(newDate);
    }
  };

  if (!isOpen) return null;

  // Hitung subtotal dan grand total secara dinamis
  const subtotal = items.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0);
  const discount = Math.max(0, Number(formData.discount_amount) || 0);
  const tax = Math.max(0, Number(formData.tax_amount) || 0);
  const grandTotal = Math.max(0, subtotal - discount + tax);

  // Handler update baris produk
  const handleItemChange = (index, field, value) => {
    setItems((prev) => {
      const updated = [...prev];
      const item = { ...updated[index], [field]: value };

      if (field === 'product_id') {
        const prod = products.find((p) => String(p.id) === String(value));
        if (prod) {
          // Isi default harga beli satuan dari data katalog
          item.purchase_price = Number(prod.purchase_price_numeric ?? prod.purchase_price) || 0;
        }
      }

      const qty = Number(field === 'quantity' ? value : item.quantity) || 0;
      const price = Number(field === 'purchase_price' ? value : item.purchase_price) || 0;
      item.subtotal = Math.round(qty * price * 100) / 100;

      updated[index] = item;
      return updated;
    });
  };

  const handleAddItem = () => {
    setItems((prev) => [
      ...prev,
      { product_id: '', quantity: 1, purchase_price: 0, subtotal: 0 },
    ]);
  };

  const handleRemoveItem = (index) => {
    if (items.length <= 1) return;
    setItems((prev) => prev.filter((_, idx) => idx !== index));
  };

  // Ubah metode pembayaran
  const handlePaymentMethodChange = (newMethod) => {
    setFormData((prev) => ({
      ...prev,
      payment_method: newMethod,
    }));
  };

  // Validasi & Submit
  const handleSubmit = (e) => {
    e.preventDefault();
    const newErrors = {};

    if (!formData.supplier_id) newErrors.supplier_id = 'Pilih supplier terlebih dahulu';
    if (!formData.purchase_date) newErrors.purchase_date = 'Tanggal pembelian wajib diisi';

    const validItems = items.filter(
      (item) => item.product_id && Number(item.quantity) > 0
    );
    if (validItems.length === 0) {
      newErrors.items = 'Wajib memilih minimal 1 produk dari katalog dengan jumlah (Qty) > 0';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    const payload = {
      supplier_id: Number(formData.supplier_id),
      invoice_number: formData.invoice_number.trim() || undefined,
      purchase_date: formData.purchase_date,
      status: formData.status, // 'received' atau 'draft'
      payment_method: formData.payment_method,
      paid_amount: formData.payment_method === 'tempo' ? 0 : grandTotal,
      due_date: null,
      discount_amount: discount,
      tax_amount: tax,
      subtotal,
      grand_total: grandTotal,
      notes: formData.notes?.trim() || null,
      items: validItems.map((item) => ({
        product_id: Number(item.product_id),
        quantity: Number(item.quantity),
        purchase_price: Number(item.purchase_price),
      })),
    };

    onSubmit(payload);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#10262E]/50 backdrop-blur-xs animate-backdrop">
      <div className="bg-white rounded-2xl border border-[#D7EBEF] shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[94vh] animate-modal-pop">
        {/* Modal Header */}
        <div className="px-6 py-4 border-b border-[#EAF4F6] flex items-center justify-between bg-[#F8FDFE]">
          <div>
            <div className="flex items-center gap-2">
              <h3 className="text-base font-bold text-[#16323B]">
                Input Pembelian Stok Barang
              </h3>
              <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#EAF8FA] text-[#167A93] border border-[#D7EBEF]">
                Faktur Pembelian
              </span>
            </div>
            <p className="text-xs text-[#577782] mt-0.5">
              Input faktur pembelian barang masuk ke toko. Stok fisik, kartu stok, dan nilai persediaan otomatis tersinkronisasi.
            </p>
          </div>
          <button
            onClick={onClose}
            type="button"
            className="text-[#879197] hover:text-[#16323B] p-1.5 rounded-lg hover:bg-[#EAF8FA] transition cursor-pointer"
            title="Tutup Modal"
          >
            ✕
          </button>
        </div>

        {/* Modal Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-5 overflow-y-auto">
          {/* Section 1: Data Faktur */}
          <div className="bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl p-4 space-y-3.5">
            <div className="flex items-center justify-between border-b border-[#EAF4F6] pb-2">
              <h4 className="text-xs font-bold text-[#16323B] uppercase tracking-wider flex items-center gap-1.5">
                <span>📄</span>
                <span>Data Faktur Pembelian</span>
              </h4>
              <span className="text-[11px] text-[#879197]">
                Format Nomor: <span className="font-mono font-semibold text-[#167A93]">PO-YYYYMMDD-XXXX</span>
              </span>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
              {/* Nomor Invoice */}
              <div>
                <label className="block text-[11px] font-semibold text-[#16323B] mb-1">
                  No. Invoice Pembelian <span className="text-[#879197] font-normal">(Otomatis)</span>
                </label>
                <div className="relative">
                  <input
                    type="text"
                    value={formData.invoice_number}
                    onChange={(e) => {
                      setIsManualInvoice(true);
                      setFormData({ ...formData, invoice_number: e.target.value });
                    }}
                    placeholder="PO-YYYYMMDD-XXXX"
                    className="w-full px-3 py-2 bg-white border border-[#D7EBEF] rounded-xl text-xs font-mono font-bold text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
                  />
                  {!isManualInvoice && (
                    <span className="absolute right-2.5 top-2 text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                      Auto
                    </span>
                  )}
                </div>
              </div>

              {/* Pilih Supplier */}
              <div>
                <label className="block text-[11px] font-semibold text-[#16323B] mb-1">
                  Pilih Supplier <span className="text-rose-500">*</span>
                </label>
                <select
                  value={formData.supplier_id}
                  onChange={(e) => {
                    setFormData({ ...formData, supplier_id: e.target.value });
                    setErrors({ ...errors, supplier_id: null });
                  }}
                  className={`w-full px-3 py-2 bg-white border ${
                    errors.supplier_id ? 'border-rose-400' : 'border-[#D7EBEF]'
                  } rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]`}
                >
                  <option value="">-- Pilih Supplier --</option>
                  {suppliers.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.name} {s.code ? `(${s.code})` : ''}
                    </option>
                  ))}
                </select>
                {errors.supplier_id && (
                  <p className="text-[11px] text-rose-500 mt-1">{errors.supplier_id}</p>
                )}
              </div>

              {/* Tanggal Pembelian */}
              <div>
                <label className="block text-[11px] font-semibold text-[#16323B] mb-1">
                  Tanggal Pembelian <span className="text-rose-500">*</span>
                </label>
                <input
                  type="date"
                  value={formData.purchase_date}
                  onChange={(e) => handleDateChange(e.target.value)}
                  className="w-full px-3 py-2 bg-white border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
                />
              </div>

              {/* Status Barang (Diterima / Pending) */}
              <div>
                <label className="block text-[11px] font-semibold text-[#16323B] mb-1">
                  Status Barang <span className="text-rose-500">*</span>
                </label>
                <select
                  value={formData.status}
                  onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                  className="w-full px-3 py-2 bg-white border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
                >
                  <option value="received">Diterima (Stok Langsung Masuk)</option>
                  <option value="draft">Pending (Menunggu Barang Tiba)</option>
                </select>
                <p className="text-[10px] text-[#577782] mt-1">
                  {formData.status === 'received'
                    ? '✓ Stok, Kartu Stok, dan HPP Rata-rata langsung bertambah.'
                    : '⏱️ Barang belum tiba; stok tidak bertambah sampai dikonfirmasi.'}
                </p>
              </div>

              {/* Metode Pembayaran */}
              <div>
                <label className="block text-[11px] font-semibold text-[#16323B] mb-1">
                  Metode Pembayaran <span className="text-rose-500">*</span>
                </label>
                <select
                  value={formData.payment_method}
                  onChange={(e) => handlePaymentMethodChange(e.target.value)}
                  className="w-full px-3 py-2 bg-white border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
                >
                  <option value="cash">Cash (Tunai Kas Toko)</option>
                  <option value="transfer">Transfer (Rekening Bank)</option>
                  <option value="tempo">Tempo (Kredit / Utang Usaha)</option>
                </select>
              </div>

              {/* Catatan / Keterangan */}
              <div>
                <label className="block text-[11px] font-semibold text-[#16323B] mb-1">
                  Catatan / Keterangan
                </label>
                <input
                  type="text"
                  placeholder="No surat jalan / catatan tambahan..."
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  className="w-full px-3 py-2 bg-white border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
                />
              </div>
            </div>
          </div>

          {/* Section 2: Daftar Item Barang (Dinamis) */}
          <div className="space-y-3">
            <div className="flex items-center justify-between border-b border-[#EAF4F6] pb-2">
              <div>
                <span className="text-xs font-bold text-[#16323B] uppercase tracking-wider flex items-center gap-1.5">
                  <span>📦</span>
                  <span>Daftar Item Barang (Katalog Produk)</span>
                </span>
                <p className="text-[11px] text-[#577782] mt-0.5">
                  Pilih produk dari katalog, tentukan jumlah (qty) masuk, dan harga beli satuan.
                </p>
              </div>
              <button
                type="button"
                onClick={handleAddItem}
                className="px-3 py-1.5 rounded-lg text-xs font-bold text-[#167A93] bg-[#EAF8FA] hover:bg-[#D7EBEF] border border-[#D7EBEF] transition cursor-pointer flex items-center gap-1"
              >
                <span>+</span>
                <span>Tambah Baris Barang</span>
              </button>
            </div>

            {errors.items && (
              <div className="p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-600">
                {errors.items}
              </div>
            )}

            <div className="space-y-3">
              {items.map((item, idx) => {
                const prod = products.find((p) => String(p.id) === String(item.product_id));
                const currentStock = Number(prod?.stock || 0);
                const inputQty = Number(item.quantity) || 0;
                const projectedStock = currentStock + inputQty;
                const unitSymbol = prod?.unit?.symbol || 'Unit';

                return (
                  <div
                    key={idx}
                    className="p-3.5 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl space-y-2.5 transition hover:border-[#27C5E5]"
                  >
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                      {/* Dropdown Produk */}
                      <div className="md:col-span-5">
                        <label className="block text-[11px] font-semibold text-[#577782] mb-1">
                          Produk Toko <span className="text-rose-500">*</span>
                        </label>
                        <select
                          value={item.product_id}
                          onChange={(e) => handleItemChange(idx, 'product_id', e.target.value)}
                          className="w-full px-2.5 py-2 bg-white border border-[#D7EBEF] rounded-lg text-xs text-[#16323B] font-medium focus:outline-none focus:border-[#27C5E5]"
                        >
                          <option value="">-- Pilih Barang dari Katalog --</option>
                          {products.map((p) => (
                            <option key={p.id} value={p.id}>
                              {p.name} ({p.sku}) — Stok: {Number(p.stock || 0).toLocaleString('id-ID')} {p.unit?.symbol || 'Unit'}
                            </option>
                          ))}
                        </select>
                      </div>

                      {/* Jumlah Qty */}
                      <div className="md:col-span-2">
                        <label className="block text-[11px] font-semibold text-[#577782] mb-1">
                          Jumlah (Qty) <span className="text-rose-500">*</span>
                        </label>
                        <input
                          type="number"
                          min="0.01"
                          step="any"
                          placeholder="1"
                          value={item.quantity}
                          onChange={(e) => handleItemChange(idx, 'quantity', e.target.value)}
                          className="w-full px-2.5 py-2 bg-white border border-[#D7EBEF] rounded-lg text-xs text-right font-mono font-bold focus:outline-none focus:border-[#27C5E5]"
                        />
                      </div>

                      {/* Harga Beli Satuan */}
                      <div className="md:col-span-2">
                        <label className="block text-[11px] font-semibold text-[#577782] mb-1">
                          Harga Beli Satuan (Rp) <span className="text-rose-500">*</span>
                        </label>
                        <input
                          type="number"
                          min="0"
                          step="any"
                          placeholder="0"
                          value={item.purchase_price}
                          onChange={(e) => handleItemChange(idx, 'purchase_price', e.target.value)}
                          className="w-full px-2.5 py-2 bg-white border border-[#D7EBEF] rounded-lg text-xs text-right font-mono font-bold focus:outline-none focus:border-[#27C5E5]"
                        />
                      </div>

                      {/* Subtotal Item */}
                      <div className="md:col-span-2 text-right">
                        <label className="block text-[11px] font-semibold text-[#577782] mb-1">
                          Subtotal
                        </label>
                        <div className="text-xs font-black text-[#16323B] font-mono py-2 bg-white px-2 rounded-lg border border-[#E7ECEE]">
                          {formatRupiah(item.subtotal || 0)}
                        </div>
                      </div>

                      {/* Tombol Hapus Baris */}
                      <div className="md:col-span-1 text-center">
                        <button
                          type="button"
                          onClick={() => handleRemoveItem(idx)}
                          disabled={items.length <= 1}
                          className="w-full py-2 text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg text-xs font-semibold disabled:opacity-30 cursor-pointer transition"
                          title="Hapus baris barang ini"
                        >
                          ✕
                        </button>
                      </div>
                    </div>

                    {/* Badge Pengaruh Stok & HPP Terkini */}
                    {prod && (
                      <div className="px-3 py-1.5 bg-[#EAF8FA] border border-[#D7EBEF] rounded-lg text-[11px] flex flex-wrap items-center justify-between text-[#577782]">
                        <div className="flex items-center gap-1.5 flex-wrap">
                          <span>📦 Stok Saat Ini:</span>
                          <strong className="text-[#16323B] font-mono">
                            {currentStock.toLocaleString('id-ID')} {unitSymbol}
                          </strong>
                          <span className="text-[#8EA6AF]">&rarr;</span>
                          <span>Setelah Masuk:</span>
                          <strong className="text-emerald-700 font-mono">
                            {projectedStock.toLocaleString('id-ID')} {unitSymbol}
                          </strong>
                        </div>
                        <div className="text-[10px] text-[#167A93]">
                          Harga Modal Terakhir: <span className="font-mono font-semibold">{formatRupiah(prod.purchase_price || 0)}</span>
                        </div>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>

          {/* Section 3: Ringkasan Biaya, Diskon & Pajak */}
          <div className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl p-4 flex flex-col md:flex-row justify-between items-end gap-4">
            <div className="w-full md:w-1/2 space-y-2">
              <div className="flex items-center gap-3">
                <label className="text-xs font-semibold text-[#16323B] w-28">Diskon Faktur (Rp):</label>
                <input
                  type="number"
                  min="0"
                  value={formData.discount_amount}
                  onChange={(e) => setFormData({ ...formData, discount_amount: e.target.value })}
                  className="px-2.5 py-1.5 bg-white border border-[#D7EBEF] rounded-lg text-xs font-mono font-bold text-right w-40"
                  placeholder="0"
                />
              </div>
              <div className="flex items-center gap-3">
                <label className="text-xs font-semibold text-[#16323B] w-28">Pajak / PPN (Rp):</label>
                <input
                  type="number"
                  min="0"
                  value={formData.tax_amount}
                  onChange={(e) => setFormData({ ...formData, tax_amount: e.target.value })}
                  className="px-2.5 py-1.5 bg-white border border-[#D7EBEF] rounded-lg text-xs font-mono font-bold text-right w-40"
                  placeholder="0"
                />
              </div>
            </div>

            <div className="w-full md:w-1/2 text-right space-y-1">
              <div className="text-xs text-[#577782]">
                Subtotal Barang: <span className="font-mono font-semibold text-[#16323B]">{formatRupiah(subtotal)}</span>
              </div>
              <div className="text-lg font-black text-[#167A93]">
                Total Belanja: <span className="font-mono">{formatRupiah(grandTotal)}</span>
              </div>
            </div>
          </div>

          {/* Modal Footer */}
          <div className="pt-3 border-t border-[#EAF4F6] flex flex-col sm:flex-row items-center justify-between gap-3">
            <div className="text-xs text-[#577782] flex items-center gap-1.5">
              <span>ℹ️</span>
              <span>
                {formData.status === 'received'
                  ? 'Stok masuk, kartu stok & nilai persediaan otomatis terintegrasi.'
                  : 'Faktur tersimpan sebagai draft pending menunggu konfirmasi terima.'}
              </span>
            </div>

            <div className="flex items-center gap-2.5 w-full sm:w-auto justify-end">
              <button
                type="button"
                onClick={onClose}
                disabled={isLoading}
                className="px-4 py-2 rounded-xl text-xs font-semibold text-[#577782] hover:bg-[#F0F9FB] transition cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                disabled={isLoading}
                className="px-5 py-2.5 rounded-full text-xs font-bold bg-[#20282D] hover:bg-black text-white shadow-xs transition disabled:opacity-50 cursor-pointer flex items-center gap-2"
              >
                {isLoading ? (
                  <span>Menyimpan...</span>
                ) : (
                  <>
                    <span>✓</span>
                    <span>
                      {formData.status === 'received'
                        ? 'Simpan & Tambah ke Stok'
                        : 'Simpan Draft Pembelian'}
                    </span>
                  </>
                )}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}

export default PurchaseModal;
