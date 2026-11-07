import React, { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import purchaseService from '../../services/purchaseService';
import supplierService from '../../services/supplierService';
import { formatRupiah } from '../../utils/currency';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import PurchaseModal from './PurchaseModal';
import { IconPurchase } from '../../components/common/Icons';

export function PurchasePage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const navigate = useNavigate();

  const [purchases, setPurchases] = useState([]);
  const [suppliers, setSuppliers] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);

  // Filters
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [paymentMethodFilter, setPaymentMethodFilter] = useState('');
  const [paymentStatusFilter, setPaymentStatusFilter] = useState('');
  const [supplierFilter, setSupplierFilter] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Toast
  const [toast, setToast] = useState(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  useEffect(() => {
    async function loadSuppliers() {
      try {
        const res = await supplierService.getActiveSuppliers();
        if (res.success) setSuppliers(res.data);
      } catch (err) {
        console.error('Gagal memuat supplier:', err);
      }
    }
    loadSuppliers();
  }, []);

  const fetchPurchases = useCallback(
    async (
      page = 1,
      query = search,
      status = statusFilter,
      method = paymentMethodFilter,
      pStatus = paymentStatusFilter,
      suppId = supplierFilter,
      start = startDate,
      end = endDate
    ) => {
      setIsLoading(true);
      try {
        const params = { page, search: query };
        if (status) params.status = status;
        if (method) params.payment_method = method;
        if (pStatus) params.payment_status = pStatus;
        if (suppId) params.supplier_id = suppId;
        if (start) params.start_date = start;
        if (end) params.end_date = end;

        const response = await purchaseService.getPurchases(params);
        if (response.success) {
          setPurchases(response.data);
          setPagination(response.pagination);
        }
      } catch (error) {
        showToast(error.response?.data?.message || 'Gagal memuat data pembelian', 'error');
      } finally {
        setIsLoading(false);
      }
    },
    [search, statusFilter, paymentMethodFilter, paymentStatusFilter, supplierFilter, startDate, endDate]
  );

  useEffect(() => {
    fetchPurchases(
      currentPage,
      search,
      statusFilter,
      paymentMethodFilter,
      paymentStatusFilter,
      supplierFilter,
      startDate,
      endDate
    );
  }, [
    currentPage,
    search,
    statusFilter,
    paymentMethodFilter,
    paymentStatusFilter,
    supplierFilter,
    startDate,
    endDate,
    fetchPurchases,
  ]);

  const handleCreateSubmit = async (formData) => {
    setIsSubmitting(true);
    try {
      const response = await purchaseService.createPurchase(formData);
      showToast('Transaksi pembelian berhasil dibuat!');
      setIsModalOpen(false);
      fetchPurchases(1, search, statusFilter, paymentMethodFilter, paymentStatusFilter, supplierFilter, startDate, endDate);
      if (response.data?.id) {
        navigate(`/admin/purchases/${response.data.id}`);
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal membuat transaksi pembelian', 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  const columns = [
    {
      key: 'invoice_number',
      label: 'No Invoice',
      render: (item) => (
        <div>
          <Link
            to={`/admin/purchases/${item.id}`}
            className="font-mono text-xs font-bold text-[#167A93] hover:underline"
          >
            {item.invoice_number}
          </Link>
          <div className="text-[11px] text-[#577782]">{item.purchase_date}</div>
        </div>
      ),
    },
    {
      key: 'supplier',
      label: 'Supplier',
      render: (item) => (
        <div>
          <div className="font-semibold text-[#16323B]">{item.supplier?.name || '-'}</div>
          <div className="text-[11px] text-[#577782] font-mono">{item.supplier?.code}</div>
        </div>
      ),
    },
    {
      key: 'items_count',
      label: 'Jumlah Item',
      render: (item) => (
        <span className="text-xs text-[#16323B] font-medium whitespace-nowrap">
          {item.items?.length || 0} macam
        </span>
      ),
    },
    {
      key: 'status',
      label: 'Status Barang',
      render: (item) => {
        if (item.status === 'received') {
          return (
            <span className="px-2.5 py-0.5 text-[11px] font-bold rounded-md bg-emerald-600 text-white whitespace-nowrap inline-block shadow-2xs">
              ✓ Diterima
            </span>
          );
        }
        if (item.status === 'draft') {
          return (
            <span className="px-2.5 py-0.5 text-[11px] font-bold rounded-md bg-amber-600 text-white whitespace-nowrap inline-block shadow-2xs">
              ⏳ Draft
            </span>
          );
        }
        return (
          <span className="px-2.5 py-0.5 text-[11px] font-bold rounded-md bg-rose-600 text-white whitespace-nowrap inline-block shadow-2xs">
            ✕ Batal
          </span>
        );
      },
    },
    {
      key: 'payment_status',
      label: 'Status Bayar',
      render: (item) => {
        const isPaid = item.payment_status === 'paid';
        const isPartial = item.payment_status === 'partial';
        return (
          <div>
            <span
              className={`px-2.5 py-0.5 text-[11px] font-bold rounded-md whitespace-nowrap inline-block shadow-2xs ${
                isPaid
                  ? 'bg-emerald-600 text-white'
                  : isPartial
                  ? 'bg-blue-600 text-white'
                  : 'bg-rose-600 text-white'
              }`}
            >
              {isPaid ? 'Lunas' : isPartial ? 'Sebagian (DP)' : 'Belum Bayar'}
            </span>
            <div className="text-[10px] text-[#577782] mt-0.5 capitalize whitespace-nowrap font-medium">
              {item.payment_method}
            </div>
          </div>
        );
      },
    },
    {
      key: 'grand_total',
      label: 'Total Pembelian',
      align: 'right',
      render: (item) => (
        <div className="font-mono text-xs font-bold text-[#16323B]">
          {formatRupiah(item.grand_total)}
        </div>
      ),
    },
    {
      key: 'remaining_debt',
      label: 'Sisa Utang',
      align: 'right',
      render: (item) => {
        const debt = Math.max(0, Number(item.grand_total) - Number(item.paid_amount));
        return (
          <div
            className={`font-mono text-xs font-bold ${
              debt > 0 ? 'text-rose-600' : 'text-[#8FA8B0]'
            }`}
          >
            {debt > 0 ? formatRupiah(debt) : '-'}
          </div>
        );
      },
    },
    {
      key: 'actions',
      label: 'Aksi',
      align: 'right',
      render: (item) => (
        <Link
          to={`/admin/purchases/${item.id}`}
          className="px-2.5 py-1 text-xs font-semibold text-[#167A93] hover:bg-[#EAF8FA] rounded-lg transition"
        >
          Detail →
        </Link>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Toast */}
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

      {/* Header Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-[#16323B] flex items-center gap-2.5">
            <span className="p-2 bg-[#167A93] text-white rounded-xl shadow-xs">
              <IconPurchase className="w-5 h-5" />
            </span>
            Pembelian Toko
          </h1>
          <p className="text-xs text-[#577782] mt-1">
            Pencatatan faktur barang masuk dari supplier, kalkulasi HPP rata-rata, dan utang tempo
          </p>
        </div>

        {isAdmin && (
          <button
            onClick={() => setIsModalOpen(true)}
            className="flex items-center justify-center gap-2 px-4 py-2.5 bg-[#27C5E5] hover:bg-[#167A93] text-white font-semibold rounded-xl text-xs shadow-xs transition"
          >
            <span>+</span>
            <span>Buat Pembelian Baru</span>
          </button>
        )}
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs space-y-3">
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
          <div>
            <label className="block text-[11px] font-semibold text-[#577782] mb-1">
              Pencarian
            </label>
            <SearchInput
              value={search}
              onChange={(val) => {
                setSearch(val);
                setCurrentPage(1);
              }}
              placeholder="No Invoice, Supplier..."
            />
          </div>

          <div>
            <label className="block text-[11px] font-semibold text-[#577782] mb-1">
              Supplier
            </label>
            <select
              value={supplierFilter}
              onChange={(e) => {
                setSupplierFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
            >
              <option value="">Semua Supplier</option>
              {suppliers.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-[11px] font-semibold text-[#577782] mb-1">
              Status Barang
            </label>
            <select
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
            >
              <option value="">Semua Status Barang</option>
              <option value="received">Diterima</option>
              <option value="draft">Draft (Menunggu)</option>
              <option value="cancelled">Dibatalkan</option>
            </select>
          </div>

          <div>
            <label className="block text-[11px] font-semibold text-[#577782] mb-1">
              Status Pembayaran
            </label>
            <select
              value={paymentStatusFilter}
              onChange={(e) => {
                setPaymentStatusFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
            >
              <option value="">Semua Status Bayar</option>
              <option value="paid">Lunas</option>
              <option value="partial">Sebagian (DP)</option>
              <option value="unpaid">Belum Bayar</option>
            </select>
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 border-t border-[#F0F9FB]">
          <div>
            <label className="block text-[11px] font-semibold text-[#577782] mb-1">
              Metode Pembayaran
            </label>
            <select
              value={paymentMethodFilter}
              onChange={(e) => {
                setPaymentMethodFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
            >
              <option value="">Semua Metode</option>
              <option value="cash">Tunai (Kas)</option>
              <option value="transfer">Transfer Bank</option>
              <option value="tempo">Tempo (Kredit)</option>
            </select>
          </div>

          <div>
            <label className="block text-[11px] font-semibold text-[#577782] mb-1">
              Tanggal Mulai
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => {
                setStartDate(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
            />
          </div>

          <div>
            <label className="block text-[11px] font-semibold text-[#577782] mb-1">
              Tanggal Akhir
            </label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => {
                setEndDate(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5]"
            />
          </div>
        </div>
      </div>

      {/* Table Data */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
        <DataTable
          columns={columns}
          data={purchases}
          isLoading={isLoading}
          emptyMessage="Belum ada riwayat transaksi pembelian."
        />

        {pagination && pagination.total > pagination.per_page && (
          <div className="mt-4 pt-4 border-t border-[#EAF4F6]">
            <Pagination
              currentPage={pagination.current_page}
              lastPage={pagination.last_page}
              total={pagination.total}
              perPage={pagination.per_page}
              onPageChange={(page) => setCurrentPage(page)}
            />
          </div>
        )}
      </div>

      {/* Create Purchase Modal */}
      <PurchaseModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSubmit={handleCreateSubmit}
        isLoading={isSubmitting}
      />
    </div>
  );
}

export default PurchasePage;

