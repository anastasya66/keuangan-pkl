import React, { useState, useEffect, useCallback } from 'react';
import useAuth from '../../hooks/useAuth';
import posService from '../../services/posService';
import { formatRupiah } from '../../utils/currency';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import SearchInput from '../../components/common/SearchInput';
import { IconPos, IconPrinter } from '../../components/common/Icons';

export function SalesHistoryPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const [sales, setSales] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);

  // Filters
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [paymentMethodFilter, setPaymentMethodFilter] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  // Detail / Reprint Modal State
  const [selectedSale, setSelectedSale] = useState(null);
  const [receiptData, setReceiptData] = useState(null);
  const [isReceiptModalOpen, setIsReceiptModalOpen] = useState(false);

  // Cancel Modal State
  const [cancelTarget, setCancelTarget] = useState(null);
  const [cancelReason, setCancelReason] = useState('');
  const [isCancelling, setIsCancelling] = useState(false);

  // Toast
  const [toast, setToast] = useState(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchSales = useCallback(
    async (
      page = 1,
      query = search,
      status = statusFilter,
      method = paymentMethodFilter,
      start = startDate,
      end = endDate
    ) => {
      setIsLoading(true);
      try {
        const params = { page, search: query };
        if (status) params.status = status;
        if (method) params.payment_method = method;
        if (start) params.start_date = start;
        if (end) params.end_date = end;

        const response = await posService.getSales(params);
        if (response.success) {
          setSales(response.data);
          setPagination(response.pagination);
        }
      } catch (err) {
        showToast(err.response?.data?.message || 'Gagal memuat riwayat penjualan', 'error');
      } finally {
        setIsLoading(false);
      }
    },
    [search, statusFilter, paymentMethodFilter, startDate, endDate]
  );

  useEffect(() => {
    fetchSales(currentPage, search, statusFilter, paymentMethodFilter, startDate, endDate);
  }, [currentPage, search, statusFilter, paymentMethodFilter, startDate, endDate, fetchSales]);

  const handleOpenReceipt = async (saleId) => {
    try {
      const res = await posService.getReceipt(saleId);
      if (res.success) {
        setReceiptData(res.data);
        setIsReceiptModalOpen(true);
      }
    } catch (err) {
      showToast('Gagal memuat struk penjualan', 'error');
    }
  };

  const handleCancelSubmit = async () => {
    if (!cancelTarget) return;
    setIsCancelling(true);
    try {
      await posService.cancelSale(cancelTarget.id, { reason: cancelReason });
      showToast('Transaksi penjualan berhasil dibatalkan dan stok dikembalikan.');
      setCancelTarget(null);
      setCancelReason('');
      fetchSales(currentPage, search, statusFilter, paymentMethodFilter, startDate, endDate);
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal membatalkan penjualan', 'error');
    } finally {
      setIsCancelling(false);
    }
  };

  const columns = [
    {
      key: 'transaction_number',
      label: 'No Transaksi',
      render: (item) => (
        <div>
          <button
            type="button"
            onClick={() => handleOpenReceipt(item.id)}
            className="font-mono text-xs font-bold text-[#167A93] hover:underline"
          >
            {item.transaction_number}
          </button>
          <div className="text-[11px] text-[#577782]">{item.sale_date}</div>
        </div>
      ),
    },
    {
      key: 'cashier',
      label: 'Kasir',
      render: (item) => (
        <span className="text-xs font-semibold text-[#16323B]">{item.cashier?.name || '-'}</span>
      ),
    },
    {
      key: 'customer_name',
      label: 'Pelanggan',
      render: (item) => (
        <span className="text-xs text-[#577782]">{item.customer_name || 'Pelanggan Umum'}</span>
      ),
    },
    {
      key: 'items_count',
      label: 'Item',
      render: (item) => (
        <span className="text-xs text-[#16323B] font-medium whitespace-nowrap">{item.items?.length || 0} barang</span>
      ),
    },
    {
      key: 'payment_method',
      label: 'Metode',
      render: (item) => (
        <span className="px-2 py-0.5 text-[11px] font-bold rounded-md bg-[#F0F9FB] text-[#167A93] uppercase whitespace-nowrap inline-block">
          {item.payment_method}
        </span>
      ),
    },
    {
      key: 'grand_total',
      label: 'Total Belanja',
      align: 'right',
      render: (item) => (
        <div className="font-mono text-xs font-bold text-[#16323B] whitespace-nowrap">
          {formatRupiah(item.grand_total)}
        </div>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (item) => {
        if (item.status === 'completed') {
          return (
            <span className="px-2 py-0.5 text-[11px] font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap inline-block">
              ✓ Berhasil
            </span>
          );
        }
        return (
          <span className="px-2 py-0.5 text-[11px] font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200 whitespace-nowrap inline-block">
            ✕ Dibatalkan
          </span>
        );
      },
    },
    {
      key: 'actions',
      label: 'Aksi',
      align: 'right',
      render: (item) => (
        <div className="flex items-center justify-end gap-1.5">
          <button
            type="button"
            onClick={() => handleOpenReceipt(item.id)}
            className="px-2 py-1 text-xs font-semibold text-[#167A93] hover:bg-[#EAF8FA] rounded-lg transition"
            title="Cetak Ulang Struk"
          >
            🖨️ Struk
          </button>
          {isAdmin && item.status !== 'cancelled' && (
            <button
              type="button"
              onClick={() => setCancelTarget(item)}
              className="px-2 py-1 text-xs font-semibold text-rose-500 hover:bg-rose-50 rounded-lg transition"
              title="Batalkan Transaksi"
            >
              Batal
            </button>
          )}
        </div>
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

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-[#16323B] flex items-center gap-2.5">
            <span className="p-2 bg-[#EAF8FA] text-[#167A93] rounded-xl border border-[#38BFE0]/30">
              <IconPos className="w-5 h-5" />
            </span>
            Riwayat Transaksi Penjualan Kasir
          </h1>
          <p className="text-xs text-[#577782] mt-1">
            Daftar seluruh transaksi kasir POS, cetak ulang struk, dan status pembatalan
          </p>
        </div>
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
        <div className="md:col-span-2">
          <label className="block text-[11px] font-semibold text-[#577782] mb-1">
            Pencarian
          </label>
          <SearchInput
            value={search}
            onChange={(val) => {
              setSearch(val);
              setCurrentPage(1);
            }}
            placeholder="No transaksi, pelanggan..."
          />
        </div>

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
            className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
          >
            <option value="">Semua Metode</option>
            <option value="cash">Tunai (Cash)</option>
            <option value="transfer">Transfer Bank</option>
            <option value="qris">QRIS</option>
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
            className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
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
            className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
          />
        </div>
      </div>

      {/* Table Data */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
        <DataTable
          columns={columns}
          data={sales}
          isLoading={isLoading}
          emptyMessage="Belum ada riwayat penjualan kasir."
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

      {/* Reprint Receipt Modal */}
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

              <div className="text-center pt-1 text-[10px] text-[#577782] space-y-0.5">
                <div>{receiptData.footer_message}</div>
                <div>Barang yang dibeli tidak dapat ditukar</div>
              </div>
            </div>

            <div className="flex items-center gap-2 pt-2">
              <button
                type="button"
                onClick={() => window.print()}
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
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Cancel Confirm Dialog */}
      {cancelTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/40 backdrop-blur-xs animate-backdrop">
          <div className="bg-white rounded-2xl border border-[#D7EBEF] shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 className="text-base font-bold text-rose-600">Batalkan / Retur Penjualan?</h3>
            <p className="text-xs text-[#577782]">
              Transaksi <strong className="font-mono">{cancelTarget.transaction_number}</strong> akan dibatalkan, stok barang akan dikembalikan ke gudang, dan pencatatan akuntansi akan di-reverse.
            </p>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Alasan Pembatalan:
              </label>
              <textarea
                rows="2"
                placeholder="Contoh: Salah ketik / pelanggan membatalkan..."
                value={cancelReason}
                onChange={(e) => setCancelReason(e.target.value)}
                className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
              />
            </div>

            <div className="pt-3 flex items-center justify-end gap-2">
              <button
                type="button"
                onClick={() => setCancelTarget(null)}
                disabled={isCancelling}
                className="px-4 py-2 text-xs font-semibold text-[#577782] hover:bg-[#F0F9FB] rounded-xl"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handleCancelSubmit}
                disabled={isCancelling}
                className="px-5 py-2 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs"
              >
                {isCancelling ? 'Memproses...' : 'Ya, Batalkan Penjualan'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default SalesHistoryPage;

