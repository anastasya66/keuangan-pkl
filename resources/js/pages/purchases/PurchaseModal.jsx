import React, { useState, useEffect } from 'react';
import supplierService from '../../services/supplierService';
import productService from '../../services/productService';
import { formatRupiah } from '../../utils/currency';

export function PurchaseModal({ isOpen, onClose, onSubmit, isLoading = false }) {
  const [suppliers, setSuppliers] = useState([]);
  const [products, setProducts] = useState([]);

  const [formData, setFormData] = useState({
    supplier_id: '',
    invoice_number: '',
    purchase_date: new Date().toISOString().split('T')[0],
    status: 'received', // draft atau received
    payment_method: 'cash', // cash, transfer, tempo
    paid_amount: 0,
    due_date: '',
    discount_amount: 0,
    tax_amount: 0,
    notes: '',
  });

  const [items, setItems] = useState([
    { product_id: '', quantity: 1, purchase_price: 0, subtotal: 0 },
  ]);

  const [errors, setErrors] = useState({});

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
          console.error('Gagal memuat supplier/produk:', err);
        }
      }
      loadOptions();

      // Reset form
      setFormData({
        supplier_id: '',
        invoice_number: '',
        purchase_date: new Date().toISOString().split('T')[0],
        status: 'received',
        payment_method: 'cash',
        paid_amount: 0,
        due_date: '',
        discount_amount: 0,
        tax_amount: 0,
        notes: '',
      });
      setItems([{ product_id: '', quantity: 1, purchase_price: 0, subtotal: 0 }]);
      setErrors({});
    }
  }, [isOpen]);

  if (!isOpen) return null;

  // Hitung subtotal dan grand total
  const subtotal = items.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0);
  const discount = Number(formData.discount_amount) || 0;
  const tax = Number(formData.tax_amount) || 0;
  const grandTotal = Math.max(0, subtotal - discount + tax);

  const handleItemChange = (index, field, value) => {
    setItems((prev) => {
      const updated = [...prev];
      const item = { ...updated[index], [field]: value };

      if (field === 'product_id') {
        const prod = products.find((p) => String(p.id) === String(value));
        if (prod) {
          item.purchase_price = Number(prod.purchase_price_numeric ?? prod.purchase_price) || 0;
        }
      }

      const qty = Number(field === 'quantity' ? value : item.quantity) || 0;
      const price = Number(field === 'purchase_price' ? value : item.purchase_price) || 0;
      item.subtotal = qty * price;

      updated[index] = item;
      return updated;
    });
  };

  const handleAddItem = () => {
    setItems((prev) => [...prev, { product_id: '', quantity: 1, purchase_price: 0, subtotal: 0 }]);
  };

  const handleRemoveItem = (index) => {
    if (items.length <= 1) return;
    setItems((prev) => prev.filter((_, idx) => idx !== index));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const newErrors = {};

    if (!formData.supplier_id) newErrors.supplier_id = 'Pilih supplier';
    if (!formData.purchase_date) newErrors.purchase_date = 'Tanggal wajib diisi';

    const validItems = items.filter((item) => item.product_id && item.quantity > 0);
    if (validItems.length === 0) {
      newErrors.items = 'Tambahkan minimal 1 item produk dengan jumlah > 0';
    }

    if (formData.payment_method === 'tempo' && !formData.due_date) {
      newErrors.due_date = 'Tanggal jatuh tempo wajib diisi untuk pembelian tempo';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    const payload = {
      ...formData,
      subtotal,
      discount_amount: discount,
      tax_amount: tax,
      grand_total: grandTotal,
      paid_amount:
        formData.payment_method === 'tempo' ? Number(formData.paid_amount) || 0 : grandTotal,
      items: validItems.map((item) => ({
        product_id: Number(item.product_id),
        quantity: Number(item.quantity),
        purchase_price: Number(item.purchase_price),
      })),
    };

    onSubmit(payload);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/40 backdrop-blur-xs animate-backdrop">
      <div className="bg-white rounded-2xl border border-[#D7EBEF] shadow-xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[92vh]">
        {/* Modal Header */}
        <div className="px-6 py-4 border-b border-[#EAF4F6] flex items-center justify-between bg-[#F8FDFE]">
          <div>
            <h3 className="text-base font-bold text-[#16323B]">
              Input Pembelian Barang (Stok Toko)
            </h3>
            <p className="text-xs text-[#577782]">
              Pilih produk yang ingin dibeli, masukkan jumlah (qty), dan harga beli/satuan untuk stok toko
            </p>
          </div>
          <button
            onClick={onClose}
            type="button"
            className="text-[#577782] hover:text-[#16323B] p-1.5 rounded-lg hover:bg-[#EAF8FA] transition cursor-pointer"
          >
            ✕
          </button>
        </div>

        {/* Modal Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-5 overflow-y-auto">
          {/* Main Attributes */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Pilih Supplier <span className="text-rose-500">*</span>
              </label>
              <select
                value={formData.supplier_id}
                onChange={(e) => {
                  setFormData({ ...formData, supplier_id: e.target.value });
                  setErrors({ ...errors, supplier_id: null });
                }}
                className={`w-full px-3 py-2 bg-[#F8FDFE] border ${errors.supplier_id ? 'border-rose-400' : 'border-[#D7EBEF]'
                  } rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]`}
              >
                <option value="">-- Pilih Supplier --</option>
                {suppliers.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.name} ({s.code})
                  </option>
                ))}
              </select>
              {errors.supplier_id && <p className="text-xs text-rose-500 mt-1">{errors.supplier_id}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Nomor Invoice (Otomatis jika kosong)
              </label>
              <input
                type="text"
                placeholder="PO-YYYYMMDD-0001"
                value={formData.invoice_number}
                onChange={(e) => setFormData({ ...formData, invoice_number: e.target.value })}
                className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Tanggal Pembelian <span className="text-rose-500">*</span>
              </label>
              <input
                type="date"
                value={formData.purchase_date}
                onChange={(e) => setFormData({ ...formData, purchase_date: e.target.value })}
                className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Status Penerimaan
              </label>
              <select
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
              >
                <option value="received">Diterima (Stok Langsung Masuk)</option>
                <option value="draft">Draft (Pesanan Belum Tiba)</option>
              </select>
              <p className="text-[10px] text-[#577782] mt-0.5">
                {formData.status === 'received'
                  ? 'Stok fisik & HPP rata-rata akan langsung diperbarui'
                  : 'Stok tidak akan bertambah sebelum dikonfirmasi tiba'}
              </p>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Metode Pembayaran
              </label>
              <select
                value={formData.payment_method}
                onChange={(e) => setFormData({ ...formData, payment_method: e.target.value })}
                className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
              >
                <option value="cash">Tunai (Kas Toko)</option>
                <option value="transfer">Transfer Bank (Rekening Toko)</option>
                <option value="tempo">Tempo / Kredit (Utang Usaha)</option>
              </select>
            </div>

            {formData.payment_method === 'tempo' ? (
              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">
                  Jatuh Tempo <span className="text-rose-500">*</span>
                </label>
                <input
                  type="date"
                  value={formData.due_date}
                  onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
                  className={`w-full px-3 py-2 bg-[#F8FDFE] border ${errors.due_date ? 'border-rose-400' : 'border-[#D7EBEF]'
                    } rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]`}
                />
                {errors.due_date && <p className="text-xs text-rose-500 mt-0.5">{errors.due_date}</p>}
              </div>
            ) : (
              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">
                  Catatan / Nomor Referensi
                </label>
                <input
                  type="text"
                  placeholder="Keterangan pengiriman / faktur..."
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
                />
              </div>
            )}
          </div>

          {formData.payment_method === 'tempo' && (
            <div className="p-3 bg-[#FEF8EC] border border-[#FAD7A0] rounded-xl text-xs flex items-center justify-between">
              <div>
                <span className="font-semibold text-[#B7791F]">Uang Muka / Pembayaran Awal (DP):</span>
                <p className="text-[11px] text-[#744210]">Jika belum ada DP, biarkan 0.</p>
              </div>
              <div className="w-48">
                <input
                  type="number"
                  min="0"
                  value={formData.paid_amount}
                  onChange={(e) => setFormData({ ...formData, paid_amount: e.target.value })}
                  placeholder="0"
                  className="w-full px-3 py-1.5 bg-white border border-[#FAD7A0] rounded-lg text-xs font-mono font-bold text-[#16323B] text-right"
                />
              </div>
            </div>
          )}

          {/* Items Section */}
          <div className="space-y-3">
            <div className="flex items-center justify-between border-b border-[#EAF4F6] pb-2">
              <span className="text-xs font-bold text-[#16323B] uppercase tracking-wider">
                Daftar Barang Pembelian
              </span>
              <button
                type="button"
                onClick={handleAddItem}
                className="text-xs font-semibold text-[#167A93] hover:text-[#27C5E5] flex items-center gap-1"
              >
                + Tambah Baris Produk
              </button>
            </div>

            {errors.items && <p className="text-xs text-rose-500">{errors.items}</p>}

            <div className="space-y-3">
              {items.map((item, idx) => {
                const prod = products.find((p) => String(p.id) === String(item.product_id));
                const currentStock = Number(prod?.stock || 0);
                const inputQty = Number(item.quantity) || 0;
                const projectedStock = currentStock + inputQty;
                const unitSymbol = prod?.unit?.symbol || 'pcs';

                return (
                  <div
                    key={idx}
                    className="p-3.5 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl space-y-2.5 transition hover:border-[#27C5E5]/50"
                  >
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                      <div className="md:col-span-5">
                        <label className="block text-[11px] font-semibold text-[#577782] mb-1">
                          Produk (Barang Toko) <span className="text-rose-500">*</span>
                        </label>
                        <select
                          value={item.product_id}
                          onChange={(e) => handleItemChange(idx, 'product_id', e.target.value)}
                          className="w-full px-2.5 py-1.5 bg-white border border-[#D7EBEF] rounded-lg text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
                        >
                          <option value="">-- Pilih Barang yang Dibeli --</option>
                          {products.map((p) => (
                            <option key={p.id} value={p.id}>
                              {p.name} ({p.sku}) — Stok: {Number(p.stock || 0).toLocaleString('id-ID')} {p.unit?.symbol || 'pcs'}
                            </option>
                          ))}
                        </select>
                      </div>

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
                          className="w-full px-2 py-1.5 bg-white border border-[#D7EBEF] rounded-lg text-xs text-right font-mono focus:outline-none focus:border-[#27C5E5]"
                        />
                      </div>

                      <div className="md:col-span-2">
                        <label className="block text-[11px] font-semibold text-[#577782] mb-1">
                          Harga Beli/Satuan <span className="text-rose-500">*</span>
                        </label>
                        <input
                          type="number"
                          min="0"
                          step="any"
                          placeholder="0"
                          value={item.purchase_price}
                          onChange={(e) => handleItemChange(idx, 'purchase_price', e.target.value)}
                          className="w-full px-2 py-1.5 bg-white border border-[#D7EBEF] rounded-lg text-xs text-right font-mono focus:outline-none focus:border-[#27C5E5]"
                        />
                      </div>

                      <div className="md:col-span-2 text-right">
                        <label className="block text-[11px] font-semibold text-[#577782] mb-1">
                          Subtotal Pembelian
                        </label>
                        <div className="text-xs font-bold text-[#16323B] font-mono py-1.5">
                          {formatRupiah(item.subtotal || 0)}
                        </div>
                      </div>

                      <div className="md:col-span-1 text-center">
                        <label className="block text-[11px] text-transparent mb-1">Aksi</label>
                        <button
                          type="button"
                          onClick={() => handleRemoveItem(idx)}
                          disabled={items.length <= 1}
                          className="text-rose-400 hover:text-rose-600 p-1.5 rounded disabled:opacity-30 cursor-pointer"
                          title="Hapus baris"
                        >
                          🗑️
                        </button>
                      </div>
                    </div>

                    {/* Stock impact helper badge */}
                    {prod && (
                      <div className="px-3 py-1.5 bg-[#EAF8FA] border border-[#D7EBEF] rounded-lg text-[11px] flex flex-wrap items-center justify-between text-[#577782]">
                        <div className="flex items-center gap-1.5">
                          <span>📦 Stok Saat Ini:</span>
                          <strong className="text-[#16323B] font-mono">
                            {currentStock.toLocaleString('id-ID')} {unitSymbol}
                          </strong>
                          <span className="text-[#8EA6AF]">&rarr;</span>
                          <span>Setelah Pembelian Masuk:</span>
                          <strong className="text-emerald-700 font-mono">
                            {projectedStock.toLocaleString('id-ID')} {unitSymbol}
                          </strong>
                        </div>
                        <div className="text-[10px] text-[#167A93]">
                          Harga Terakhir: <span className="font-mono font-semibold">{formatRupiah(prod.purchase_price || 0)}</span>
                        </div>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>

          {/* Totals Section */}
          <div className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl p-4 flex flex-col md:flex-row justify-between items-end gap-4">
            <div className="w-full md:w-1/2 space-y-2">
              <div className="flex items-center gap-3">
                <label className="text-xs font-semibold text-[#16323B] w-24">Diskon (Rp):</label>
                <input
                  type="number"
                  min="0"
                  value={formData.discount_amount}
                  onChange={(e) => setFormData({ ...formData, discount_amount: e.target.value })}
                  className="px-2.5 py-1 bg-white border border-[#D7EBEF] rounded-lg text-xs font-mono text-right w-36"
                />
              </div>
              <div className="flex items-center gap-3">
                <label className="text-xs font-semibold text-[#16323B] w-24">Pajak (Rp):</label>
                <input
                  type="number"
                  min="0"
                  value={formData.tax_amount}
                  onChange={(e) => setFormData({ ...formData, tax_amount: e.target.value })}
                  className="px-2.5 py-1 bg-white border border-[#D7EBEF] rounded-lg text-xs font-mono text-right w-36"
                />
              </div>
            </div>

            <div className="w-full md:w-1/2 text-right space-y-1">
              <div className="text-xs text-[#577782]">
                Subtotal Item: <span className="font-mono font-semibold">{formatRupiah(subtotal)}</span>
              </div>
              <div className="text-lg font-bold text-[#167A93]">
                Total Akhir: <span className="font-mono">{formatRupiah(grandTotal)}</span>
              </div>
            </div>
          </div>

          {/* Modal Footer */}
          <div className="pt-3 border-t border-[#EAF4F6] flex items-center justify-end gap-3">
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
              className="px-6 py-2.5 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#167A93] text-white shadow-xs transition disabled:opacity-50 cursor-pointer"
            >
              {isLoading ? 'Menyimpan...' : 'Simpan & Tambahkan ke Stok Toko'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default PurchaseModal;

