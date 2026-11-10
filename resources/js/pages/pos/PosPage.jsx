import React, { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import useAuth from '../../hooks/useAuth';
import posService from '../../services/posService';
import categoryService from '../../services/categoryService';
import { formatRupiah } from '../../utils/currency';
import { IconPos } from '../../components/common/Icons';

/**
 * Komponen Thumbnail Produk dengan Fallback Placeholder Elegan
 */
function ProductImage({ product, className = '', imgClassName = '', aspect = 'aspect-[4/3]' }) {
  const [hasError, setHasError] = useState(false);

  const imageUrl = useMemo(() => {
    const raw = product?.image_url || product?.image || product?.photo || product?.photo_url || product?.thumbnail;
    if (!raw) return null;
    if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:') || raw.startsWith('/')) {
      return raw;
    }
    return `/storage/${raw}`;
  }, [product]);

  // Tema warna placeholder dinamis dan elegan berdasarkan nama/kategori
  const placeholderTheme = useMemo(() => {
    const themes = [
      { bg: 'from-[#EAF8FA] via-[#F4FBFC] to-[#D7F1F6]', text: 'text-[#167A93]', ring: 'border-[#BCE8EF]', badgeBg: 'bg-[#167A93]/10' },
      { bg: 'from-[#F0FDF4] via-[#F6FEF9] to-[#DCFCE7]', text: 'text-[#15803D]', ring: 'border-[#BBF7D0]', badgeBg: 'bg-[#15803D]/10' },
      { bg: 'from-[#FFF7ED] via-[#FFFAF5] to-[#FFEDD5]', text: 'text-[#C2410C]', ring: 'border-[#FED7AA]', badgeBg: 'bg-[#C2410C]/10' },
      { bg: 'from-[#F5F3FF] via-[#FAF8FF] to-[#EDE9FE]', text: 'text-[#7C3AED]', ring: 'border-[#DDD6FE]', badgeBg: 'bg-[#7C3AED]/10' },
      { bg: 'from-[#EFF6FF] via-[#F6F9FF] to-[#DBEAFE]', text: 'text-[#1D4ED8]', ring: 'border-[#BFDBFE]', badgeBg: 'bg-[#1D4ED8]/10' },
    ];
    const hash = ((product?.name || 'item').charCodeAt(0) + (Number(product?.category_id) || 1)) % themes.length;
    return themes[hash];
  }, [product]);

  if (imageUrl && !hasError) {
    return (
      <div className={`overflow-hidden relative bg-[#F4FBFC] ${aspect} ${className}`}>
        <img
          src={imageUrl}
          alt={product.name}
          onError={() => setHasError(true)}
          className={`w-full h-full object-cover transition-transform duration-300 ${imgClassName}`}
          loading="lazy"
        />
      </div>
    );
  }

  return (
    <div
      className={`overflow-hidden relative bg-gradient-to-br ${placeholderTheme.bg} flex flex-col items-center justify-center p-3 border-b ${placeholderTheme.ring} ${aspect} ${className}`}
    >
      {/* Ornamen latar belakang halus */}
      <div className="absolute -top-3 -right-3 w-16 h-16 rounded-full bg-white/40 blur-xs pointer-events-none" />
      <div className="absolute -bottom-3 -left-3 w-14 h-14 rounded-full bg-white/30 blur-xs pointer-events-none" />

      {/* Ikon Barang Toko Elegan */}
      <div
        className={`w-11 h-11 rounded-2xl bg-white/90 shadow-xs backdrop-blur-xs flex items-center justify-center ${placeholderTheme.text} transition-transform duration-300 group-hover:scale-110`}
      >
        <svg
          className="w-6 h-6"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.8"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
          <path d="m3.3 7 8.7 5 8.7-5" />
          <path d="M12 22V12" />
        </svg>
      </div>

      {/* Label Kategori Kecil */}
      <span
        className={`mt-2 text-[9px] font-bold uppercase tracking-wider ${placeholderTheme.text} px-2 py-0.5 rounded-full bg-white/80 backdrop-blur-xs max-w-[85%] truncate shadow-xs`}
      >
        {product?.category?.name || 'Produk'}
      </span>
    </div>
  );
}

/**
 * Komponen Thumbnail Kecil untuk Baris Item di Keranjang Belanja
 */
