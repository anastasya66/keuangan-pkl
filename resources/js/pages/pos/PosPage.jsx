import React, { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import useAuth from '../../hooks/useAuth';
import posService from '../../services/posService';
import categoryService from '../../services/categoryService';
import { formatRupiah } from '../../utils/currency';
import { IconPos } from '../../components/common/Icons';
import ReceiptModal from '../../components/pos/ReceiptModal';
import CashPaymentModal from '../../components/pos/CashPaymentModal';
import QrisPaymentModal from '../../components/pos/QrisPaymentModal';
import BankTransferModal from '../../components/pos/BankTransferModal';
import SalesHistoryModal from '../../components/pos/SalesHistoryModal';
import PosShortcutsModal from '../../components/pos/PosShortcutsModal';

/**
 * Komponen Thumbnail Produk Modern dengan Fallback Visual Elegan
 */
function ProductThumbnail({ product, className = '', aspect = 'aspect-[4/3]' }) {
  const [hasError, setHasError] = useState(false);

  const imageUrl = useMemo(() => {
    const raw = product?.image_url || product?.image || product?.photo || product?.photo_url || product?.thumbnail;
    if (!raw) return null;
    if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:') || raw.startsWith('/')) {
      return raw;
    }
    return `/storage/${raw}`;
  }, [product]);

  const placeholderTheme = useMemo(() => {
    const themes = [
      { bg: 'from-[#E5F6FA] to-[#CFEFF6]', text: 'text-[#167A93]', badge: 'bg-[#55BFD8]/15 text-[#167A93]' },
      { bg: 'from-[#F0FDF4] to-[#DCFCE7]', text: 'text-emerald-700', badge: 'bg-emerald-100 text-emerald-800' },
      { bg: 'from-[#FFF7ED] to-[#FFEDD5]', text: 'text-amber-700', badge: 'bg-amber-100 text-amber-800' },
      { bg: 'from-[#F5F3FF] to-[#EDE9FE]', text: 'text-purple-700', badge: 'bg-purple-100 text-purple-800' },
      { bg: 'from-[#EFF6FF] to-[#DBEAFE]', text: 'text-blue-700', badge: 'bg-blue-100 text-blue-800' },
    ];
    const hash = ((product?.name || 'p').charCodeAt(0) + (Number(product?.category_id) || 1)) % themes.length;
    return themes[hash];
  }, [product]);

  if (imageUrl && !hasError) {
    return (
      <div className={`overflow-hidden relative bg-[#F8FAFA] ${aspect} ${className}`}>
        <img
          src={imageUrl}
          alt={product.name}
          onError={() => setHasError(true)}
          className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          loading="lazy"
        />
      </div>
    );
  }

  return (
    <div
      className={`overflow-hidden relative bg-gradient-to-br ${placeholderTheme.bg} flex flex-col items-center justify-center p-3 border-b border-[#E7ECEE] ${aspect} ${className}`}
    >
      <div className="w-10 h-10 rounded-xl bg-white/90 shadow-2xs backdrop-blur-xs flex items-center justify-center text-[#167A93] transition-transform duration-300 group-hover:scale-110">
        <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
          <path d="m3.3 7 8.7 5 8.7-5" />
          <path d="M12 22V12" />
        </svg>
      </div>
      <span className={`mt-2 text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full ${placeholderTheme.badge} max-w-[85%] truncate shadow-2xs`}>
        {product?.category?.name || 'Produk'}
      </span>
    </div>
  );
}

/**
 * Thumbnail Kecil untuk Baris Item di Keranjang Belanja
 */
function CartThumbnail({ product }) {
  const [hasError, setHasError] = useState(false);
  const imageUrl = useMemo(() => {
    const raw = product?.image_url || product?.image || product?.photo || product?.thumbnail;
    if (!raw) return null;
    if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:') || raw.startsWith('/')) {
      return raw;
    }
    return `/storage/${raw}`;
  }, [product]);

  if (imageUrl && !hasError) {
    return (
      <div className="w-11 h-11 rounded-xl overflow-hidden shrink-0 bg-[#F1F4F5] border border-[#E7ECEE]">
        <img src={imageUrl} alt={product.name} onError={() => setHasError(true)} className="w-full h-full object-cover" />
      </div>
    );
  }

  return (
    <div className="w-11 h-11 rounded-xl shrink-0 bg-gradient-to-br from-[#E5F6FA] to-[#CFEFF6] border border-[#D5E3E7] flex items-center justify-center text-[#167A93]">
      <svg className="w-5 h-5 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
        <path d="m3.3 7 8.7 5 8.7-5" />
        <path d="M12 22V12" />
      </svg>
    </div>
  );
}

