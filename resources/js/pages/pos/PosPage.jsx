import React, { useState, useEffect, useRef, useCallback } from 'react';
import useAuth from '../../hooks/useAuth';
import posService from '../../services/posService';
import categoryService from '../../services/categoryService';
import { formatRupiah } from '../../utils/currency';
import { IconPos, IconPrinter } from '../../components/common/Icons';

export function PosPage() {
  const { user } = useAuth();

  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [barcodeInput, setBarcodeInput] = useState('');
  const [isLoadingProducts, setIsLoadingProducts] = useState(false);

  // Cart State
  const [cart, setCart] = useState([]);
  const [transactionDiscount, setTransactionDiscount] = useState(0);

  // Payment Modal State
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
  const [customerName, setCustomerName] = useState('Pelanggan Umum');
  const [paymentMethod, setPaymentMethod] = useState('cash'); // cash, transfer, qris
  const [paidAmount, setPaidAmount] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Receipt Modal State
  const [isReceiptModalOpen, setIsReceiptModalOpen] = useState(false);
  const [receiptData, setReceiptData] = useState(null);

  // Toast State
  const [toast, setToast] = useState(null);

  const barcodeInputRef = useRef(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 3500);
  };

  // Load Categories & Products on mount
  useEffect(() => {
    async function loadInitialData() {
      try {
        const catRes = await categoryService.getCategories({ all: true, is_active: true });
        if (catRes.success) {
          setCategories(catRes.data);
        }
      } catch (err) {
        console.error('Gagal memuat kategori:', err);
      }
    }
    loadInitialData();
  }, []);

  const loadProducts = useCallback(async (query = searchQuery) => {
    setIsLoadingProducts(true);
    try {
      const res = await posService.getPosProducts({ search: query, limit: 50 });
      if (res.success) {
        setProducts(res.data);
      }
    } catch (err) {
      console.error('Gagal memuat produk POS:', err);
    } finally {
      setIsLoadingProducts(false);
    }
  }, [searchQuery]);

  useEffect(() => {
    loadProducts(searchQuery);
  }, [searchQuery, loadProducts]);

  // Focus barcode input on mount and after modal close
  useEffect(() => {
    if (!isPaymentModalOpen && !isReceiptModalOpen && barcodeInputRef.current) {
      barcodeInputRef.current.focus();
    }
  }, [isPaymentModalOpen, isReceiptModalOpen]);

  // Filter products by selected category in UI
  const filteredProducts = selectedCategory
    ? products.filter((p) => String(p.category_id) === String(selectedCategory))
    : products;

  // Add product to cart
  const addToCart = (product) => {
    const availableStock = Number(product.stock) || 0;
    if (availableStock <= 0) {
      showToast(`Stok ${product.name} telah habis!`, 'error');
      return;
    }

    setCart((prev) => {
      const existingIndex = prev.findIndex((item) => item.product.id === product.id);
      if (existingIndex > -1) {
        const existing = prev[existingIndex];
        const newQty = existing.quantity + 1;
        if (newQty > availableStock) {
          showToast(`Stok hanya tersisa ${availableStock} ${product.unit?.symbol || 'pcs'}`, 'error');
          return prev;
        }
        const updated = [...prev];
        updated[existingIndex] = {
          ...existing,
          quantity: newQty,
          subtotal: newQty * existing.unit_price - existing.discount_amount,
        };
        return updated;
      } else {
        return [
          ...prev,
          {
            product,
            quantity: 1,
            unit_price: Number(product.selling_price) || 0,
            discount_amount: 0,
            subtotal: Number(product.selling_price) || 0,
          },
        ];
      }
    });
  };

  // Handle barcode scanner Enter key
  const handleBarcodeKeyDown = async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const code = barcodeInput.trim();
      if (!code) return;

      try {
        const res = await posService.findByBarcode(code);
        if (res.success && res.data) {
          addToCart(res.data);
          setBarcodeInput('');
        }
      } catch (err) {
        // Fallback search in loaded products
        const match = products.find((p) => p.barcode === code || p.sku === code);
        if (match) {
          addToCart(match);
          setBarcodeInput('');
        } else {
          showToast(`Produk dengan barcode "${code}" tidak ditemukan`, 'error');
        }
      }
    }
  };

  const updateCartQty = (productId, delta) => {
    setCart((prev) => {
      return prev
        .map((item) => {
          if (item.product.id === productId) {
            const availableStock = Number(item.product.stock) || 0;
            const newQty = item.quantity + delta;

            if (newQty <= 0) return null; // remove item
            if (newQty > availableStock) {
              showToast(`Maksimal stok tersedia: ${availableStock}`, 'error');
              return item;
            }

            return {
              ...item,
              quantity: newQty,
              subtotal: newQty * item.unit_price - item.discount_amount,
            };
          }
          return item;
        })
        .filter(Boolean);
    });
  };

  const removeCartItem = (productId) => {
    setCart((prev) => prev.filter((item) => item.product.id !== productId));
  };

  const clearCart = () => {
    setCart([]);
    setTransactionDiscount(0);
  };

  // Calculations
  const cartSubtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
  const totalDiscount = Number(transactionDiscount) || 0;
  const grandTotal = Math.max(0, cartSubtotal - totalDiscount);

  // Quick cash buttons
  const cashOptions = [
    { label: 'Uang Pas', value: grandTotal },
    { label: '10.000', value: 10000 },
    { label: '20.000', value: 20000 },
    { label: '50.000', value: 50000 },
    { label: '100.000', value: 100000 },
    { label: '200.000', value: 200000 },
  ];

  const currentPaid = Number(paidAmount) || 0;
  const changeAmount = paymentMethod === 'cash' ? Math.max(0, currentPaid - grandTotal) : 0;

  const handleOpenPayment = () => {
    if (cart.length === 0) {
      showToast('Keranjang kasir masih kosong', 'error');
      return;
    }
    setPaidAmount(grandTotal);
    setIsPaymentModalOpen(true);
  };

  const handleCheckoutSubmit = async (e) => {
    e.preventDefault();
    if (paymentMethod === 'cash' && currentPaid < grandTotal) {
      showToast('Uang yang dibayarkan kurang dari total belanja!', 'error');
      return;
    }

    setIsSubmitting(true);
    try {
      const payload = {
        customer_name: customerName,
        payment_method: paymentMethod,
        paid_amount: paymentMethod === 'cash' ? currentPaid : grandTotal,
        discount_amount: totalDiscount,
        items: cart.map((item) => ({
          product_id: item.product.id,
          quantity: item.quantity,
          unit_price: item.unit_price,
          discount_amount: item.discount_amount,
        })),
      };

      const res = await posService.checkout(payload);
      if (res.success) {
        showToast('Transaksi berhasil disimpan!');
        setIsPaymentModalOpen(false);

        // Fetch receipt data for printing
        const recRes = await posService.getReceipt(res.data.id);
        if (recRes.success) {
          setReceiptData(recRes.data);
          setIsReceiptModalOpen(true);
        }

        // Reset cart and reload product stocks
        clearCart();
        loadProducts(searchQuery);
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal memproses transaksi kasir', 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handlePrintReceipt = () => {
    window.print();
  };

  return (
    <div className="space-y-4">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2 ${
            toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-700'
              : 'bg-emerald-50 border-emerald-200 text-emerald-700'
          }`}
        >
          <span>{toast.type === 'error' ? '⚠️' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      {/* POS Top Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
        <div>
          <h1 className="text-lg font-bold text-[#16323B] flex items-center gap-2">
            <span className="p-1.5 bg-[#EAF8FA] text-[#167A93] rounded-lg border border-[#38BFE0]/30">
              <IconPos className="w-5 h-5" />
            </span>
            FinToko POS — Layar Kasir Penjualan
          </h1>
          <p className="text-xs text-[#577782]">
            Kasir: <strong className="text-[#16323B]">{user?.name}</strong> &bull; Mode Scan Barcode &amp; Pencarian Cepat
          </p>
        </div>

        {/* Barcode Fast Scan Input */}
        <div className="w-full sm:w-80">
          <div className="relative">
            <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-[#577782]">
              📷
            </span>
            <input
              ref={barcodeInputRef}
              type="text"
              placeholder="Scan Barcode (Tekan Enter)..."
              value={barcodeInput}
              onChange={(e) => setBarcodeInput(e.target.value)}
              onKeyDown={handleBarcodeKeyDown}
              className="w-full pl-9 pr-4 py-2 bg-[#F8FDFE] border-2 border-[#27C5E5] rounded-xl text-xs font-mono font-bold text-[#16323B] placeholder:font-sans placeholder:font-normal focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#27C5E5]/30 transition"
            />
          </div>
        </div>
      </div>

      {/* Main Layout: Product Grid (Left 7 cols) & Cart Panel (Right 5 cols) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        {/* Left: Product Catalog */}
        <div className="lg:col-span-7 xl:col-span-8 space-y-3">
          {/* Search & Category Filter Chips */}
          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-3 shadow-xs space-y-2.5">
            <input
              type="text"
              placeholder="Ketik nama produk atau SKU..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full px-3.5 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#27C5E5]"
            />

            {/* Category Chips */}
            <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
              <button
                type="button"
                onClick={() => setSelectedCategory('')}
                className={`px-3 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition ${
                  selectedCategory === ''
                    ? 'bg-[#167A93] text-white shadow-xs'
                    : 'bg-[#F0F9FB] text-[#577782] hover:bg-[#EAF8FA]'
                }`}
              >
                Semua Kategori
              </button>
              {categories.map((c) => (
                <button
                  key={c.id}
                  type="button"
                  onClick={() => setSelectedCategory(c.id)}
                  className={`px-3 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition ${
                    String(selectedCategory) === String(c.id)
                      ? 'bg-[#167A93] text-white shadow-xs'
                      : 'bg-[#F0F9FB] text-[#577782] hover:bg-[#EAF8FA]'
                  }`}
                >
                  {c.name}
                </button>
              ))}
            </div>
          </div>

          {/* Product Cards Grid */}
          <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
            {isLoadingProducts ? (
              <div className="col-span-full p-8 text-center text-xs text-[#577782]">
                Memuat katalog produk...
              </div>
            ) : filteredProducts.length === 0 ? (
              <div className="col-span-full p-8 text-center text-xs text-[#577782] bg-white rounded-2xl border border-[#D7EBEF]">
                Tidak ada produk yang cocok dengan pencarian.
              </div>
            ) : (
              filteredProducts.map((p) => {
                const stock = Number(p.stock) || 0;
                const isOutOfStock = stock <= 0;
                const isLow = stock > 0 && stock <= Number(p.min_stock || 5);

                return (
                  <div
                    key={p.id}
                    onClick={() => !isOutOfStock && addToCart(p)}
                    className={`bg-white border rounded-2xl p-3 flex flex-col justify-between transition group select-none ${
                      isOutOfStock
                        ? 'opacity-60 border-gray-200 cursor-not-allowed'
                        : 'border-[#D7EBEF] hover:border-[#27C5E5] hover:shadow-md cursor-pointer active:scale-[0.98]'
                    }`}
                  >
                    <div>
                      <div className="flex items-center justify-between gap-1 text-[10px] text-[#577782] mb-1">
                        <span className="font-mono">{p.sku}</span>
                        <span
                          className={`px-1.5 py-0.5 rounded font-bold ${
                            isOutOfStock
                              ? 'bg-rose-50 text-rose-600'
                              : isLow
                              ? 'bg-amber-50 text-amber-700'
                              : 'bg-emerald-50 text-emerald-700'
                          }`}
                        >
                          {isOutOfStock ? 'Habis' : `${stock} ${p.unit?.symbol || 'pcs'}`}
                        </span>
                      </div>
                      <h4 className="text-xs font-bold text-[#16323B] line-clamp-2 group-hover:text-[#167A93]">
                        {p.name}
                      </h4>
                    </div>

                    <div className="mt-3 pt-2 border-t border-[#F0F9FB] flex items-center justify-between">
                      <div className="text-xs font-bold font-mono text-[#167A93]">
                        {formatRupiah(p.selling_price)}
                      </div>
                      <button
                        type="button"
                        disabled={isOutOfStock}
                        className="px-2 py-1 text-[11px] font-bold rounded-lg bg-[#EAF8FA] text-[#167A93] group-hover:bg-[#27C5E5] group-hover:text-white transition"
                      >
                        + Tambah
                      </button>
                    </div>
                  </div>
                );
              })
            )}
          </div>
        </div>

        {/* Right: Cart Panel */}
        <div className="lg:col-span-5 xl:col-span-4 bg-white border border-[#D7EBEF] rounded-2xl shadow-sm flex flex-col sticky top-4 max-h-[calc(100vh-100px)]">
          {/* Cart Header */}
          <div className="p-4 border-b border-[#EAF4F6] flex items-center justify-between bg-[#F8FDFE] rounded-t-2xl">
            <div className="flex items-center gap-2">
              <span className="font-bold text-sm text-[#16323B]">Keranjang Belanja</span>
              <span className="px-2 py-0.5 bg-[#EAF8FA] text-[#167A93] font-bold text-xs rounded-full border border-[#38BFE0]/30">
                {cart.reduce((sum, i) => sum + i.quantity, 0)} item
              </span>
            </div>
            {cart.length > 0 && (
              <button
                type="button"
                onClick={clearCart}
                className="text-xs text-rose-500 hover:text-rose-700 font-semibold"
              >
                Kosongkan
              </button>
            )}
          </div>

          {/* Cart Item List */}
          <div className="p-3 overflow-y-auto space-y-2 flex-1 divide-y divide-[#F0F9FB]">
            {cart.length === 0 ? (
              <div className="py-12 text-center text-xs text-[#577782]">
                <div className="text-3xl mb-2">🛒</div>
                Keranjang masih kosong.<br />
                Pilih produk di samping atau scan barcode.
              </div>
            ) : (
              cart.map((item) => (
                <div key={item.product.id} className="pt-2 first:pt-0 space-y-1">
                  <div className="flex justify-between items-start gap-2">
                    <div>
                      <div className="text-xs font-bold text-[#16323B]">{item.product.name}</div>
                      <div className="text-[11px] text-[#577782] font-mono">
                        {formatRupiah(item.unit_price)} / {item.product.unit?.symbol || 'pcs'}
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={() => removeCartItem(item.product.id)}
                      className="text-[#8FA8B0] hover:text-rose-500 text-sm px-1"
                      title="Hapus item"
                    >
                      ✕
                    </button>
                  </div>

                  <div className="flex items-center justify-between pt-1">
                    {/* Qty Stepper */}
                    <div className="flex items-center gap-1 bg-[#F8FDFE] border border-[#D7EBEF] rounded-lg p-0.5">
                      <button
                        type="button"
                        onClick={() => updateCartQty(item.product.id, -1)}
                        className="w-6 h-6 flex items-center justify-center font-bold text-xs text-[#577782] hover:bg-white rounded"
                      >
                        -
                      </button>
                      <span className="w-8 text-center font-mono font-bold text-xs text-[#16323B]">
                        {item.quantity}
                      </span>
                      <button
                        type="button"
                        onClick={() => updateCartQty(item.product.id, 1)}
                        className="w-6 h-6 flex items-center justify-center font-bold text-xs text-[#577782] hover:bg-white rounded"
                      >
                        +
                      </button>
                    </div>

                    {/* Subtotal Item */}
                    <div className="font-mono text-xs font-bold text-[#167A93]">
                      {formatRupiah(item.subtotal)}
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Cart Footer & Checkout Button */}
          <div className="p-4 border-t border-[#EAF4F6] bg-[#F8FDFE] rounded-b-2xl space-y-3">
            <div className="space-y-1.5 text-xs">
              <div className="flex justify-between text-[#577782]">
                <span>Subtotal Belanja:</span>
                <span className="font-mono font-semibold">{formatRupiah(cartSubtotal)}</span>
              </div>
              <div className="flex justify-between items-center text-[#577782]">
                <span>Diskon Transaksi (Rp):</span>
                <input
                  type="number"
                  min="0"
                  value={transactionDiscount}
                  onChange={(e) => setTransactionDiscount(e.target.value)}
                  className="w-24 px-2 py-0.5 bg-white border border-[#D7EBEF] rounded text-right font-mono text-xs"
                />
              </div>
              <div className="flex justify-between text-base font-bold text-[#16323B] pt-1.5 border-t border-[#EAF4F6]">
                <span>Total Bayar:</span>
                <span className="font-mono text-[#167A93]">{formatRupiah(grandTotal)}</span>
              </div>
            </div>

            <button
              type="button"
              onClick={handleOpenPayment}
              disabled={cart.length === 0}
              className="w-full py-3 bg-[#27C5E5] hover:bg-[#167A93] text-white font-bold text-sm rounded-xl shadow-md transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <span>Bayar Sekarang (F4)</span>
              <span>→</span>
            </button>
          </div>
        </div>
      </div>

      {/* Checkout / Payment Modal */}
      {isPaymentModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/50 backdrop-blur-xs animate-backdrop">
          <div className="bg-white rounded-2xl border border-[#D7EBEF] shadow-2xl w-full max-w-lg p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-[#EAF4F6] pb-3">
              <div>
                <h3 className="text-base font-bold text-[#16323B]">Penyelesaian Pembayaran Kasir</h3>
                <p className="text-xs text-[#577782]">Pilih metode pembayaran dan masukkan uang pelanggan</p>
              </div>
              <button
                onClick={() => setIsPaymentModalOpen(false)}
                className="text-[#577782] hover:text-[#16323B] text-sm"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleCheckoutSubmit} className="space-y-4">
              {/* Grand Total Display */}
              <div className="bg-[#EAF8FA] border border-[#38BFE0]/30 rounded-xl p-4 text-center">
                <span className="text-xs font-semibold text-[#577782]">Total yang Harus Dibayar:</span>
                <div className="text-2xl font-bold font-mono text-[#167A93] mt-0.5">
                  {formatRupiah(grandTotal)}
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">
                  Nama Pelanggan (Opsional)
                </label>
                <input
                  type="text"
                  value={customerName}
                  onChange={(e) => setCustomerName(e.target.value)}
                  placeholder="Pelanggan Umum"
                  className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">
                  Metode Pembayaran
                </label>
                <div className="grid grid-cols-3 gap-2">
                  {[
                    { id: 'cash', label: '💵 Tunai' },
                    { id: 'transfer', label: '🏦 Transfer' },
                    { id: 'qris', label: '📱 QRIS' },
                  ].map((m) => (
                    <button
                      key={m.id}
                      type="button"
                      onClick={() => {
                        setPaymentMethod(m.id);
                        if (m.id !== 'cash') {
                          setPaidAmount(grandTotal);
                        }
                      }}
                      className={`py-2 px-3 rounded-xl text-xs font-bold border transition ${
                        paymentMethod === m.id
                          ? 'bg-[#167A93] text-white border-[#167A93] shadow-xs'
                          : 'bg-[#F8FDFE] text-[#577782] border-[#D7EBEF] hover:bg-white'
                      }`}
                    >
                      {m.label}
                    </button>
                  ))}
                </div>
              </div>

              {paymentMethod === 'cash' && (
                <div className="space-y-3">
                  <div>
                    <label className="block text-xs font-semibold text-[#16323B] mb-1">
                      Uang Diterima dari Pelanggan (Rp)
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={paidAmount}
                      onChange={(e) => setPaidAmount(e.target.value)}
                      placeholder="0"
                      autoFocus
                      className="w-full px-3.5 py-2.5 bg-white border-2 border-[#27C5E5] rounded-xl text-lg font-mono font-bold text-[#16323B] text-right"
                    />
                  </div>

                  {/* Quick Cash Buttons */}
                  <div className="grid grid-cols-3 gap-1.5">
                    {cashOptions.map((opt, idx) => (
                      <button
                        key={idx}
                        type="button"
                        onClick={() => setPaidAmount(opt.value)}
                        className="py-1.5 px-2 bg-[#F0F9FB] hover:bg-[#EAF8FA] border border-[#D7EBEF] rounded-lg text-xs font-mono font-semibold text-[#167A93] transition"
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>

                  {/* Change Calculation */}
                  <div className="p-3 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl flex items-center justify-between">
                    <span className="text-xs font-semibold text-[#577782]">Uang Kembalian:</span>
                    <span
                      className={`text-lg font-bold font-mono ${
                        currentPaid >= grandTotal ? 'text-emerald-600' : 'text-rose-500'
                      }`}
                    >
                      {currentPaid >= grandTotal
                        ? formatRupiah(changeAmount)
                        : `Kurang ${formatRupiah(grandTotal - currentPaid)}`}
                    </span>
                  </div>
                </div>
              )}

              <div className="pt-2 flex items-center justify-end gap-2 border-t border-[#EAF4F6]">
                <button
                  type="button"
                  onClick={() => setIsPaymentModalOpen(false)}
                  disabled={isSubmitting}
                  className="px-4 py-2 text-xs font-semibold text-[#577782] hover:bg-[#F0F9FB] rounded-xl"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting || (paymentMethod === 'cash' && currentPaid < grandTotal)}
                  className="px-6 py-2.5 bg-[#27C5E5] hover:bg-[#167A93] text-white font-bold text-xs rounded-xl shadow-md transition disabled:opacity-40"
                >
                  {isSubmitting ? 'Memproses...' : 'Konfirmasi & Selesaikan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Thermal Receipt Print Modal (Indomaret Style) */}
      {isReceiptModalOpen && receiptData && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/60 backdrop-blur-xs animate-backdrop">
          <div className="bg-white rounded-2xl border border-[#D7EBEF] shadow-2xl w-full max-w-sm p-5 space-y-4 max-h-[95vh] flex flex-col">
            <div className="flex items-center justify-between border-b border-[#EAF4F6] pb-2">
              <h3 className="text-xs font-bold uppercase tracking-wider text-[#16323B]">
                Struk Pembelian Kasir
              </h3>
              <button
                onClick={() => setIsReceiptModalOpen(false)}
                className="text-xs text-[#577782] hover:text-[#16323B]"
              >
                ✕
              </button>
            </div>

            {/* Printable Area (58mm / 80mm format) */}
            <div
              id="printable-receipt"
              className="p-4 bg-white border border-dashed border-[#D7EBEF] rounded-xl font-mono text-[11px] leading-tight text-[#16323B] space-y-2 overflow-y-auto"
            >
              <div className="text-center space-y-0.5 border-b border-dashed border-[#B0D2D9] pb-2">
                <div className="font-bold text-xs uppercase">{receiptData.store_name}</div>
                <div className="text-[10px] text-[#577782]">{receiptData.store_address}</div>
                <div className="text-[10px] text-[#577782]">Telp: {receiptData.store_phone}</div>
              </div>

              <div className="text-[10px] space-y-0.5 border-b border-dashed border-[#B0D2D9] pb-1.5">
                <div className="flex justify-between">
                  <span>No: {receiptData.transaction_number}</span>
                  <span>{receiptData.sale_date}</span>
                </div>
                <div className="flex justify-between">
                  <span>Kasir: {receiptData.cashier_name}</span>
                  <span>{receiptData.sale_time}</span>
                </div>
                <div>Plg: {receiptData.customer_name}</div>
              </div>

              {/* Items */}
              <div className="space-y-1.5 border-b border-dashed border-[#B0D2D9] pb-2">
                {receiptData.items?.map((item, idx) => (
                  <div key={idx} className="space-y-0.5">
                    <div className="font-bold">{item.name}</div>
                    <div className="flex justify-between text-[10px]">
                      <span>
                        {item.quantity} x {Number(item.unit_price).toLocaleString('id-ID')}
                      </span>
                      <span>{Number(item.subtotal).toLocaleString('id-ID')}</span>
                    </div>
                  </div>
                ))}
              </div>

              {/* Totals */}
              <div className="space-y-1 border-b border-dashed border-[#B0D2D9] pb-2 text-[11px]">
                <div className="flex justify-between">
                  <span>Subtotal:</span>
                  <span>{Number(receiptData.subtotal).toLocaleString('id-ID')}</span>
                </div>
                {Number(receiptData.discount_amount) > 0 && (
                  <div className="flex justify-between">
                    <span>Diskon:</span>
                    <span>-{Number(receiptData.discount_amount).toLocaleString('id-ID')}</span>
                  </div>
                )}
                <div className="flex justify-between font-bold text-xs pt-1 border-t border-dashed border-[#D7EBEF]">
                  <span>TOTAL:</span>
                  <span>Rp {Number(receiptData.grand_total).toLocaleString('id-ID')}</span>
                </div>
                <div className="flex justify-between text-[10px]">
                  <span>Bayar ({receiptData.payment_method}):</span>
                  <span>Rp {Number(receiptData.paid_amount).toLocaleString('id-ID')}</span>
                </div>
                <div className="flex justify-between font-bold text-[11px]">
                  <span>Kembali:</span>
                  <span>Rp {Number(receiptData.change_amount).toLocaleString('id-ID')}</span>
                </div>
              </div>

              {/* Footer */}
              <div className="text-center pt-1 text-[10px] text-[#577782] space-y-0.5">
                <div>{receiptData.footer_message}</div>
                <div>Barang yang dibeli tidak dapat ditukar</div>
              </div>
            </div>

            {/* Receipt Modal Buttons */}
            <div className="flex items-center gap-2 pt-2">
              <button
                type="button"
                onClick={handlePrintReceipt}
                className="flex-1 py-2 bg-[#27C5E5] hover:bg-[#167A93] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5"
              >
                <IconPrinter className="w-4 h-4" />
                <span>Cetak Struk</span>
              </button>
              <button
                type="button"
                onClick={() => setIsReceiptModalOpen(false)}
                className="py-2 px-4 bg-[#F0F9FB] hover:bg-[#EAF8FA] text-[#167A93] font-semibold text-xs rounded-xl transition"
              >
                Tutup (Baru)
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default PosPage;
