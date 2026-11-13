import React, { useState, useEffect, useCallback } from 'react';
import {
  Handshake,
  Plus,
  FileText,
  FileSpreadsheet,
  Search,
  Filter,
  RefreshCw,
  Package,
  ShoppingCart,
  TrendingUp,
  Clock,
  CheckCircle2,
  AlertCircle,
  Building2,
  Edit2,
  Trash2,
  ArrowDownLeft,
  ArrowUpRight,
  ChevronLeft,
  ChevronRight,
  Coins,
} from 'lucide-react';
import PageHeader from '../../components/ui/PageHeader';
import { consignmentService } from '../../services/consignmentService';
import categoryService from '../../services/categoryService';
import unitService from '../../services/unitService';
import { formatRupiah } from '../../utils/currency';

// Modals
import ConsignorModal from './ConsignorModal';
import ConsignmentProductModal from './ConsignmentProductModal';
import ConsignmentStockModal from './ConsignmentStockModal';
import ConsignmentSettlementModal from './ConsignmentSettlementModal';

export default function ConsignmentPage() {
  // Sub-tabs: 'sales', 'products', 'consignors'
  const [activeTab, setActiveTab] = useState('sales');

  // Summary Metrics State
  const [summary, setSummary] = useState({
    total_active_sku: 0,
    total_active_stock: 0,
    total_sold_qty: 0,
    total_sales_amount: 0,
    total_store_commission: 0,
    unsettled_payable: 0,
    settled_paid: 0,
    total_consignors: 0,
  });

  // Table Data & Paginations
  const [sales, setSales] = useState([]);
  const [salesPagination, setSalesPagination] = useState({ current_page: 1, last_page: 1, total: 0 });

  const [products, setProducts] = useState([]);
  const [productsPagination, setProductsPagination] = useState({ current_page: 1, last_page: 1, total: 0 });

  const [consignors, setConsignors] = useState([]);
  const [consignorsPagination, setConsignorsPagination] = useState({ current_page: 1, last_page: 1, total: 0 });

  const [activeConsignorsList, setActiveConsignorsList] = useState([]);
  const [categories, setCategories] = useState([]);
  const [units, setUnits] = useState([]);

  // Filters State
  const [filters, setFilters] = useState({
    search: '',
    consignor_id: '',
    settlement_status: 'all',
    date_from: '',
    date_to: '',
  });

  // UI / Loading states
  const [isLoading, setIsLoading] = useState(false);
  const [isExportingPdf, setIsExportingPdf] = useState(false);
  const [isExportingExcel, setIsExportingExcel] = useState(false);
  const [toast, setToast] = useState(null);

  // Modals state
  const [consignorModalOpen, setConsignorModalOpen] = useState(false);
  const [selectedConsignor, setSelectedConsignor] = useState(null);

  const [productModalOpen, setProductModalOpen] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState(null);

  const [stockModalOpen, setStockModalOpen] = useState(false);
  const [selectedStockProduct, setSelectedStockProduct] = useState(null);

  const [settlementModalOpen, setSettlementModalOpen] = useState(false);
  const [selectedSettlementConsignor, setSelectedSettlementConsignor] = useState(null);

  const [isSubmitting, setIsSubmitting] = useState(false);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  // Load Dropdown Options (Categories, Units, Active Consignors)
  const loadOptions = useCallback(async () => {
    try {
      const [cRes, catRes, uRes] = await Promise.all([
        consignmentService.getActiveConsignors(),
        categoryService.getCategories({ all: true, per_page: 100 }),
        unitService.getUnits({ all: true, per_page: 100 }),
      ]);
      const cList = cRes?.data || [];
      const catList = catRes?.data || [];
      const uList = uRes?.data || [];

      setActiveConsignorsList(Array.isArray(cList) ? cList : []);
      setCategories(Array.isArray(catList) ? catList : []);
      setUnits(Array.isArray(uList) ? uList : []);
    } catch (e) {
      console.error('Gagal memuat master opsi konsinyasi:', e);
    }
  }, []);

  // Fetch Summary Metrics
  const fetchSummary = useCallback(async () => {
    try {
      const res = await consignmentService.getSummary({
        consignor_id: filters.consignor_id || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
      });
      if (res.success && res.data) {
        setSummary(res.data);
      }
    } catch (e) {
      console.error('Gagal mengambil ringkasan konsinyasi:', e);
    }
  }, [filters.consignor_id, filters.date_from, filters.date_to]);

  // Fetch Active Tab Data
  const fetchData = useCallback(async () => {
    setIsLoading(true);
    try {
      if (activeTab === 'sales') {
        const res = await consignmentService.getSales({
          page: salesPagination.current_page,
          search: filters.search || undefined,
          consignor_id: filters.consignor_id || undefined,
          settlement_status: filters.settlement_status !== 'all' ? filters.settlement_status : undefined,
          date_from: filters.date_from || undefined,
          date_to: filters.date_to || undefined,
        });
        setSales(res.data || []);
        if (res.pagination) setSalesPagination(res.pagination);
      } else if (activeTab === 'products') {
        const res = await consignmentService.getProducts({
          page: productsPagination.current_page,
          search: filters.search || undefined,
          consignor_id: filters.consignor_id || undefined,
        });
        setProducts(res.data || []);
        if (res.pagination) setProductsPagination(res.pagination);
      } else if (activeTab === 'consignors') {
        const res = await consignmentService.getConsignors({
          page: consignorsPagination.current_page,
          search: filters.search || undefined,
        });
        setConsignors(res.data || []);
        if (res.pagination) setConsignorsPagination(res.pagination);
      }
    } catch (e) {
      console.error('Gagal mengambil data tab konsinyasi:', e);
      showToast('Gagal memuat data konsinyasi', 'error');
    } finally {
      setIsLoading(false);
    }
  }, [activeTab, filters, salesPagination.current_page, productsPagination.current_page, consignorsPagination.current_page]);

  useEffect(() => {
    loadOptions();
  }, [loadOptions]);

  useEffect(() => {
    fetchSummary();
  }, [fetchSummary]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // Filter Handlers
  const handleFilterChange = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
  };

  const handleResetFilters = () => {
    setFilters({
      search: '',
      consignor_id: '',
      settlement_status: 'all',
      date_from: '',
      date_to: '',
    });
    setSalesPagination((prev) => ({ ...prev, current_page: 1 }));
    setProductsPagination((prev) => ({ ...prev, current_page: 1 }));
    setConsignorsPagination((prev) => ({ ...prev, current_page: 1 }));
  };

  const handlePageChange = (newPage) => {
    if (activeTab === 'sales') {
      setSalesPagination((prev) => ({ ...prev, current_page: newPage }));
    } else if (activeTab === 'products') {
      setProductsPagination((prev) => ({ ...prev, current_page: newPage }));
    } else if (activeTab === 'consignors') {
      setConsignorsPagination((prev) => ({ ...prev, current_page: newPage }));
    }
  };

  // Export Handlers
  const handleExportPdf = async () => {
    setIsExportingPdf(true);
    try {
      await consignmentService.downloadPdf({
        search: filters.search || undefined,
        consignor_id: filters.consignor_id || undefined,
        settlement_status: filters.settlement_status !== 'all' ? filters.settlement_status : undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
      });
      showToast('Laporan konsinyasi PDF berhasil diunduh');
    } catch (e) {
      showToast('Gagal mengunduh PDF konsinyasi', 'error');
    } finally {
      setIsExportingPdf(false);
    }
  };

  const handleExportExcel = async () => {
    setIsExportingExcel(true);
    try {
      await consignmentService.downloadExcel({
        search: filters.search || undefined,
        consignor_id: filters.consignor_id || undefined,
        settlement_status: filters.settlement_status !== 'all' ? filters.settlement_status : undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
      });
      showToast('Laporan konsinyasi Excel berhasil diunduh');
    } catch (e) {
      showToast('Gagal mengunduh Excel konsinyasi', 'error');
    } finally {
      setIsExportingExcel(false);
    }
  };

  // CRUD Submissions
  const handleConsignorSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedConsignor) {
        await consignmentService.updateConsignor(selectedConsignor.id, formData);
        showToast('Data mitra konsinyor berhasil diperbarui');
      } else {
        await consignmentService.createConsignor(formData);
        showToast('Mitra konsinyor baru berhasil ditambahkan');
      }
      setConsignorModalOpen(false);
      setSelectedConsignor(null);
      loadOptions();
      fetchSummary();
      fetchData();
    } catch (e) {
      const msg = e.response?.data?.message || 'Gagal menyimpan data mitra konsinyor';
      showToast(msg, 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDeleteConsignor = async (consignor) => {
    if (!window.confirm(`Yakin ingin menghapus mitra konsinyor "${consignor.name}"?`)) return;
    try {
      await consignmentService.deleteConsignor(consignor.id);
      showToast('Mitra konsinyor berhasil dihapus');
      loadOptions();
      fetchSummary();
      fetchData();
    } catch (e) {
      const msg = e.response?.data?.message || 'Gagal menghapus mitra konsinyor';
      showToast(msg, 'error');
    }
  };

  const handleProductSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      if (selectedProduct) {
        await consignmentService.updateProduct(selectedProduct.id, formData);
        showToast('Data barang konsinyasi berhasil diperbarui');
      } else {
        await consignmentService.createProduct(formData);
        showToast('Barang konsinyasi baru berhasil ditambahkan ke inventaris');
      }
      setProductModalOpen(false);
      setSelectedProduct(null);
      fetchSummary();
      fetchData();
    } catch (e) {
      const msg = e.response?.data?.message || 'Gagal menyimpan barang konsinyasi';
      showToast(msg, 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleStockSubmit = async (stockData) => {
    if (!selectedStockProduct) return;
    setIsSubmitting(true);
    try {
      await consignmentService.adjustStock(selectedStockProduct.id, stockData);
      showToast(
        stockData.type === 'return'
          ? 'Retur barang konsinyasi ke mitra berhasil dicatat'
          : 'Penambahan stok titipan berhasil disimpan'
      );
      setStockModalOpen(false);
      setSelectedStockProduct(null);
      fetchSummary();
      fetchData();
    } catch (e) {
      const msg = e.response?.data?.message || 'Gagal memproses penyesuaian stok';
      showToast(msg, 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSettlementSubmit = async (settlementData) => {
    setIsSubmitting(true);
    try {
      await consignmentService.settle(settlementData);
      showToast('Penyetoran bagi hasil konsinyasi berhasil diselesaikan!');
      setSettlementModalOpen(false);
      setSelectedSettlementConsignor(null);
      fetchSummary();
      fetchData();
    } catch (e) {
      const msg = e.response?.data?.message || 'Gagal memproses penyetoran tagihan';
      showToast(msg, 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold animate-slide-up ${
            toast.type === 'error'
              ? 'bg-rose-50 text-rose-700 border-rose-200'
              : 'bg-emerald-50 text-emerald-800 border-emerald-200'
          }`}
        >
          {toast.type === 'error' ? <AlertCircle className="w-4 h-4 shrink-0" /> : <CheckCircle2 className="w-4 h-4 shrink-0" />}
          <span>{toast.message}</span>
        </div>
      )}

      {/* Page Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2.5">
            <h1 className="text-xl font-black text-[#20282D] tracking-tight">
              Manajemen &amp; Penjualan Konsinyasi
            </h1>
            <span className="px-2.5 py-0.5 text-[11px] font-bold bg-[#E5F6FA] text-[#167A93] rounded-full border border-[#CFEFF6]">
              Real-Time POS
            </span>
          </div>
          <p className="text-xs text-[#879197] mt-1">
            Kelola barang titipan mitra (konsinyor), pantau stok terjual, dan perhitungan bagi hasil/komisi.
          </p>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={() => {
              setSelectedProduct(null);
              setProductModalOpen(true);
            }}
            className="px-3.5 py-2 text-xs font-bold text-white bg-[#20282D] hover:bg-[#167A93] rounded-xl transition flex items-center gap-1.5 shadow-xs cursor-pointer"
          >
            <Plus className="w-4 h-4" />
            <span>+ Tambah Barang Konsinyasi</span>
          </button>

          <button
            type="button"
            onClick={() => {
              setSelectedConsignor(null);
              setConsignorModalOpen(true);
            }}
            className="px-3 py-2 text-xs font-bold text-[#20282D] bg-white hover:bg-[#F8FAFA] border border-[#E7ECEE] hover:border-[#D5E3E7] rounded-xl transition flex items-center gap-1.5 shadow-2xs cursor-pointer"
          >
            <Building2 className="w-4 h-4 text-[#167A93]" />
            <span>+ Data Konsinyor (Mitra)</span>
          </button>

          <button
            type="button"
            onClick={handleExportPdf}
            disabled={isExportingPdf}
            className="px-3 py-2 text-xs font-bold text-[#20282D] bg-white hover:bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl transition flex items-center gap-1.5 shadow-2xs cursor-pointer disabled:opacity-50"
          >
            <FileText className="w-4 h-4 text-rose-500" />
            <span>{isExportingPdf ? 'PDF...' : 'PDF'}</span>
          </button>

          <button
            type="button"
            onClick={handleExportExcel}
            disabled={isExportingExcel}
            className="px-3 py-2 text-xs font-bold text-[#20282D] bg-white hover:bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl transition flex items-center gap-1.5 shadow-2xs cursor-pointer disabled:opacity-50"
          >
            <FileSpreadsheet className="w-4 h-4 text-emerald-600" />
            <span>{isExportingExcel ? 'Excel...' : 'Excel'}</span>
          </button>
        </div>
      </div>

      {/* 4 Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Card 1: Produk Aktif */}
        <div className="bg-white rounded-2xl p-4 border border-[#E7ECEE] shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-[#879197]">
              TOTAL PRODUK AKTIF
            </span>
            <div className="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
              <Package className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <span className="text-xl font-black text-[#20282D] font-mono">
              {summary.total_active_sku}{' '}
              <span className="text-xs font-semibold text-[#879197]">SKU</span>
            </span>
            <div className="text-[11px] text-[#879197] mt-0.5">
              Sisa fisik: <span className="font-bold text-[#20282D]">{summary.total_active_stock}</span> unit
            </div>
          </div>
        </div>

        {/* Card 2: Terjual Periode Ini */}
        <div className="bg-white rounded-2xl p-4 border border-[#E7ECEE] shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-[#879197]">
              TOTAL TERJUAL PERIODE INI
            </span>
            <div className="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
              <ShoppingCart className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <span className="text-xl font-black text-[#20282D] font-mono">
              {Number(summary.total_sold_qty || 0).toLocaleString('id-ID')}{' '}
              <span className="text-xs font-semibold text-[#879197]">Unit</span>
            </span>
            <div className="text-[11px] text-[#879197] mt-0.5">
              Omzet POS: <span className="font-bold text-[#20282D]">{formatRupiah(summary.total_sales_amount)}</span>
            </div>
          </div>
        </div>

        {/* Card 3: Komisi Toko / Bagi Hasil */}
        <div className="bg-white rounded-2xl p-4 border border-[#E7ECEE] shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-[#879197]">
              KOMISI TOKO / BAGI HASIL
            </span>
            <div className="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <TrendingUp className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <span className="text-xl font-black text-[#059669] font-mono">
              {formatRupiah(summary.total_store_commission)}
            </span>
            <div className="text-[11px] text-emerald-700 font-semibold mt-0.5">
              Pendapatan bersih toko diakui
            </div>
          </div>
        </div>

        {/* Card 4: Tagihan ke Konsinyor */}
        <div className="bg-white rounded-2xl p-4 border border-[#E7ECEE] shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-[#879197]">
              TAGIHAN KE KONSINYOR
            </span>
            <div className="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
              <Clock className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <span className="text-xl font-black text-amber-600 font-mono">
              {formatRupiah(summary.unsettled_payable)}
            </span>
            <div className="text-[11px] text-[#879197] mt-0.5">
              Sudah disetor:{' '}
              <span className="font-bold text-[#059669]">{formatRupiah(summary.settled_paid)}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Sub-Tabs Navigation */}
      <div className="bg-white rounded-2xl border border-[#E7ECEE] p-1.5 flex flex-wrap gap-1 shadow-2xs">
        <button
          type="button"
          onClick={() => setActiveTab('sales')}
          className={`flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer ${
            activeTab === 'sales'
              ? 'bg-[#20282D] text-white shadow-xs'
              : 'text-[#879197] hover:text-[#20282D] hover:bg-[#F8FAFA]'
          }`}
        >
          <ShoppingCart className="w-4 h-4" />
          <span>Penjualan &amp; Bagi Hasil Konsinyasi</span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab('products')}
          className={`flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer ${
            activeTab === 'products'
              ? 'bg-[#20282D] text-white shadow-xs'
              : 'text-[#879197] hover:text-[#20282D] hover:bg-[#F8FAFA]'
          }`}
        >
          <Package className="w-4 h-4" />
          <span>Stok Barang Konsinyasi</span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab('consignors')}
          className={`flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer ${
            activeTab === 'consignors'
              ? 'bg-[#20282D] text-white shadow-xs'
              : 'text-[#879197] hover:text-[#20282D] hover:bg-[#F8FAFA]'
          }`}
        >
          <Handshake className="w-4 h-4" />
          <span>Data Konsinyor &amp; Rekap Tagihan</span>
        </button>
      </div>

      {/* Filter Bar */}
      <div className="bg-white rounded-2xl p-4 border border-[#E7ECEE] shadow-2xs space-y-3">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          {/* Search Input */}
          <div className="relative lg:col-span-2">
            <Search className="w-4 h-4 text-[#879197] absolute left-3 top-2.5" />
            <input
              type="text"
              value={filters.search}
              onChange={(e) => handleFilterChange('search', e.target.value)}
              placeholder="Cari produk, invoice POS, atau mitra..."
              className="w-full pl-9 pr-3 py-2 text-xs border border-[#E7ECEE] rounded-xl focus:outline-none focus:border-[#55BFD8]"
            />
          </div>

          {/* Consignor Filter */}
          <div>
            <select
              value={filters.consignor_id}
              onChange={(e) => handleFilterChange('consignor_id', e.target.value)}
              className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl bg-white focus:outline-none focus:border-[#55BFD8]"
            >
              <option value="">Semua Mitra Konsinyor</option>
              {activeConsignorsList.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
          </div>

          {/* Status Filter (Tab 1) */}
          <div>
            <select
              value={filters.settlement_status}
              onChange={(e) => handleFilterChange('settlement_status', e.target.value)}
              className="w-full px-3 py-2 text-xs border border-[#E7ECEE] rounded-xl bg-white focus:outline-none focus:border-[#55BFD8]"
            >
              <option value="all">Semua Status Setoran</option>
              <option value="unsettled">Belum Disetor (Hutang)</option>
              <option value="settled">Sudah Lunas Disetor</option>
            </select>
          </div>

          {/* Reset Button */}
          <div>
            <button
              type="button"
              onClick={handleResetFilters}
              className="w-full py-2 text-xs font-bold text-[#879197] hover:text-[#20282D] hover:bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer"
            >
              <RefreshCw className="w-3.5 h-3.5" />
              <span>Reset Filter</span>
            </button>
          </div>
        </div>

        {/* Date Range (Untuk Tab Penjualan) */}
        {activeTab === 'sales' && (
          <div className="pt-2 border-t border-[#F1F4F5] flex flex-wrap items-center gap-3 text-xs text-[#879197]">
            <span className="font-semibold text-[#20282D]">Periode Penjualan:</span>
            <div className="flex items-center gap-2">
              <input
                type="date"
                value={filters.date_from}
                onChange={(e) => handleFilterChange('date_from', e.target.value)}
                className="px-2.5 py-1.5 text-xs border border-[#E7ECEE] rounded-lg focus:outline-none focus:border-[#55BFD8]"
              />
              <span>s/d</span>
              <input
                type="date"
                value={filters.date_to}
                onChange={(e) => handleFilterChange('date_to', e.target.value)}
                className="px-2.5 py-1.5 text-xs border border-[#E7ECEE] rounded-lg focus:outline-none focus:border-[#55BFD8]"
              />
            </div>
          </div>
        )}
      </div>

      {/* Content Section based on active tab */}
      <div className="bg-white rounded-2xl border border-[#E7ECEE] shadow-2xs overflow-hidden">
        {/* ================= TAB 1: PENJUALAN & BAGI HASIL ================= */}
        {activeTab === 'sales' && (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-[#F8FAFA] border-b border-[#E7ECEE] text-[#879197] font-bold">
                  <th className="py-3 px-4 w-12 text-center">No</th>
                  <th className="py-3 px-4">Tanggal &amp; No. POS</th>
                  <th className="py-3 px-4">Produk Konsinyasi</th>
                  <th className="py-3 px-4">Mitra (Konsinyor)</th>
                  <th className="py-3 px-4 text-center">Qty</th>
                  <th className="py-3 px-4 text-right">Harga Jual</th>
                  <th className="py-3 px-4 text-right">Total Omzet</th>
                  <th className="py-3 px-4 text-right">Hak Mitra (HPP)</th>
                  <th className="py-3 px-4 text-right">Komisi Toko</th>
                  <th className="py-3 px-4 text-center">Status Setor</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E7ECEE]">
                {isLoading ? (
                  <tr>
                    <td colSpan="10" className="py-12 text-center text-[#879197]">
                      <div className="flex items-center justify-center gap-2">
                        <RefreshCw className="w-4 h-4 animate-spin text-[#167A93]" />
                        <span>Memuat data transaksi konsinyasi...</span>
                      </div>
                    </td>
                  </tr>
                ) : sales.length === 0 ? (
                  <tr>
                    <td colSpan="10" className="py-12 text-center text-[#879197]">
                      Belum ada transaksi penjualan konsinyasi yang sesuai kriteria pencarian.
                    </td>
                  </tr>
                ) : (
                  sales.map((item, idx) => {
                    const isSettled = item.settlement_status === 'settled';
                    return (
                      <tr key={item.id} className="hover:bg-[#F8FAFA] transition">
                        <td className="py-3 px-4 text-center text-[#879197] font-mono">
                          {(salesPagination.current_page - 1) * 15 + idx + 1}
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-bold text-[#20282D]">
                            {item.sale?.sale_date ? new Date(item.sale.sale_date).toLocaleDateString('id-ID') : '-'}
                          </div>
                          <span className="font-mono text-[11px] text-[#167A93] font-bold">
                            {item.sale?.transaction_number || item.sale?.invoice_number || '-'}
                          </span>
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-bold text-[#20282D]">{item.product?.name || '-'}</div>
                          <span className="text-[10px] font-mono text-[#879197]">
                            SKU: {item.product?.sku || '-'}
                          </span>
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-semibold text-[#20282D]">{item.consignor?.name || '-'}</div>
                          <span className="text-[10px] text-[#879197] font-mono">
                            {item.consignor?.code || '-'}
                          </span>
                        </td>
                        <td className="py-3 px-4 text-center font-mono font-bold text-[#20282D]">
                          {Number(item.quantity).toLocaleString('id-ID')}
                        </td>
                        <td className="py-3 px-4 text-right font-mono text-[#20282D]">
                          {formatRupiah(item.unit_price)}
                        </td>
                        <td className="py-3 px-4 text-right font-mono font-bold text-[#20282D]">
                          {formatRupiah(item.subtotal)}
                        </td>
                        <td className="py-3 px-4 text-right font-mono font-semibold text-amber-700">
                          {formatRupiah(item.consignment_cost)}
                        </td>
                        <td className="py-3 px-4 text-right font-mono font-extrabold text-[#059669]">
                          +{formatRupiah(item.commission_amount)}
                        </td>
                        <td className="py-3 px-4 text-center">
                          {isSettled ? (
                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                              <CheckCircle2 className="w-3 h-3" />
                              <span>Lunas</span>
                            </span>
                          ) : (
                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                              <Clock className="w-3 h-3" />
                              <span>Belum Disetor</span>
                            </span>
                          )}
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        )}

        {/* ================= TAB 2: STOK BARANG KONSINYASI ================= */}
        {activeTab === 'products' && (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-[#F8FAFA] border-b border-[#E7ECEE] text-[#879197] font-bold">
                  <th className="py-3 px-4 w-12 text-center">No</th>
                  <th className="py-3 px-4">Kode SKU / Barcode</th>
                  <th className="py-3 px-4">Nama Produk Titipan</th>
                  <th className="py-3 px-4">Mitra Konsinyor</th>
                  <th className="py-3 px-4 text-center">Stok Awal</th>
                  <th className="py-3 px-4 text-center">Terjual</th>
                  <th className="py-3 px-4 text-center">Sisa Stok Fisik</th>
                  <th className="py-3 px-4 text-right">Modal Mitra</th>
                  <th className="py-3 px-4 text-right">Harga Jual</th>
                  <th className="py-3 px-4 text-right">Komisi Toko</th>
                  <th className="py-3 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E7ECEE]">
                {isLoading ? (
                  <tr>
                    <td colSpan="11" className="py-12 text-center text-[#879197]">
                      <div className="flex items-center justify-center gap-2">
                        <RefreshCw className="w-4 h-4 animate-spin text-[#167A93]" />
                        <span>Memuat daftar stok barang konsinyasi...</span>
                      </div>
                    </td>
                  </tr>
                ) : products.length === 0 ? (
                  <tr>
                    <td colSpan="11" className="py-12 text-center text-[#879197]">
                      Belum ada data barang konsinyasi yang terdaftar.
                    </td>
                  </tr>
                ) : (
                  products.map((p, idx) => {
                    const stock = Number(p.stock) || 0;
                    const isLow = stock <= Number(p.minimum_stock || 5);
                    return (
                      <tr key={p.id} className="hover:bg-[#F8FAFA] transition">
                        <td className="py-3 px-4 text-center text-[#879197] font-mono">
                          {(productsPagination.current_page - 1) * 15 + idx + 1}
                        </td>
                        <td className="py-3 px-4 font-mono font-bold text-[#167A93]">
                          {p.sku || '-'}
                          {p.barcode && <div className="text-[10px] text-[#879197]">{p.barcode}</div>}
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-bold text-[#20282D]">{p.name}</div>
                          <span className="text-[10px] text-[#879197]">
                            Kategori: {p.category?.name || '-'}
                          </span>
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-semibold text-[#20282D]">{p.consignor?.name || '-'}</div>
                          <span className="text-[10px] text-[#879197] font-mono">
                            {p.consignor?.phone || '-'}
                          </span>
                        </td>
                        <td className="py-3 px-4 text-center font-mono text-[#879197]">
                          {p.initial_consignment_stock || 0}
                        </td>
                        <td className="py-3 px-4 text-center font-mono font-bold text-blue-600">
                          {p.total_sold_qty || 0}
                        </td>
                        <td className="py-3 px-4 text-center font-mono font-black">
                          <span className={stock <= 0 ? 'text-rose-600' : isLow ? 'text-amber-600' : 'text-[#059669]'}>
                            {stock} {p.unit?.symbol || 'unit'}
                          </span>
                        </td>
                        <td className="py-3 px-4 text-right font-mono text-[#879197]">
                          {formatRupiah(p.consignment_cost_price)}
                        </td>
                        <td className="py-3 px-4 text-right font-mono font-bold text-[#20282D]">
                          {formatRupiah(p.selling_price)}
                        </td>
                        <td className="py-3 px-4 text-right">
                          <div className="font-mono font-extrabold text-[#059669]">
                            {formatRupiah(p.commission_amount)}
                          </div>
                          <span className="text-[10px] text-[#879197]">({p.commission_rate}%)</span>
                        </td>
                        <td className="py-3 px-4 text-center">
                          <div className="flex items-center justify-center gap-1.5">
                            <button
                              type="button"
                              title="Penyesuaian Stok (Tambah Titipan / Retur)"
                              onClick={() => {
                                setSelectedStockProduct(p);
                                setStockModalOpen(true);
                              }}
                              className="p-1.5 rounded-lg text-[#167A93] hover:bg-[#E5F6FA] transition cursor-pointer"
                            >
                              <ArrowDownLeft className="w-4 h-4" />
                            </button>
                            <button
                              type="button"
                              title="Edit Data Produk"
                              onClick={() => {
                                setSelectedProduct(p);
                                setProductModalOpen(true);
                              }}
                              className="p-1.5 rounded-lg text-[#879197] hover:text-[#20282D] hover:bg-gray-100 transition cursor-pointer"
                            >
                              <Edit2 className="w-4 h-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        )}

        {/* ================= TAB 3: DATA KONSIYOR & REKAP TAGIHAN ================= */}
        {activeTab === 'consignors' && (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-[#F8FAFA] border-b border-[#E7ECEE] text-[#879197] font-bold">
                  <th className="py-3 px-4 w-12 text-center">No</th>
                  <th className="py-3 px-4">Nama Mitra Konsinyasi</th>
                  <th className="py-3 px-4">Kontak &amp; Alamat</th>
                  <th className="py-3 px-4">Rekening Penyetoran</th>
                  <th className="py-3 px-4 text-center">Jumlah Jenis Barang</th>
                  <th className="py-3 px-4 text-center">Status</th>
                  <th className="py-3 px-4 text-right">Tagihan Belum Disetor</th>
                  <th className="py-3 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E7ECEE]">
                {isLoading ? (
                  <tr>
                    <td colSpan="8" className="py-12 text-center text-[#879197]">
                      <div className="flex items-center justify-center gap-2">
                        <RefreshCw className="w-4 h-4 animate-spin text-[#167A93]" />
                        <span>Memuat data mitra konsinyasi...</span>
                      </div>
                    </td>
                  </tr>
                ) : consignors.length === 0 ? (
                  <tr>
                    <td colSpan="8" className="py-12 text-center text-[#879197]">
                      Belum ada data mitra konsinyasi yang terdaftar.
                    </td>
                  </tr>
                ) : (
                  consignors.map((c, idx) => {
                    const hasUnsettled = (Number(c.unsettled_amount) || 0) > 0;
                    const isActive = c.is_active ?? true;
                    return (
                      <tr key={c.id} className="hover:bg-[#F8FAFA] transition">
                        <td className="py-3.5 px-4 text-center text-[#879197] font-mono">
                          {(consignorsPagination.current_page - 1) * 15 + idx + 1}
                        </td>
                        <td className="py-3.5 px-4">
                          <div className="flex items-center gap-2">
                            <span className="font-bold text-[#20282D] text-xs">{c.name}</span>
                            <span className="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-[#E5F6FA] text-[#167A93] border border-[#CFEFF6]">
                              {c.code || '-'}
                            </span>
                          </div>
                          <span className="text-[10px] text-[#879197] block mt-0.5">
                            Bagi hasil toko default: <strong>{c.default_commission_rate ?? 10}%</strong>
                          </span>
                        </td>
                        <td className="py-3.5 px-4">
                          <div className="text-[#20282D] font-bold">{c.phone || '-'}</div>
                          <span className="text-[10px] text-[#879197] line-clamp-1 mt-0.5">
                            {c.address || c.email || 'Tanpa alamat'}
                          </span>
                        </td>
                        <td className="py-3.5 px-4">
                          {c.bank_account_number ? (
                            <div>
                              <div className="font-bold text-[#20282D]">
                                {c.bank_name || 'Bank'} — {c.bank_account_number}
                              </div>
                              <span className="text-[10px] text-[#879197] block">
                                a.n. {c.bank_account_holder || c.name}
                              </span>
                            </div>
                          ) : (
                            <span className="inline-block px-2 py-0.5 bg-gray-100 text-[#879197] rounded text-[10px] font-medium">
                              Tunai Langsung
                            </span>
                          )}
                        </td>
                        <td className="py-3.5 px-4 text-center">
                          <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-[#EFF7FA] text-[#167A93] border border-[#D5E3E7]">
                            <Package className="w-3.5 h-3.5" />
                            <span>{c.products_count || 0} Jenis Barang</span>
                          </span>
                          <span className="text-[10px] text-[#879197] block mt-1">
                            Sisa fisik: <strong className="text-[#20282D]">{Number(c.total_stock_units || 0).toLocaleString('id-ID')}</strong> unit
                          </span>
                        </td>
                        <td className="py-3.5 px-4 text-center">
                          {isActive ? (
                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                              <span className="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                              <span>Aktif</span>
                            </span>
                          ) : (
                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600">
                              <span>Nonaktif</span>
                            </span>
                          )}
                        </td>
                        <td className="py-3.5 px-4 text-right">
                          <div className="font-mono font-black text-amber-600 text-xs">
                            {formatRupiah(c.unsettled_amount)}
                          </div>
                          {hasUnsettled ? (
                            <span className="text-[10px] text-amber-700 font-semibold block mt-0.5">
                              {c.unsettled_items_count} item belum disetor
                            </span>
                          ) : (
                            <span className="text-[10px] text-emerald-700 font-medium block mt-0.5">
                              Semua tagihan lunas
                            </span>
                          )}
                        </td>
                        <td className="py-3.5 px-4 text-center">
                          <div className="flex items-center justify-center gap-1.5">
                            {hasUnsettled && (
                              <button
                                type="button"
                                title="Settle / Bayar Bagi Hasil ke Mitra"
                                onClick={() => {
                                  setSelectedSettlementConsignor(c);
                                  setSettlementModalOpen(true);
                                }}
                                className="px-2.5 py-1 text-[11px] font-bold text-white bg-[#059669] hover:bg-emerald-700 rounded-lg shadow-2xs transition cursor-pointer flex items-center gap-1"
                              >
                                <Coins className="w-3.5 h-3.5" />
                                <span>Setor</span>
                              </button>
                            )}
                            <button
                              type="button"
                              title="Edit Data Mitra"
                              onClick={() => {
                                setSelectedConsignor(c);
                                setConsignorModalOpen(true);
                              }}
                              className="p-1.5 rounded-lg text-[#879197] hover:text-[#20282D] hover:bg-gray-100 transition cursor-pointer"
                            >
                              <Edit2 className="w-4 h-4" />
                            </button>
                            <button
                              type="button"
                              title="Hapus Mitra"
                              onClick={() => handleDeleteConsignor(c)}
                              className="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition cursor-pointer"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        )}
        {/* Pagination Controls */}
        {(() => {
          const pagination =
            activeTab === 'sales'
              ? salesPagination
              : activeTab === 'products'
              ? productsPagination
              : consignorsPagination;

          if (!pagination || pagination.total === 0) return null;

          return (
            <div className="p-3.5 border-t border-[#E7ECEE] bg-[#F8FAFA] flex flex-wrap items-center justify-between gap-3 text-xs">
              <span className="text-[#879197]">
                Menampilkan{' '}
                <strong className="text-[#20282D]">
                  {Math.min((pagination.current_page - 1) * (pagination.per_page || 15) + 1, pagination.total)}
                </strong>{' '}
                -{' '}
                <strong className="text-[#20282D]">
                  {Math.min(pagination.current_page * (pagination.per_page || 15), pagination.total)}
                </strong>{' '}
                dari <strong className="text-[#20282D]">{pagination.total}</strong> total data
              </span>

              {pagination.last_page > 1 && (
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    disabled={pagination.current_page <= 1}
                    onClick={() => handlePageChange(pagination.current_page - 1)}
                    className="px-3 py-1.5 rounded-lg border border-[#E7ECEE] bg-white text-xs font-semibold text-[#20282D] hover:bg-[#F1F4F5] disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                  >
                    Sebelumnya
                  </button>
                  <span className="px-2 py-1 font-mono font-bold text-[#167A93]">
                    {pagination.current_page} / {pagination.last_page}
                  </span>
                  <button
                    type="button"
                    disabled={pagination.current_page >= pagination.last_page}
                    onClick={() => handlePageChange(pagination.current_page + 1)}
                    className="px-3 py-1.5 rounded-lg border border-[#E7ECEE] bg-white text-xs font-semibold text-[#20282D] hover:bg-[#F1F4F5] disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                  >
                    Selanjutnya
                  </button>
                </div>
              )}
            </div>
          );
        })()}
      </div>

      {/* Modals Container */}
      <ConsignorModal
        isOpen={consignorModalOpen}
        onClose={() => {
          setConsignorModalOpen(false);
          setSelectedConsignor(null);
        }}
        onSubmit={handleConsignorSubmit}
        initialData={selectedConsignor}
        isSubmitting={isSubmitting}
      />

      <ConsignmentProductModal
        isOpen={productModalOpen}
        onClose={() => {
          setProductModalOpen(false);
          setSelectedProduct(null);
        }}
        onSubmit={handleProductSubmit}
        initialData={selectedProduct}
        consignors={activeConsignorsList}
        categories={categories}
        units={units}
        isSubmitting={isSubmitting}
      />

      <ConsignmentStockModal
        isOpen={stockModalOpen}
        onClose={() => {
          setStockModalOpen(false);
          setSelectedStockProduct(null);
        }}
        onSubmit={handleStockSubmit}
        product={selectedStockProduct}
        isSubmitting={isSubmitting}
      />

      <ConsignmentSettlementModal
        isOpen={settlementModalOpen}
        onClose={() => {
          setSettlementModalOpen(false);
          setSelectedSettlementConsignor(null);
        }}
        onSubmit={handleSettlementSubmit}
        consignor={selectedSettlementConsignor}
        isSubmitting={isSubmitting}
      />
    </div>
  );
}