export function PosPage() {
  const { user } = useAuth();

  // Data State
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [stockFilter, setStockFilter] = useState('all'); // 'all', 'available', 'low'

  // Cart State
  const [cart, setCart] = useState([]);
  const [customerName, setCustomerName] = useState('Pelanggan Umum');
  const [isEditingCustomer, setIsEditingCustomer] = useState(false);
  const [customDiscount, setCustomDiscount] = useState(0);
  const [showDiscountInput, setShowDiscountInput] = useState(false);
  const [taxRate, setTaxRate] = useState(0); // 0% default, adjustable

  // Modals State
  const [activeModal, setActiveModal] = useState(null); // 'cash', 'qris', 'transfer', 'receipt', 'history', 'shortcuts'
  const [receiptData, setReceiptData] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Toast
  const [toast, setToast] = useState(null);
  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 3500);
  };

  const searchInputRef = useRef(null);
  const customerInputRef = useRef(null);

  // 1. Fetch All Products & Categories
  const fetchProducts = useCallback(async () => {
    setIsLoading(true);
    try {
      const [prodRes, catRes] = await Promise.all([
        posService.getPosProducts({ limit: 250 }),
        categoryService.getCategories({ all: true, is_active: true }).catch(() => ({ success: false })),
      ]);

      if (prodRes.success && prodRes.data) {
        setProducts(prodRes.data);
      }
      if (catRes.success && catRes.data) {
        setCategories(catRes.data);
      }
    } catch (err) {
      console.error('Gagal memuat produk kasir:', err);
      showToast('Gagal memuat katalog produk', 'error');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  // Autofocus search on mount
  useEffect(() => {
    if (searchInputRef.current) {
      searchInputRef.current.focus();
    }
  }, []);

  // 2. Global Keyboard Shortcuts Listener
  useEffect(() => {
    const handleKeyDown = (e) => {
      // Abaikan jika user sedang mengetik di input biasa kecuali tombol khusus
      const tag = e.target.tagName;
      const isInput = tag === 'INPUT' || tag === 'TEXTAREA';

      if (e.key === 'F2') {
        e.preventDefault();
        searchInputRef.current?.focus();
        searchInputRef.current?.select();
      } else if (e.key === 'F4') {
        e.preventDefault();
        setIsEditingCustomer(true);
        setTimeout(() => customerInputRef.current?.focus(), 50);
      } else if (e.key === 'F8') {
        e.preventDefault();
        setActiveModal('history');
      } else if (e.key === 'F9' && cart.length > 0) {
        e.preventDefault();
        setActiveModal('cash');
      } else if (e.key === 'F10' && cart.length > 0) {
        e.preventDefault();
        setActiveModal('qris');
      } else if (e.key === 'F11' && cart.length > 0) {
        e.preventDefault();
        setActiveModal('transfer');
      } else if (e.key === 'Escape') {
        if (activeModal) {
          setActiveModal(null);
        } else if (isEditingCustomer) {
          setIsEditingCustomer(false);
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [activeModal, cart.length, isEditingCustomer]);

  // 3. Filtered Products
  const filteredProducts = useMemo(() => {
    return products.filter((p) => {
      // Category filter
      if (selectedCategory && String(p.category_id) !== String(selectedCategory)) {
        return false;
      }

      // Stock status filter
      const stock = Number(p.stock) || 0;
      const minStock = Number(p.min_stock || 5);
      if (stockFilter === 'available' && stock <= 0) return false;
      if (stockFilter === 'low' && (stock <= 0 || stock > minStock)) return false;

      // Text query
      if (!searchQuery.trim()) return true;
      const q = searchQuery.trim().toLowerCase();
      const name = (p.name || '').toLowerCase();
      const sku = (p.sku || '').toLowerCase();
      const barcode = (p.barcode || '').toLowerCase();

      return name.includes(q) || sku.includes(q) || barcode.includes(q);
    });
  }, [products, selectedCategory, stockFilter, searchQuery]);

  // 4. Handle Barcode Scanner Enter Key
  const handleSearchKeyDown = async (e) => {
    if (e.key === 'Enter' && searchQuery.trim()) {
      e.preventDefault();
      const query = searchQuery.trim();

      // Cari exact match barcode di memori produk
      const exact = products.find(
        (p) => (p.barcode && p.barcode.toLowerCase() === query.toLowerCase()) ||
               (p.sku && p.sku.toLowerCase() === query.toLowerCase())
      );

      if (exact) {
        handleAddToCart(exact);
        setSearchQuery('');
        return;
      }

      // Fallback query barcode via backend endpoint
      try {
        const res = await posService.findByBarcode(query);
        if (res.success && res.data) {
          handleAddToCart(res.data);
          setSearchQuery('');
        } else {
          showToast(`Produk "${query}" tidak ditemukan`, 'error');
        }
      } catch (err) {
        showToast(`Produk barcode "${query}" tidak ditemukan`, 'error');
      }
    }
  };

  // 5. Add Product to Cart
  const handleAddToCart = (product) => {
    const availableStock = Number(product.stock) || 0;
    if (availableStock <= 0) {
      showToast(`Stok "${product.name}" telah habis!`, 'error');
      return;
    }

    setCart((prev) => {
      const idx = prev.findIndex((item) => item.product.id === product.id);
      if (idx > -1) {
        const item = prev[idx];
        const newQty = item.quantity + 1;
        if (newQty > availableStock) {
          showToast(`Stok maksimal "${product.name}" hanya ${availableStock}`, 'error');
          return prev;
        }
        const updated = [...prev];
        updated[idx] = {
          ...item,
          quantity: newQty,
          subtotal: newQty * item.unit_price,
        };
        showToast(`+1 ${product.name} (Total: ${newQty})`);
        return updated;
      }

      const unitPrice = Number(product.selling_price) || 0;
      showToast(`✓ Ditambahkan: ${product.name}`);
      return [
        ...prev,
        {
          product,
          quantity: 1,
          unit_price: unitPrice,
          subtotal: unitPrice,
        },
      ];
    });
  };

  // 6. Update Quantity in Cart
  const updateCartQty = (productId, delta) => {
    setCart((prev) =>
      prev
        .map((item) => {
          if (item.product.id === productId) {
            const availableStock = Number(item.product.stock) || 0;
            const newQty = item.quantity + delta;
            if (newQty <= 0) return null;
            if (newQty > availableStock) {
              showToast(`Stok maksimal "${item.product.name}" hanya ${availableStock}`, 'error');
              return item;
            }
            return {
              ...item,
              quantity: newQty,
              subtotal: newQty * item.unit_price,
            };
          }
          return item;
        })
        .filter(Boolean)
    );
  };

  // 7. Remove item from cart
  const removeCartItem = (productId) => {
    setCart((prev) => prev.filter((item) => item.product.id !== productId));
  };

  // 8. Clear Cart
  const handleClearCart = () => {
    if (cart.length === 0) return;
    setCart([]);
    setCustomDiscount(0);
    showToast('Keranjang pesanan telah dibersihkan');
  };

  // 9. Financial Calculations
  const rawSubtotal = cart.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0);
  const totalItemCount = cart.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
  const discountAmount = Math.min(rawSubtotal, Number(customDiscount) || 0);
  const taxableAmount = Math.max(0, rawSubtotal - discountAmount);
  const taxAmount = Math.round((taxableAmount * taxRate) / 100);
  const grandTotal = Math.max(0, taxableAmount + taxAmount);

  // 10. Execute Checkout Transaction
  const executeCheckout = async ({ payment_method, paid_amount, order_id, notes }) => {
    if (cart.length === 0 || isSubmitting) return;

    setIsSubmitting(true);
    try {
      const payload = {
        customer_name: customerName || 'Pelanggan Umum',
        payment_method,
        paid_amount: Number(paid_amount) || grandTotal,
        discount_amount: discountAmount,
        tax_amount: taxAmount,
        order_id: order_id || null,
        notes: notes || null,
        items: cart.map((item) => ({
          product_id: item.product.id,
          quantity: item.quantity,
          unit_price: item.unit_price,
          discount_amount: 0,
        })),
      };

      const res = await posService.checkout(payload);
      if (res.success && res.data) {
        showToast(`✓ Penjualan #${res.data.transaction_number} berhasil diselesaikan!`, 'success');

        // Ambil data struk kasir
        try {
          const receiptRes = await posService.getReceipt(res.data.id);
          if (receiptRes.success && receiptRes.data) {
            setReceiptData(receiptRes.data);
            setActiveModal('receipt');
          }
        } catch (e) {
          console.error('Gagal mengambil struk:', e);
        }

        // Reset Cart
        setCart([]);
        setCustomDiscount(0);
        setShowDiscountInput(false);

        // Refresh stock numbers
        fetchProducts();
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Gagal memproses checkout kasir';
      showToast(msg, 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="space-y-4">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-2xl shadow-xl border text-xs font-semibold flex items-center gap-2.5 transition-all animate-in slide-in-from-top-2 ${
            toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-800'
              : 'bg-emerald-50 border-emerald-200 text-emerald-800'
          }`}
        >
          <span className="text-base">{toast.type === 'error' ? '⚠️' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      {/* ========================================================
          HEADER BAR MODERN (MATCHING REFERENCE UI)
         ======================================================== */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-3.5 sm:px-5 sm:py-3.5 rounded-2xl border border-[#E7ECEE] shadow-2xs">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-[#E5F6FA] text-[#167A93] rounded-xl border border-[#55BFD8]/30 shadow-2xs">
            <IconPos className="w-5 h-5" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-lg font-bold text-[#20282D]">New Sale</h1>
              <span className="px-2.5 py-0.5 bg-[#E5F6FA] text-[#167A93] font-mono text-[11px] font-bold rounded-full border border-[#79D5E8]/40">
                POS Kasir Aktif
              </span>
            </div>
            <p className="text-xs text-[#879197]">
              Kasir: <strong className="text-[#20282D]">{user?.name || 'Kasir'}</strong> &bull; {products.length} Produk Siap Jual
            </p>
          </div>
        </div>

        {/* Action Buttons Header: Returns, History, Shortcuts */}
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => setActiveModal('history')}
            className="px-3 py-1.5 bg-[#F8FAFA] hover:bg-[#E5F6FA] text-[#20282D] hover:text-[#167A93] border border-[#E7ECEE] rounded-xl text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 shadow-2xs"
            title="Riwayat Transaksi Penjualan Kasir (F8)"
          >
            <span>📜</span>
            <span>History</span>
          </button>

          <button
            type="button"
            onClick={() => setActiveModal('shortcuts')}
            className="px-3 py-1.5 bg-[#F8FAFA] hover:bg-[#E5F6FA] text-[#20282D] hover:text-[#167A93] border border-[#E7ECEE] rounded-xl text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 shadow-2xs"
            title="Daftar Shortcut Keyboard"
          >
            <span>⌨️</span>
            <span>Shortcuts</span>
          </button>
        </div>
      </div>

      {/* ========================================================
          MAIN POS TWO-COLUMN LAYOUT (LEFT CATALOG & RIGHT CHECKOUT)
         ======================================================== */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        {/* ========================================================
            LEFT COLUMN (7-8 COLS): PRODUCT CATALOG & FILTER SECTION
           ======================================================== */}
        <div className="lg:col-span-7 xl:col-span-8 space-y-3.5">
          {/* Search Box with Barcode Icon */}
          <div className="bg-white border border-[#E7ECEE] rounded-2xl p-3.5 shadow-2xs space-y-3">
            <div className="relative flex items-center">
              <span className="absolute left-3.5 text-[#879197] pointer-events-none">
                <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
              </span>

              <input
                ref={searchInputRef}
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                onKeyDown={handleSearchKeyDown}
                placeholder="Search products by name, SKU or scan barcode... (Enter)"
                className="w-full pl-10 pr-16 py-2.5 bg-[#F8FAFA] border border-[#E7ECEE] focus:border-[#55BFD8] focus:bg-white rounded-xl text-xs font-semibold text-[#20282D] placeholder:text-[#A6AFB3] placeholder:font-normal focus:outline-none transition shadow-2xs"
              />

              {/* Barcode scanner icon & clear button */}
              <div className="absolute right-3 flex items-center gap-1.5 text-[#879197]">
                {searchQuery ? (
                  <button
                    type="button"
                    onClick={() => setSearchQuery('')}
                    className="text-xs text-[#879197] hover:text-[#20282D] p-1 rounded-full cursor-pointer"
                  >
                    ✕
                  </button>
                ) : (
                  <span className="font-mono text-xs opacity-60 tracking-tighter" title="Barcode Scanner Ready">
                    |||||||||
                  </span>
                )}
              </div>
            </div>

            {/* Category Filter Pills */}
            <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
              <button
                type="button"
                onClick={() => setSelectedCategory('')}
                className={`px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-1.5 ${
                  selectedCategory === ''
                    ? 'bg-[#167A93] text-white shadow-2xs'
                    : 'bg-[#F1F4F5] text-[#20282D] hover:bg-[#E5F6FA] hover:text-[#167A93]'
                }`}
              >
                <span>All Products</span>
                <span className={`px-1.5 py-0.2 rounded-full text-[10px] ${selectedCategory === '' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'}`}>
                  {products.length}
                </span>
              </button>

              {categories.map((c) => {
                const count = products.filter((p) => String(p.category_id) === String(c.id)).length;
                const isSelected = String(selectedCategory) === String(c.id);

                return (
                  <button
                    key={c.id}
                    type="button"
                    onClick={() => setSelectedCategory(c.id)}
                    className={`px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-1.5 ${
                      isSelected
                        ? 'bg-[#167A93] text-white shadow-2xs'
                        : 'bg-[#F1F4F5] text-[#20282D] hover:bg-[#E5F6FA] hover:text-[#167A93]'
                    }`}
                  >
                    <span>{c.name}</span>
                    <span className={`px-1.5 py-0.2 rounded-full text-[10px] ${isSelected ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'}`}>
                      {count}
                    </span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Product Cards Grid */}
          <div className="min-h-[460px]">
            {isLoading ? (
              <div className="p-16 text-center text-xs text-[#879197] bg-white rounded-2xl border border-[#E7ECEE] shadow-2xs">
                <div className="animate-spin text-3xl mb-3 text-[#167A93]">🔄</div>
                <p className="font-semibold text-sm text-[#20282D]">Memuat katalog produk kasir...</p>
                <p className="text-[#A6AFB3] mt-1 text-xs">Menyiapkan stok dan harga terkini</p>
              </div>
            ) : filteredProducts.length === 0 ? (
              <div className="p-16 text-center text-xs text-[#879197] bg-white rounded-2xl border border-[#E7ECEE] shadow-2xs">
                <div className="text-4xl mb-3">📦</div>
                <p className="font-bold text-sm text-[#20282D]">Tidak ada produk yang cocok</p>
                <p className="text-[#A6AFB3] mt-1 text-xs">
                  {searchQuery ? `Pencarian "${searchQuery}" tidak menghasilkan produk.` : 'Kategori ini belum memiliki produk aktif.'}
                </p>
              </div>
            ) : (
              <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5">
                {filteredProducts.map((p) => {
                  const stock = Number(p.stock) || 0;
                  const isOutOfStock = stock <= 0;
                  const isLow = stock > 0 && stock <= Number(p.min_stock || 5);
                  const inCartItem = cart.find((item) => item.product.id === p.id);

                  return (
                    <div
                      key={p.id}
                      onClick={() => !isOutOfStock && handleAddToCart(p)}
                      className={`group relative bg-white border rounded-2xl overflow-hidden flex flex-col justify-between transition-all duration-200 select-none ${
                        isOutOfStock
                          ? 'opacity-60 border-gray-200 bg-gray-50/70 cursor-not-allowed shadow-2xs'
                          : 'border-[#E7ECEE] hover:border-[#55BFD8] hover:shadow-lg hover:-translate-y-1 cursor-pointer active:scale-[0.98]'
                      }`}
                    >
                      {/* Floating In-Cart Badge */}
                      {inCartItem && (
                        <span className="absolute top-2.5 right-2.5 z-10 px-2 py-0.5 bg-[#167A93] text-white font-mono font-bold text-[11px] rounded-full shadow-md border-2 border-white flex items-center gap-1">
                          <span>✓</span>
                          <span>{inCartItem.quantity}x</span>
                        </span>
                      )}

                      {/* Product Image / Placeholder */}
                      <div className="relative overflow-hidden">
                        <ProductThumbnail product={p} aspect="aspect-[4/3]" />

                        {isOutOfStock && (
                          <div className="absolute inset-0 bg-black/45 backdrop-blur-[1px] flex items-center justify-center">
                            <span className="px-2.5 py-1 bg-rose-600 text-white font-bold text-[10px] rounded-md uppercase tracking-wider shadow-sm">
                              Out of Stock
                            </span>
                          </div>
                        )}
                      </div>

                      {/* Card Content */}
                      <div className="p-3 flex flex-col justify-between flex-1">
                        <div>
                          {/* Title & SKU */}
                          <h3 className="text-xs font-bold text-[#20282D] line-clamp-1 group-hover:text-[#167A93] transition-colors">
                            {p.name}
                          </h3>
                          <div className="text-[10px] font-mono text-[#879197] truncate mt-0.5">
                            SKU: {p.sku || p.barcode || '-'}
                          </div>
                        </div>

                        {/* Bottom Row: Price, Stock Badge, & Plus Button */}
                        <div className="mt-2.5 pt-2 border-t border-[#F1F4F5] flex items-center justify-between gap-1">
                          <div>
                            <div className="text-[10px] font-semibold text-[#879197]">
                              {isOutOfStock ? (
                                <span className="text-rose-600 font-bold">Habis</span>
                              ) : isLow ? (
                                <span className="text-amber-600 font-bold">Low Stock: {stock}</span>
                              ) : (
                                <span className="text-emerald-700 font-bold">In Stock: {stock}</span>
                              )}
                            </div>
                            <div className="font-mono text-xs font-extrabold text-[#167A93]">
                              {formatRupiah(p.selling_price)}
                            </div>
                          </div>

                          {/* Quick Add '+' Button */}
                          <button
                            type="button"
                            disabled={isOutOfStock}
                            onClick={(e) => {
                              e.stopPropagation();
                              if (!isOutOfStock) handleAddToCart(p);
                            }}
                            className="w-7 h-7 rounded-xl bg-[#E5F6FA] group-hover:bg-[#167A93] text-[#167A93] group-hover:text-white flex items-center justify-center font-bold text-sm transition-all duration-200 shadow-2xs disabled:opacity-30 disabled:cursor-not-allowed"
                            title="Tambah ke keranjang"
                          >
                            +
                          </button>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* ========================================================
            RIGHT COLUMN (4-5 COLS): CHECKOUT CART & PAYMENT PANEL
           ======================================================== */}
        <div className="lg:col-span-5 xl:col-span-4 bg-white border border-[#E7ECEE] rounded-2xl shadow-sm overflow-hidden sticky top-4 flex flex-col">
          {/* Customer Selection Card (Matching Reference UI) */}
          <div className="p-3.5 bg-[#F8FAFA] border-b border-[#E7ECEE]">
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs font-bold text-[#20282D]">Customer</span>
              <button
                type="button"
                onClick={() => setIsEditingCustomer(!isEditingCustomer)}
                className="text-[11px] font-bold text-[#167A93] hover:underline cursor-pointer"
              >
                {isEditingCustomer ? 'Selesai' : '+ Add New'}
              </button>
            </div>

            {isEditingCustomer ? (
              <div className="flex items-center gap-2">
                <input
                  ref={customerInputRef}
                  type="text"
                  value={customerName}
                  onChange={(e) => setCustomerName(e.target.value)}
                  placeholder="Nama Pelanggan..."
                  className="flex-1 px-3 py-1.5 bg-white border border-[#55BFD8] rounded-xl text-xs font-semibold text-[#20282D] focus:outline-none"
                />
                <button
                  type="button"
                  onClick={() => setIsEditingCustomer(false)}
                  className="px-2.5 py-1.5 bg-[#167A93] text-white text-xs font-bold rounded-xl cursor-pointer"
                >
                  OK
                </button>
              </div>
            ) : (
              <div
                onClick={() => setIsEditingCustomer(true)}
                className="p-2.5 bg-white border border-[#E7ECEE] hover:border-[#55BFD8] rounded-xl flex items-center justify-between cursor-pointer transition shadow-2xs"
              >
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-full bg-[#E5F6FA] text-[#167A93] flex items-center justify-center font-bold text-xs">
                    👤
                  </div>
                  <div>
                    <div className="text-xs font-bold text-[#20282D] leading-tight">
                      {customerName || 'Walk-in Customer'}
                    </div>
                    <div className="text-[10px] text-[#879197]">No loyalty points</div>
                  </div>
                </div>
                <span className="text-[#879197] text-xs">▼</span>
              </div>
            )}
          </div>

          {/* Cart Header */}
          <div className="px-3.5 py-2.5 bg-white border-b border-[#E7ECEE] flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="font-bold text-xs text-[#20282D]">Cart Items</span>
              <span className="px-2 py-0.5 bg-[#E5F6FA] text-[#167A93] font-bold text-[11px] rounded-full">
                {cart.length}
              </span>
            </div>
            {cart.length > 0 && (
              <button
                type="button"
                onClick={handleClearCart}
                className="text-xs font-semibold text-rose-500 hover:text-rose-700 hover:bg-rose-50 px-2 py-0.5 rounded-lg transition cursor-pointer"
              >
                Clear All
              </button>
            )}
          </div>

          {/* Cart Items List */}
          <div className="p-3 max-h-64 overflow-y-auto space-y-2.5 flex-1 scrollbar-thin">
            {cart.length === 0 ? (
              <div className="py-12 text-center text-xs text-[#879197]">
                <div className="w-12 h-12 mx-auto mb-2 rounded-2xl bg-[#F1F4F5] border border-[#E7ECEE] flex items-center justify-center text-xl">
                  🛒
                </div>
                <p className="font-bold text-[#20282D]">Keranjang Belanja Masih Kosong</p>
                <span className="text-[11px] text-[#A6AFB3] mt-0.5 block">
                  Klik kartu produk di katalog untuk menambahkan ke keranjang.
                </span>
              </div>
            ) : (
              cart.map((item) => {
                const availableStock = Number(item.product.stock) || 0;
                const isMax = Number(item.quantity) >= availableStock;

                return (
                  <div
                    key={item.product.id}
                    className="p-2.5 bg-white border border-[#E7ECEE] hover:border-[#55BFD8] rounded-xl transition flex items-center gap-3 shadow-2xs"
                  >
                    <CartThumbnail product={item.product} />

                    <div className="flex-1 min-w-0">
                      <div className="flex items-start justify-between gap-1">
                        <div>
                          <h4 className="text-xs font-bold text-[#20282D] truncate leading-tight">
                            {item.product.name}
                          </h4>
                          <span className="text-[10px] font-mono text-[#879197]">
                            SKU: {item.product.sku || '-'}
                          </span>
                        </div>
                        <div className="text-right font-mono text-xs font-extrabold text-[#167A93]">
                          {formatRupiah(item.subtotal)}
                        </div>
                      </div>

                      {/* Stepper & Delete Button */}
                      <div className="flex items-center justify-between mt-2 pt-1 border-t border-[#F8FAFA]">
                        <div className="inline-flex items-center bg-[#F8FAFA] border border-[#E7ECEE] rounded-lg p-0.5">
                          <button
                            type="button"
                            onClick={() => updateCartQty(item.product.id, -1)}
                            className="w-6 h-6 flex items-center justify-center font-bold text-xs text-[#879197] hover:text-rose-600 hover:bg-rose-50 rounded-md transition cursor-pointer"
                          >
                            -
                          </button>
                          <span className="w-8 text-center font-mono font-bold text-xs text-[#20282D]">
                            {item.quantity}
                          </span>
                          <button
                            type="button"
                            onClick={() => updateCartQty(item.product.id, 1)}
                            disabled={isMax}
                            className="w-6 h-6 flex items-center justify-center font-bold text-xs text-[#879197] hover:text-[#167A93] hover:bg-[#E5F6FA] rounded-md transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                          >
                            +
                          </button>
                        </div>

                        <button
                          type="button"
                          onClick={() => removeCartItem(item.product.id)}
                          className="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition cursor-pointer"
                          title="Hapus dari keranjang"
                        >
                          🗑️
                        </button>
                      </div>
                    </div>
                  </div>
                );
              })
            )}
          </div>

          {/* Price Summary Breakdown */}
          <div className="p-3.5 bg-[#F8FAFA] border-t border-[#E7ECEE] space-y-2 text-xs">
            <div className="flex justify-between items-center text-[#879197]">
              <span>Subtotal ({totalItemCount} unit)</span>
              <span className="font-mono font-semibold text-[#20282D]">{formatRupiah(rawSubtotal)}</span>
            </div>

            {taxRate > 0 && (
              <div className="flex justify-between items-center text-[#879197]">
                <span>PPN / Pajak ({taxRate}%)</span>
                <span className="font-mono font-semibold text-[#20282D]">+{formatRupiah(taxAmount)}</span>
              </div>
            )}

            {discountAmount > 0 && (
              <div className="flex justify-between items-center text-emerald-600 font-semibold">
                <span>Discount</span>
                <span className="font-mono">-{formatRupiah(discountAmount)}</span>
              </div>
            )}

            <div className="pt-2 border-t border-[#E7ECEE] flex justify-between items-baseline">
              <span className="font-bold text-[#20282D] text-sm">Total</span>
              <span className="font-mono font-black text-xl text-[#167A93]">
                {formatRupiah(grandTotal)}
              </span>
            </div>

            {/* Apply Discount / Coupon Accordion */}
            <div className="pt-1">
              {!showDiscountInput ? (
                <button
                  type="button"
                  onClick={() => setShowDiscountInput(true)}
                  className="w-full py-1.5 text-center text-xs font-bold text-[#167A93] bg-[#E5F6FA] hover:bg-[#D5F2F7] rounded-xl transition cursor-pointer flex items-center justify-center gap-1.5"
                >
                  <span>🏷️</span>
                  <span>Apply Discount / Coupon</span>
                </button>
              ) : (
                <div className="p-2 bg-white rounded-xl border border-[#D5E3E7] space-y-1.5">
                  <div className="flex items-center justify-between text-[11px] font-bold text-[#20282D]">
                    <span>Potongan Diskon (Rp):</span>
                    <button
                      type="button"
                      onClick={() => setShowDiscountInput(false)}
                      className="text-gray-400 hover:text-gray-700"
                    >
                      ✕
                    </button>
                  </div>
                  <input
                    type="number"
                    min="0"
                    step="500"
                    value={customDiscount || ''}
                    onChange={(e) => setCustomDiscount(Math.max(0, Number(e.target.value) || 0))}
                    placeholder="0"
                    className="w-full px-2.5 py-1.5 bg-[#F8FAFA] border border-[#E7ECEE] rounded-lg text-xs font-mono font-bold text-right focus:outline-none focus:border-[#55BFD8]"
                  />
                </div>
              )}
            </div>
          </div>

          {/* 2x2 Payment Action Buttons Grid (Matching Reference UI) */}
          <div className="p-3.5 bg-white border-t border-[#E7ECEE] space-y-2">
            <div className="grid grid-cols-2 gap-2">
              {/* Button 1: QRIS / Card (Primary Cyan) */}
              <button
                type="button"
                disabled={cart.length === 0 || isSubmitting}
                onClick={() => setActiveModal('qris')}
                className="py-2.5 px-2 bg-[#55BFD8] hover:bg-[#37AFCB] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed active:scale-95"
                title="Bayar QRIS Dinamis (F10)"
              >
                <span>📱</span>
                <span>QRIS / Digital</span>
              </button>

              {/* Button 2: Cash (Green Accent) */}
              <button
                type="button"
                disabled={cart.length === 0 || isSubmitting}
                onClick={() => setActiveModal('cash')}
                className="py-2.5 px-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed active:scale-95"
                title="Bayar Tunai / Cash (F9)"
              >
                <span>💵</span>
                <span>Cash</span>
              </button>

              {/* Button 3: Bank Transfer (Slate / Charcoal) */}
              <button
                type="button"
                disabled={cart.length === 0 || isSubmitting}
                onClick={() => setActiveModal('transfer')}
                className="py-2.5 px-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed active:scale-95"
                title="Transfer Bank Manual (F11)"
              >
                <span>🏦</span>
                <span>Bank Transfer</span>
              </button>

              {/* Button 4: History / Struk (Outline) */}
              <button
                type="button"
                onClick={() => setActiveModal('history')}
                className="py-2.5 px-2 bg-[#F8FAFA] hover:bg-[#E5F6FA] text-[#20282D] hover:text-[#167A93] border border-[#E7ECEE] font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer active:scale-95"
                title="Lihat Riwayat & Cetak Ulang Struk"
              >
                <span>📋</span>
                <span>Riwayat Struk</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* ========================================================
          MODALS INTEGRATION
         ======================================================== */}
      {/* 1. Cash Payment Modal */}
      {activeModal === 'cash' && (
        <CashPaymentModal
          grandTotal={grandTotal}
          isSubmitting={isSubmitting}
          onClose={() => setActiveModal(null)}
          onConfirm={({ payment_method, paid_amount }) => {
            setActiveModal(null);
            executeCheckout({ payment_method, paid_amount });
          }}
        />
      )}

      {/* 2. QRIS Dynamic Payment Modal */}
      {activeModal === 'qris' && (
        <QrisPaymentModal
          grandTotal={grandTotal}
          customerName={customerName}
          itemsCount={cart.length}
          onClose={() => setActiveModal(null)}
          onPaymentSuccess={({ payment_method, paid_amount, order_id }) => {
            setActiveModal(null);
            executeCheckout({ payment_method, paid_amount, order_id });
          }}
        />
      )}

      {/* 3. Bank Transfer Modal */}
      {activeModal === 'transfer' && (
        <BankTransferModal
          grandTotal={grandTotal}
          isSubmitting={isSubmitting}
          onClose={() => setActiveModal(null)}
          onConfirm={({ payment_method, paid_amount, notes }) => {
            setActiveModal(null);
            executeCheckout({ payment_method, paid_amount, notes });
          }}
        />
      )}

      {/* 4. Thermal Receipt Modal */}
      {activeModal === 'receipt' && receiptData && (
        <ReceiptModal
          receipt={receiptData}
          onClose={() => setActiveModal(null)}
          onNewTransaction={() => {
            setActiveModal(null);
            searchInputRef.current?.focus();
          }}
        />
      )}

      {/* 5. Sales History Modal */}
      {activeModal === 'history' && (
        <SalesHistoryModal
          user={user}
          onClose={() => setActiveModal(null)}
          onSelectReceipt={(receipt) => {
            setReceiptData(receipt);
            setActiveModal('receipt');
          }}
        />
      )}

      {/* 6. Shortcuts Help Modal */}
      {activeModal === 'shortcuts' && (
        <PosShortcutsModal onClose={() => setActiveModal(null)} />
      )}
    </div>
  );
}

export default PosPage;
