import React, { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Plus, ShoppingCart, Filter } from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import purchaseService from '../../services/purchaseService';
import supplierService from '../../services/supplierService';
import { formatRupiah } from '../../utils/currency';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import PageHeader from '../../components/ui/PageHeader';
import StatusBadge from '../../components/ui/StatusBadge';
import PurchaseModal from './PurchaseModal';

/**
 * Format tanggal ramah pengguna (contoh: 10 Nov 2026)
 */
function formatDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(d);
  } catch {
    return dateStr;
  }
}

export function PurchasePage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const basePath = isAdmin ? '/admin/purchases' : '/staff/purchases';
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

  const hasActiveFilter = Boolean(
    search ||
    statusFilter ||
    paymentMethodFilter ||
    paymentStatusFilter ||
    supplierFilter ||
    startDate ||
    endDate
  );

  const handleResetFilter = () => {
    setSearch('');
    setStatusFilter('');
    setPaymentMethodFilter('');
    setPaymentStatusFilter('');
    setSupplierFilter('');
    setStartDate('');
    setEndDate('');
    setCurrentPage(1);
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
        navigate(`${basePath}/${response.data.id}`);
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
      label: 'NO INVOICE',
      align: 'left',
      className: 'text-left min-w-[130px]',
      render: (item) => (
        <div className="space-y-0.5 text-left">
          <Link
            to={`${basePath}/${item.id}`}
            className="font-mono text-xs font-bold text-[#37AFCB] hover:underline block"
          >
            {item.invoice_number}
          </Link>
          <div className="text-[11px] text-[#879197] font-medium">
            {formatDate(item.purchase_date)}
          </div>
        </div>
      ),
    },
    {
      key: 'supplier',
      label: 'SUPPLIER',
      align: 'left',
      className: 'text-left min-w-[160px]',
      render: (item) => (
        <div className="space-y-0.5 text-left">
          <div className="font-semibold text-xs text-[#20282D] leading-tight">
            {item.supplier?.name || '-'}
          </div>
          {item.supplier?.code && (
            <div className="text-[10px] text-[#879197] font-mono">
              {item.supplier.code}
            </div>
          )}
        </div>
      ),
    },
    {
      key: 'items_count',
      label: 'JUMLAH ITEM',
      align: 'center',
      className: 'text-center whitespace-nowrap min-w-[100px]',
      render: (item) => (
        <div className="text-center font-medium text-xs text-[#879197]">
          {item.items?.length || 0} macam
        </div>
      ),
    },
    {
      key: 'status',
      label: 'STATUS BARANG',
      align: 'center',
      className: 'text-center whitespace-nowrap min-w-[110px]',
      render: (item) => {
        const statusMap = {
          received: { status: 'completed', label: 'Diterima' },
          draft: { status: 'pending', label: 'Draft' },
          cancelled: { status: 'cancelled', label: 'Dibatalkan' },
        };
        const cfg = statusMap[item.status] || { status: 'info', label: item.status };
        return (
          <div className="flex justify-center">
            <StatusBadge status={cfg.status} label={cfg.label} />
          </div>
        );
      },
    },
    {
      key: 'payment_status',
      label: 'STATUS BAYAR',
      align: 'center',
      className: 'text-center whitespace-nowrap min-w-[130px]',
      render: (item) => {
        const isPaid = item.payment_status === 'paid';
        const isPartial = item.payment_status === 'partial';
        const statusKey = isPaid ? 'completed' : isPartial ? 'sebagian' : 'draft';
        const labelText = isPaid ? 'Lunas' : isPartial ? 'Sebagian (DP)' : 'Belum Bayar';
        const methodLabel = item.payment_method
          ? item.payment_method === 'cash'
            ? 'Cash'
            : item.payment_method === 'transfer'
            ? 'Transfer'
            : item.payment_method === 'tempo'
            ? 'Tempo'
            : item.payment_method
          : '-';

        return (
          <div className="flex flex-col items-center gap-0.5">
            <StatusBadge status={statusKey} label={labelText} />
            <span className="text-[10px] text-[#879197] font-medium">Metode: {methodLabel}</span>
          </div>
        );
      },
    },
    {
      key: 'grand_total',
      label: 'TOTAL BELANJA',
      align: 'right',
      className: 'text-right whitespace-nowrap min-w-[130px]',
      render: (item) => (
        <div className="font-mono text-xs font-black text-[#20282D] text-right">
          {formatRupiah(item.grand_total || 0)}
        </div>
      ),
    },
    {
      key: 'remaining_debt',
      label: 'SISA UTANG',
      align: 'right',
      className: 'text-right whitespace-nowrap min-w-[120px]',
      render: (item) => {
        const debt = Math.max(0, Number(item.grand_total) - Number(item.paid_amount));
        return (
          <div
            className={`font-mono text-xs font-bold text-right ${
              debt > 0 ? 'text-[#E47777]' : 'text-[#879197]'
            }`}
          >
            {debt > 0 ? formatRupiah(debt) : '-'}
          </div>
        );
      },
    },
    {
      key: 'actions',
      label: 'AKSI',
      align: 'center',
      className: 'text-center whitespace-nowrap w-24',
      render: (item) => (
        <div className="flex justify-center">
          <Link
            to={`${basePath}/${item.id}`}
            className="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold text-[#20282D] bg-[#F1F4F5] hover:bg-[#E7ECEE] rounded-full transition"
          >
            <span>Detail</span>
            <span>&rarr;</span>
          </Link>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Toast */}
      {toast && (
        <div
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2 ${
            toast.type === 'error'
              ? 'bg-[#FDF3F3] border-[#E47777]/30 text-[#E47777]'
              : 'bg-[#EBF7F2] border-[#48B58B]/30 text-[#48B58B]'
          }`}
        >
          <span>{toast.type === 'error' ? '⚠️' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      {/* Header Bar */}
      <PageHeader
        title="Pembelian"
        subtitle="Pencatatan faktur barang masuk dan pembaruan stok otomatis."
        actions={
          isAdmin && (
            <button
              onClick={() => setIsModalOpen(true)}
              className="flex items-center gap-2 px-4 py-2.5 bg-[#20282D] hover:bg-black text-white font-bold rounded-full text-xs shadow-xs transition cursor-pointer"
            >
              <Plus className="w-4 h-4 stroke-[2.5]" />
              <span>+ Input Pembelian</span>
            </button>
          )
        }
      />

      {/* Filter and Search Bar */}
      <div className="bg-white border border-[#E7ECEE] rounded-2xl p-5 shadow-[0_2px_10px_rgba(0,0,0,0.02)] space-y-4">
        {/* Baris 1: Filter Utama */}
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
          <div>
            <label className="block text-[11px] font-semibold text-[#879197] mb-1.5">
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
            <label className="block text-[11px] font-semibold text-[#879197] mb-1.5">
              Supplier
            </label>
            <select
              value={supplierFilter}
              onChange={(e) => {
                setSupplierFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full h-9 px-3 bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
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
            <label className="block text-[11px] font-semibold text-[#879197] mb-1.5">
              Status Barang
            </label>
            <select
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full h-9 px-3 bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
            >
              <option value="">Semua Status Barang</option>
              <option value="received">Diterima</option>
              <option value="draft">Draft (Pending)</option>
              <option value="cancelled">Dibatalkan</option>
            </select>
          </div>

          <div>
            <label className="block text-[11px] font-semibold text-[#879197] mb-1.5">
              Status Pembayaran
            </label>
            <select
              value={paymentStatusFilter}
              onChange={(e) => {
                setPaymentStatusFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full h-9 px-3 bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
            >
              <option value="">Semua Status Bayar</option>
              <option value="paid">Lunas</option>
              <option value="partial">Sebagian (DP)</option>
              <option value="unpaid">Belum Bayar</option>
            </select>
          </div>
        </div>

        {/* Baris 2: Metode Bayar & Rentang Tanggal */}
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5 pt-3.5 border-t border-[#F1F4F5] items-end">
          <div>
            <label className="block text-[11px] font-semibold text-[#879197] mb-1.5">
              Metode Pembayaran
            </label>
            <select
              value={paymentMethodFilter}
              onChange={(e) => {
                setPaymentMethodFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full h-9 px-3 bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
            >
              <option value="">Semua Metode</option>
              <option value="cash">Tunai (Cash)</option>
              <option value="transfer">Transfer Bank</option>
              <option value="tempo">Tempo (Kredit)</option>
            </select>
          </div>

          <div>
            <label className="block text-[11px] font-semibold text-[#879197] mb-1.5">
              Tanggal Mulai
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => {
                setStartDate(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full h-9 px-3 bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
            />
          </div>

          <div>
            <label className="block text-[11px] font-semibold text-[#879197] mb-1.5">
              Tanggal Akhir
            </label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => {
                setEndDate(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full h-9 px-3 bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
            />
          </div>

          <div className="flex items-center gap-2">
            {hasActiveFilter && (
              <button
                type="button"
                onClick={handleResetFilter}
                className="w-full h-9 px-3 rounded-xl border border-[#D7EBEF] bg-[#F8FAFA] hover:bg-[#EAEFF1] text-[#5A7C85] hover:text-[#20282D] text-xs font-semibold transition cursor-pointer flex items-center justify-center gap-1.5"
              >
                <span>↺</span>
                <span>Reset Filter</span>
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Table Data */}
      <div className="space-y-4">
        <DataTable
          columns={columns}
          data={purchases}
          isLoading={isLoading}
          emptyMessage="Belum ada riwayat transaksi pembelian."
        />

        {pagination && pagination.total > pagination.per_page && (
          <Pagination
            currentPage={pagination.current_page}
            lastPage={pagination.last_page}
            total={pagination.total}
            perPage={pagination.per_page}
            onPageChange={(page) => setCurrentPage(page)}
          />
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