function CartItemThumbnail({ product }) {
  const [hasError, setHasError] = useState(false);

  const imageUrl = useMemo(() => {
    const raw = product?.image_url || product?.image || product?.photo || product?.photo_url || product?.thumbnail;
    if (!raw) return null;
    if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:') || raw.startsWith('/')) {
      return raw;
    }
    return `/storage/${raw}`;
  }, [product]);

  if (imageUrl && !hasError) {
    return (
      <div className="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-[#F4FBFC] border border-[#D7EBEF]">
        <img
          src={imageUrl}
          alt={product.name}
          onError={() => setHasError(true)}
          className="w-full h-full object-cover"
        />
      </div>
    );
  }

  return (
    <div className="w-12 h-12 rounded-xl shrink-0 bg-gradient-to-br from-[#EAF8FA] to-[#D5F2F7] border border-[#BBE7EF] flex flex-col items-center justify-center text-[#167A93]">
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

  // Master Data Produk & Kategori Toko
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  // Filter & Pencarian Kartu Barang
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const searchInputRef = useRef(null);
  const paidInputRef = useRef(null);

  // Cart / Daftar Belanja State
  const [cart, setCart] = useState([]);

  // Payment State
  const [paymentMethod, setPaymentMethod] = useState('cash'); // 'cash', 'qris', 'bank_transfer'
  const [paidAmount, setPaidAmount] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Toast Notification State
  const [toast, setToast] = useState(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 3500);
  };

  // 1. Fetch Otomatis Semua Barang Stok Toko & Kategori saat Halaman Dibuka (useEffect)
  const fetchAllData = useCallback(async () => {
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
      showToast('Gagal memuat data produk stok toko', 'error');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchAllData();
  }, [fetchAllData]);

  // Autofocus ke input pencarian saat halaman kasir dimuat
  useEffect(() => {
    if (searchInputRef.current) {
      searchInputRef.current.focus();
    }
  }, []);

  // Filter Kartu Produk secara Real-time berdasarkan Pencarian & Kategori
  const filteredProducts = useMemo(() => {
    return products.filter((p) => {
      const matchCategory = selectedCategory ? String(p.category_id) === String(selectedCategory) : true;
      if (!matchCategory) return false;

      if (!searchQuery.trim()) return true;
      const q = searchQuery.trim().toLowerCase();
      const name = (p.name || '').toLowerCase();
      const sku = (p.sku || '').toLowerCase();
      const barcode = (p.barcode || '').toLowerCase();

      return name.includes(q) || sku.includes(q) || barcode.includes(q);
    });
  }, [products, selectedCategory, searchQuery]);

  // Tambah Barang ke Keranjang Belanja saat Kartu Diklik
  const handleProductClick = (product) => {
    const availableStock = Number(product.stock) || 0;
    if (availableStock <= 0) {
      showToast(`Stok barang "${product.name}" telah habis!`, 'error');
      return;
    }

    setCart((prev) => {
      const existingIndex = prev.findIndex((item) => item.product.id === product.id);
      if (existingIndex > -1) {
        const existing = prev[existingIndex];
        const newQty = existing.quantity + 1;
        if (newQty > availableStock) {
          showToast(`Maksimal stok "${product.name}" tersedia: ${availableStock} ${product.unit?.symbol || 'pcs'}`, 'error');
          return prev;
        }
        const updated = [...prev];
        updated[existingIndex] = {
          ...existing,
          quantity: newQty,
          subtotal: newQty * existing.unit_price,
        };
        showToast(`+1 "${product.name}" (Total: ${newQty} ${product.unit?.symbol || 'pcs'})`);
        return updated;
      } else {
        const unitPrice = Number(product.selling_price) || 0;
        showToast(`✓ Ditambahkan: "${product.name}"`);
        return [
          ...prev,
          {
            product,
            quantity: 1,
            unit_price: unitPrice,
            subtotal: unitPrice,
          },
        ];
      }
    });
  };

  // Ubah Kuantitas Barang di Keranjang (Tombol +/-)
  const updateCartQty = (productId, delta) => {
    setCart((prev) =>
      prev
        .map((item) => {
          if (item.product.id === productId) {
            const availableStock = Number(item.product.stock) || 0;
            const newQty = (Number(item.quantity) || 0) + delta;

            if (newQty <= 0) return null; // Hapus jika kuantitas 0
            if (newQty > availableStock) {
              showToast(`Maksimal stok "${item.product.name}" tersedia: ${availableStock}`, 'error');
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

  // Ubah Kuantitas Barang (Ketik Manual)
  const setCartItemQty = (productId, rawVal) => {
    if (rawVal === '') {
      setCart((prev) =>
        prev.map((item) =>
          item.product.id === productId ? { ...item, quantity: '', subtotal: 0 } : item
        )
      );
      return;
    }

    const val = Number(rawVal);
    if (isNaN(val) || val < 1) return;

    setCart((prev) =>
      prev.map((item) => {
        if (item.product.id === productId) {
          const availableStock = Number(item.product.stock) || 0;
          let actualQty = val;
          if (val > availableStock) {
            showToast(`Maksimal stok "${item.product.name}" tersedia: ${availableStock}`, 'error');
            actualQty = availableStock;
          }
          return {
            ...item,
            quantity: actualQty,
            subtotal: actualQty * item.unit_price,
          };
        }
        return item;
      })
    );
  };

  // Hapus Baris Barang dari Keranjang
  const removeCartItem = (productId) => {
    setCart((prev) => prev.filter((item) => item.product.id !== productId));
    showToast('Barang dihapus dari daftar pesanan');
  };

  // Reset / Bersihkan Keranjang Belanja
  const clearCart = () => {
    if (cart.length === 0) return;
    setCart([]);
    setPaidAmount('');
    setPaymentMethod('cash');
    showToast('Daftar pesanan telah dibersihkan');
  };

  // Kalkulasi Total Transaksi
  const grandTotal = cart.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0);
  const totalItemCount = cart.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);

  const currentPaid = Number(paidAmount) || 0;
  const changeAmount = paymentMethod === 'cash' ? Math.max(0, currentPaid - grandTotal) : 0;
  const isPaidSufficient = paymentMethod !== 'cash' || (cart.length > 0 && currentPaid >= grandTotal);

  // Ubah Metode Pembayaran
  const handlePaymentMethodChange = (code) => {
    setPaymentMethod(code);
    if (code !== 'cash') {
      setPaidAmount(grandTotal);
    }
  };

  // Tombol Pecahan Uang Tunai Cepat
  const cashOptions = [
    { label: 'Uang Pas', value: grandTotal },
    { label: '10.000', value: 10000 },
    { label: '20.000', value: 20000 },
    { label: '50.000', value: 50000 },
    { label: '100.000', value: 100000 },
    { label: '200.000', value: 200000 },
  ];

  // Simpan Transaksi Penjualan, Potong Stok Toko, dan Catat Kartu Stok
  const handleCheckoutSubmit = async (e) => {
    if (e) e.preventDefault();

    if (cart.length === 0) {
      showToast('Daftar pesanan masih kosong! Silakan klik barang yang ingin dibeli.', 'error');
      return;
    }

    if (cart.some((item) => !item.quantity || Number(item.quantity) <= 0)) {
      showToast('Pastikan kuantitas (qty) setiap barang di keranjang valid.', 'error');
      return;
    }

    if (paymentMethod === 'cash' && currentPaid < grandTotal) {
      showToast('Nominal uang yang dibayarkan kurang dari Total Belanja!', 'error');
      if (paidInputRef.current) paidInputRef.current.focus();
      return;
    }

    setIsSubmitting(true);
    try {
      const payload = {
        customer_name: 'Pelanggan Umum',
        payment_method: paymentMethod,
        paid_amount: paymentMethod === 'cash' ? currentPaid : grandTotal,
        discount_amount: 0,
        tax_amount: 0,
        items: cart.map((item) => ({
          product_id: item.product.id,
          quantity: Number(item.quantity),
          unit_price: Number(item.unit_price),
          discount_amount: 0,
        })),
      };

      const res = await posService.checkout(payload);
      if (res.success) {
        showToast(
          `✓ Transaksi ${res.data?.transaction_number || ''} berhasil disimpan! Stok terpotong real-time.`,
          'success'
        );

        // Reset keranjang belanja & form kasir seketika
        setCart([]);
        setPaidAmount('');
        setPaymentMethod('cash');

        // Perbarui (refresh) angka sisa stok pada kartu barang secara real-time tanpa reload halaman
        const updatedProdRes = await posService.getPosProducts({ limit: 250 });
        if (updatedProdRes.success && updatedProdRes.data) {
          setProducts(updatedProdRes.data);
        }

        // Fokuskan kembali ke pencarian untuk pembeli berikutnya
        if (searchInputRef.current) {
          searchInputRef.current.focus();
        }
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Gagal memproses transaksi kasir';
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
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2 transition-all ${
            toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-700'
              : 'bg-emerald-50 border-emerald-200 text-emerald-700'
          }`}
        >
          <span className="text-sm">{toast.type === 'error' ? '⚠️' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      {/* Header Halaman Kasir Modern */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-1">
        <div>
          <h1 className="text-xl font-bold text-[#16323B] flex items-center gap-2">
            <span className="p-1.5 bg-[#EAF8FA] text-[#167A93] rounded-lg border border-[#38BFE0]/30 shadow-2xs">
              <IconPos className="w-5 h-5" />
            </span>
            Penjualan (Kasir POS)
          </h1>
          <p className="text-xs text-[#577782] mt-0.5">
            Petugas Kasir: <strong className="text-[#16323B]">{user?.name || 'Kasir'}</strong> &bull; Klik produk pada katalog barang untuk memasukkan ke keranjang pesanan.
          </p>
        </div>

        {/* Info Cepat Status Kasir */}
        <div className="flex items-center gap-2">
          <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-[#EAF8FA] text-[#167A93] border border-[#38BFE0]/30 rounded-xl text-xs font-semibold">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Kasir Aktif ({products.length} Produk Siap Jual)
          </span>
        </div>
      </div>

      {/* Grid Utama POS Modern (Area Produk Stok Toko & Area Keranjang/Pembayaran) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        {/* ========================================================
            BAGIAN KIRI (7-8 COLS) — KATALOG PRODUK GRID INTERAKTIF
           ======================================================== */}
        <div className="lg:col-span-7 xl:col-span-8 space-y-3.5">
          {/* Baris Pencarian Minimalis & Filter Kategori Modern */}
          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-3.5 shadow-2xs space-y-3">
            {/* Input Pencarian Cepat & Bersih */}
            <div className="relative flex items-center">
              <span className="absolute left-3.5 text-[#167A93] pointer-events-none">
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
                placeholder="Cari nama barang, kode SKU, atau barcode..."
                className="w-full pl-10 pr-9 py-2.5 bg-[#F8FDFE] border border-[#D7EBEF] focus:border-[#27C5E5] focus:bg-white rounded-xl text-xs font-semibold text-[#16323B] placeholder:text-[#8FA8B0] placeholder:font-normal focus:outline-none transition shadow-2xs"
              />
              {searchQuery && (
                <button
                  type="button"
                  onClick={() => setSearchQuery('')}
                  className="absolute right-3 text-xs text-[#8FA8B0] hover:text-[#16323B] font-bold p-1 rounded-full hover:bg-gray-100 cursor-pointer transition"
                  title="Hapus pencarian"
                >
                  ✕
                </button>
              )}
            </div>

            {/* Tab Kategori Minimalis & Responsif */}
            <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
              <button
                type="button"
                onClick={() => setSelectedCategory('')}
                className={`px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-1.5 ${
                  selectedCategory === ''
                    ? 'bg-[#167A93] text-white shadow-2xs'
                    : 'bg-[#F0F9FB] text-[#577782] hover:bg-[#EAF8FA] hover:text-[#16323B] border border-transparent hover:border-[#D7EBEF]'
                }`}
              >
                <span>Semua</span>
                <span
                  className={`px-1.5 py-0.2 rounded-full text-[10px] ${
                    selectedCategory === '' ? 'bg-white/20 text-white' : 'bg-[#E3F2F5] text-[#577782]'
                  }`}
                >
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
                    className={`px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-1.5 ${
                      isSelected
                        ? 'bg-[#167A93] text-white shadow-2xs'
                        : 'bg-[#F0F9FB] text-[#577782] hover:bg-[#EAF8FA] hover:text-[#16323B] border border-transparent hover:border-[#D7EBEF]'
                    }`}
                  >
                    <span>{c.name}</span>
                    <span
                      className={`px-1.5 py-0.2 rounded-full text-[10px] ${
                        isSelected ? 'bg-white/20 text-white' : 'bg-[#E3F2F5] text-[#577782]'
                      }`}
                    >
                      {count}
                    </span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Grid Kartu Produk Interaktif Ala Aplikasi POS Komersial */}
          <div className="min-h-[420px]">
            {isLoading ? (
              <div className="p-16 text-center text-xs text-[#577782] bg-white rounded-2xl border border-[#D7EBEF] shadow-2xs">
                <div className="animate-spin text-3xl mb-3 text-[#167A93]">🔄</div>
                <p className="font-semibold text-sm text-[#16323B]">Memuat katalog produk kasir...</p>
                <p className="text-[#8FA8B0] mt-1 text-xs">Menyiapkan data stok toko secara real-time</p>
              </div>
            ) : filteredProducts.length === 0 ? (
              <div className="p-16 text-center text-xs text-[#577782] bg-white rounded-2xl border border-[#D7EBEF] shadow-2xs">
                <div className="text-4xl mb-3">📦</div>
                <p className="font-bold text-sm text-[#16323B]">Tidak ada produk yang cocok</p>
                <p className="text-[#8FA8B0] mt-1 text-xs">
                  {searchQuery ? `Pencarian "${searchQuery}" tidak menemukan barang.` : 'Kategori ini belum memiliki barang aktif.'}
                </p>
              </div>
            ) : (
              <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5">
                {filteredProducts.map((p) => {
                  const stock = Number(p.stock) || 0;
                  const isOutOfStock = stock <= 0;
                  const isLow = stock > 0 && stock <= Number(p.min_stock || 5);

                  // Cek apakah produk ini sudah ada di keranjang belanja
                  const inCartItem = cart.find((item) => item.product.id === p.id);

                  return (
                    <div
                      key={p.id}
                      onClick={() => !isOutOfStock && handleProductClick(p)}
                      className={`group relative bg-white border rounded-2xl overflow-hidden flex flex-col justify-between transition-all duration-200 select-none ${
                        isOutOfStock
                          ? 'opacity-60 border-gray-200 bg-gray-50/70 cursor-not-allowed shadow-2xs'
                          : 'border-[#D7EBEF] hover:border-[#27C5E5] hover:shadow-lg hover:-translate-y-1 cursor-pointer active:scale-[0.98]'
                      }`}
                    >
                      {/* Indikator Floating Kuantitas di Keranjang */}
                      {inCartItem && (
                        <span className="absolute top-2.5 right-2.5 z-10 px-2 py-0.5 bg-[#167A93] text-white font-mono font-bold text-[11px] rounded-full shadow-md border-2 border-white flex items-center gap-1">
                          <span>✓</span>
                          <span>{inCartItem.quantity}x</span>
                        </span>
                      )}

                      {/* Gambar Produk / Placeholder di Bagian Atas Kartu */}
                      <div className="relative overflow-hidden group-hover:opacity-95 transition-opacity">
                        <ProductImage
                          product={p}
                          aspect="aspect-[4/3]"
                          imgClassName="group-hover:scale-105 transition-transform duration-300"
                        />

                        {/* Overlay jika Stok Habis */}
                        {isOutOfStock && (
                          <div className="absolute inset-0 bg-black/45 backdrop-blur-[1px] flex items-center justify-center">
                            <span className="px-2.5 py-1 bg-rose-600 text-white font-bold text-[10px] rounded-md uppercase tracking-wider shadow-sm">
                              Stok Habis
                            </span>
                          </div>
                        )}
                      </div>

                      {/* Detail Teks di Bawah Gambar */}
                      <div className="p-3 flex flex-col justify-between flex-1">
                        <div>
                          {/* Baris Kategori & SKU */}
                          <div className="flex items-center justify-between gap-1 text-[10px] text-[#577782] mb-1">
                            <span className="font-semibold text-[#167A93] bg-[#EAF8FA] px-1.5 py-0.5 rounded truncate max-w-[100px]">
                              {p.category?.name || 'Umum'}
                            </span>
                            <span className="font-mono text-[#8FA8B0] truncate text-[9px]">
                              {p.sku || p.barcode || '-'}
                            </span>
                          </div>

                          {/* Nama Barang */}
                          <h3 className="text-xs font-bold text-[#16323B] line-clamp-2 leading-snug group-hover:text-[#167A93] transition-colors min-h-[32px]">
                            {p.name}
                          </h3>

                          {/* Deskripsi Singkat jika Ada */}
                          {p.description && (
                            <p className="text-[10px] text-[#8FA8B0] line-clamp-1 mt-0.5">
                              {p.description}
                            </p>
                          )}
                        </div>

                        {/* Baris Harga & Sisa Stok */}
                        <div className="mt-2.5 pt-2 border-t border-[#F0F9FB] flex items-center justify-between gap-1.5">
                          <div className="font-mono text-xs sm:text-[13px] font-black text-[#167A93]">
                            {formatRupiah(p.selling_price)}
                          </div>

                          <span
                            className={`px-2 py-0.5 rounded-md font-bold text-[10px] shrink-0 ${
                              isOutOfStock
                                ? 'bg-rose-50 text-rose-600 border border-rose-200'
                                : isLow
                                ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            }`}
                          >
                            {isOutOfStock
                              ? 'Habis'
                              : isLow
                              ? `Sisa ${stock}`
                              : `Stok: ${stock}`}
                          </span>
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
            BAGIAN KANAN (4-5 COLS) — PANEL DAFTAR PESANAN & PEMBAYARAN
           ======================================================== */}
        <div className="lg:col-span-5 xl:col-span-4 bg-white border border-[#D7EBEF] rounded-2xl shadow-sm overflow-hidden sticky top-4 flex flex-col">
          {/* Header Panel Belanja */}
          <div className="p-3.5 bg-[#F8FDFE] border-b border-[#EAF4F6] flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="font-bold text-xs uppercase tracking-wider text-[#16323B]">
                Daftar Pesanan Belanja
              </span>
              <span className="px-2 py-0.5 bg-[#EAF8FA] text-[#167A93] font-bold text-xs rounded-full border border-[#38BFE0]/30">
                {cart.length} item ({totalItemCount} unit)
              </span>
            </div>
            {cart.length > 0 && (
              <button
                type="button"
                onClick={clearCart}
                className="text-xs font-semibold text-rose-500 hover:text-rose-700 hover:bg-rose-50 px-2 py-0.5 rounded-lg transition cursor-pointer"
                title="Bersihkan seluruh pesanan"
              >
                Bersihkan
              </button>
            )}
          </div>

          {/* Header Grand Total Kasir Digital */}
          <div className="p-4 bg-gradient-to-br from-[#10262E] via-[#16323B] to-[#0A1A20] text-white border-b-2 border-[#27C5E5]">
            <div className="flex items-center justify-between text-[#8FA8B0] text-[10px] font-semibold uppercase tracking-wider mb-0.5">
              <span>Total Tagihan Belanja</span>
              <span className="font-mono text-[#27C5E5]">{cart.length} Jenis Barang</span>
            </div>
            <div className="text-3xl font-extrabold font-mono text-[#27C5E5] tracking-tight">
              {formatRupiah(grandTotal)}
            </div>
          </div>

          {/* Daftar Item di Keranjang (Dengan Thumbnail, Nama, Stepper, dan Subtotal) */}
          <div className="p-3 max-h-64 overflow-y-auto space-y-2.5 flex-1 scrollbar-thin">
            {cart.length === 0 ? (
              <div className="py-12 text-center text-xs text-[#577782]">
                <div className="w-12 h-12 mx-auto mb-2 rounded-2xl bg-[#F0F9FB] border border-[#D7EBEF] flex items-center justify-center text-[#167A93] text-xl">
                  🛒
                </div>
                <p className="font-bold text-[#16323B]">Keranjang belanja masih kosong</p>
                <span className="text-[11px] text-[#8FA8B0] mt-0.5 block">
                  Klik kartu barang di sebelah kiri untuk memasukkan pesanan.
                </span>
              </div>
            ) : (
              cart.map((item) => {
                const availableStock = Number(item.product.stock) || 0;
                const isMax = Number(item.quantity) >= availableStock;

                return (
                  <div
                    key={item.product.id}
                    className="p-2.5 bg-white border border-[#EAF4F6] hover:border-[#D7EBEF] rounded-xl transition flex items-center gap-3 shadow-2xs"
                  >
                    {/* Thumbnail Kecil Produk */}
                    <CartItemThumbnail product={item.product} />

                    {/* Info Barang & Stepper Qty */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-start justify-between gap-1">
                        <h4 className="text-xs font-bold text-[#16323B] truncate leading-tight">
                          {item.product.name}
                        </h4>
                        <button
                          type="button"
                          onClick={() => removeCartItem(item.product.id)}
                          className="text-[#8FA8B0] hover:text-rose-600 hover:bg-rose-50 p-1 rounded-md transition cursor-pointer shrink-0"
                          title="Hapus barang"
                        >
                          ✕
                        </button>
                      </div>

                      <div className="text-[11px] font-mono text-[#577782] mt-0.5">
                        {formatRupiah(item.unit_price)} <span className="text-[#8FA8B0]">/ {item.product.unit?.symbol || 'pcs'}</span>
                      </div>

                      {/* Stepper Kuantitas (+ / -) & Subtotal */}
                      <div className="flex items-center justify-between mt-2 pt-1.5 border-t border-[#F8FDFE]">
                        <div className="inline-flex items-center bg-[#F8FDFE] border border-[#D7EBEF] rounded-lg p-0.5">
                          <button
                            type="button"
                            onClick={() => updateCartQty(item.product.id, -1)}
                            className="w-6 h-6 flex items-center justify-center font-bold text-xs text-[#577782] hover:text-rose-600 hover:bg-rose-50 rounded-md transition cursor-pointer"
                            title="Kurangi 1"
                          >
                            -
                          </button>
                          <input
                            type="number"
                            min="1"
                            max={availableStock}
                            value={item.quantity}
                            onChange={(e) => setCartItemQty(item.product.id, e.target.value)}
                            className="w-10 h-6 text-center font-mono font-bold text-xs text-[#16323B] bg-transparent focus:outline-none focus:bg-white rounded px-0.5"
                          />
                          <button
                            type="button"
                            onClick={() => updateCartQty(item.product.id, 1)}
                            disabled={isMax}
                            className="w-6 h-6 flex items-center justify-center font-bold text-xs text-[#577782] hover:text-[#167A93] hover:bg-[#EAF8FA] rounded-md transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                            title="Tambah 1"
                          >
                            +
                          </button>
                        </div>

                        <div className="text-right font-mono text-xs font-bold text-[#167A93]">
                          {formatRupiah(item.subtotal)}
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })
            )}
          </div>

          {/* Rincian Pembayaran Bersih & Sejajar (Subtotal, Diskon, Pajak, Grand Total) */}
          <div className="p-3.5 bg-[#F8FDFE] border-t border-[#EAF4F6] space-y-2">
            <div className="space-y-1.5 text-xs">
              <div className="flex justify-between items-center text-[#577782]">
                <span>Subtotal ({totalItemCount} unit)</span>
                <span className="font-mono font-semibold text-[#16323B]">{formatRupiah(grandTotal)}</span>
              </div>
              <div className="flex justify-between items-center text-[#577782]">
                <span>Diskon Transaksi</span>
                <span className="font-mono font-semibold text-emerald-600">- Rp 0</span>
              </div>
              <div className="flex justify-between items-center text-[#577782]">
                <span>Pajak Toko (PPN 0%)</span>
                <span className="font-mono font-semibold text-[#16323B]">Rp 0</span>
              </div>
              <div className="pt-2 border-t border-[#D7EBEF] flex justify-between items-baseline">
                <span className="font-bold text-[#16323B] uppercase text-[11px] tracking-wider">
                  Grand Total
                </span>
                <span className="font-mono font-black text-lg text-[#167A93]">
                  {formatRupiah(grandTotal)}
                </span>
              </div>
            </div>
          </div>

          {/* Bagian Input Pembayaran & Tombol Eksekusi Kasir */}
          <form onSubmit={handleCheckoutSubmit} className="p-3.5 bg-white border-t border-[#EAF4F6] space-y-3">
            {/* Pilihan Metode Pembayaran (Tunai / QRIS / Transfer) */}
            <div>
              <label className="block text-[11px] font-bold text-[#16323B] uppercase tracking-wider mb-1.5">
                Metode Pembayaran
              </label>
              <div className="grid grid-cols-3 gap-1.5">
                {[
                  { id: 'cash', label: 'Tunai', icon: '💵' },
                  { id: 'qris', label: 'QRIS', icon: '📱' },
                  { id: 'bank_transfer', label: 'Transfer', icon: '🏦' },
                ].map((m) => (
                  <button
                    key={m.id}
                    type="button"
                    onClick={() => handlePaymentMethodChange(m.id)}
                    className={`py-2 px-1 rounded-xl text-xs font-bold border transition-all flex items-center justify-center gap-1 cursor-pointer ${
                      paymentMethod === m.id
                        ? 'bg-[#167A93] text-white border-[#167A93] shadow-2xs'
                        : 'bg-white text-[#577782] border-[#D7EBEF] hover:bg-[#F0F9FB]'
                    }`}
                  >
                    <span>{m.icon}</span>
                    <span>{m.label}</span>
                  </button>
                ))}
              </div>
            </div>

            {/* Input Bayar & Kembalian Real-time (Saat Tunai) */}
            {paymentMethod === 'cash' ? (
              <div className="space-y-2">
                <div>
                  <div className="flex justify-between items-center text-[11px] font-semibold text-[#16323B] mb-1">
                    <span>Uang Diterima:</span>
                  </div>
                  <div className="relative">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-[#577782]">
                      Rp
                    </span>
                    <input
                      ref={paidInputRef}
                      type="number"
                      min="0"
                      value={paidAmount}
                      onChange={(e) => setPaidAmount(e.target.value)}
                      placeholder="0"
                      className="w-full pl-9 pr-3 py-2 bg-[#F8FDFE] border border-[#27C5E5] rounded-xl text-sm font-mono font-bold text-[#16323B] text-right focus:outline-none focus:ring-1 focus:ring-[#27C5E5] focus:bg-white"
                    />
                  </div>
                </div>

                {/* Tombol Pecahan Uang Cepat */}
                <div className="grid grid-cols-3 gap-1.5">
                  {cashOptions.map((opt, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => setPaidAmount(opt.value)}
                      className="py-1 px-1 bg-white hover:bg-[#EAF8FA] border border-[#D7EBEF] rounded-lg text-[10px] font-mono font-semibold text-[#167A93] transition cursor-pointer"
                    >
                      {opt.label}
                    </button>
                  ))}
                </div>

                {/* Kembalian / Kekurangan Real-time */}
                <div
                  className={`p-2.5 rounded-xl border flex items-center justify-between text-xs transition ${
                    currentPaid >= grandTotal
                      ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                      : 'bg-rose-50 border-rose-200 text-rose-700'
                  }`}
                >
                  <span className="font-semibold text-[11px]">
                    {currentPaid >= grandTotal ? 'Kembalian:' : 'Kurang:'}
                  </span>
                  <span className="font-mono font-bold text-sm">
                    {currentPaid >= grandTotal
                      ? formatRupiah(changeAmount)
                      : formatRupiah(grandTotal - currentPaid)}
                  </span>
                </div>
              </div>
            ) : (
              <div className="p-3 bg-[#F0F9FB] border border-[#D7EBEF] rounded-xl text-[11px] text-[#167A93] space-y-1">
                <div className="font-bold flex items-center gap-1.5">
                  <span>💳</span>
                  <span>Non-Tunai ({paymentMethod === 'qris' ? 'QRIS Digital' : 'Transfer Bank'})</span>
                </div>
                <p className="text-[#577782]">
                  Nominal pembayaran pas: <strong className="font-mono text-[#16323B]">{formatRupiah(grandTotal)}</strong>
                </p>
              </div>
            )}

            {/* Tombol Aksi: Bayar / Simpan & Reset */}
            <div className="space-y-2 pt-1">
              <button
                type="submit"
                disabled={cart.length === 0 || !isPaidSufficient || isSubmitting}
                className="w-full py-2.5 bg-[#167A93] hover:bg-[#116276] text-white font-bold text-xs sm:text-sm rounded-xl shadow-md transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98]"
              >
                {isSubmitting ? (
                  <span>Menyimpan Transaksi...</span>
                ) : (
                  <span>✓ Bayar / Simpan Transaksi</span>
                )}
              </button>

              <button
                type="button"
                onClick={clearCart}
                disabled={cart.length === 0 || isSubmitting}
                className="w-full py-1.5 bg-white hover:bg-rose-50 text-[#577782] hover:text-rose-600 font-semibold text-xs rounded-xl border border-[#D7EBEF] hover:border-rose-200 transition disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
              >
                Reset / Bersihkan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}

export default PosPage;
