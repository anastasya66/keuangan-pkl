import React, { useState, useEffect } from 'react';
import posService from '../../services/posService';
import { formatRupiah } from '../../utils/currency';

export function SalesHistoryModal({ user, onClose, onSelectReceipt }) {
  const [sales, setSales] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [cancellingSaleId, setCancellingSaleId] = useState(null);
  const [cancellationReason, setCancellationReason] = useState('');
  const [isSubmittingCancel, setIsSubmittingCancel] = useState(false);

  const loadSales = async () => {
    setIsLoading(true);
    try {
      const res = await posService.getSales({ search, per_page: 25 });
      if (res.success && res.data) {
        setSales(res.data);
      }
    } catch (err) {
      console.error('Gagal memuat riwayat penjualan:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadSales();
  }, [search]);

  const handleOpenReceipt = async (saleId) => {
    try {
      const res = await posService.getReceipt(saleId);
      if (res.success && res.data) {
        onSelectReceipt(res.data);
      }
    } catch (err) {
      alert('Gagal mengambil data struk belanja');
    }
  };

  const handleConfirmCancel = async (e) => {
    e.preventDefault();
    if (!cancellingSaleId || isSubmittingCancel) return;

    setIsSubmittingCancel(true);
    try {
      const res = await posService.cancelSale(cancellingSaleId, {
        reason: cancellationReason || 'Pembatalan transaksi oleh kasir/admin',
      });
      if (res.success) {
        setCancellingSaleId(null);
        setCancellationReason('');
        loadSales();
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal membatalkan transaksi');
    } finally {
      setIsSubmittingCancel(false);
    }
  };

  const isAdmin = user?.role === 'admin';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
      <div className="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-2xl w-full overflow-hidden flex flex-col max-h-[85vh] animate-in fade-in duration-200">
        {/* Header Modal */}
        <div className="p-4 bg-gradient-to-r from-[#16323B] to-[#1E434F] text-white flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-xl">📋</span>
            <div>
              <h3 className="font-bold text-sm">Riwayat Transaksi Penjualan Kasir</h3>
              <p className="text-[11px] text-[#A2CBD4]">Daftar struk, cetak ulang, & retur penjualan</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-white/80 hover:text-white p-1 rounded-lg text-lg cursor-pointer"
          >
            ✕
          </button>
        </div>

        {/* Search Bar */}
        <div className="p-3 bg-gray-50 border-b border-gray-200">
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Cari nomor nota (POS-...) atau nama pelanggan..."
            className="w-full px-3.5 py-2 bg-white border border-gray-300 focus:border-[#55BFD8] rounded-xl text-xs text-gray-900 focus:outline-none transition shadow-2xs"
          />
        </div>

        {/* Content Table / List */}
        <div className="flex-1 overflow-y-auto p-3 space-y-2">
          {isLoading ? (
            <div className="py-12 text-center text-xs text-gray-500">Memuat riwayat transaksi...</div>
          ) : sales.length === 0 ? (
            <div className="py-12 text-center text-xs text-gray-500">
              Belum ada data transaksi penjualan yang cocok.
            </div>
          ) : (
            sales.map((sale) => {
              const isCancelled = sale.status === 'cancelled';

              return (
                <div
                  key={sale.id}
                  className={`p-3 rounded-xl border transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 ${
                    isCancelled ? 'bg-rose-50/50 border-rose-200 opacity-80' : 'bg-white border-gray-200 hover:border-[#55BFD8]'
                  }`}
                >
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="font-bold font-mono text-xs text-gray-900">
                        {sale.transaction_number}
                      </span>
                      <span
                        className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                          isCancelled
                            ? 'bg-rose-100 text-rose-700'
                            : 'bg-emerald-100 text-emerald-800'
                        }`}
                      >
                        {isCancelled ? 'Dibatalkan' : 'Selesai'}
                      </span>
                      <span className="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-[10px] font-bold uppercase">
                        {sale.payment_method}
                      </span>
                    </div>

                    <div className="text-[11px] text-gray-500 mt-1 flex items-center gap-2">
                      <span>{sale.sale_date}</span>
                      <span>&bull;</span>
                      <span>{sale.customer_name || 'Pelanggan Umum'}</span>
                      <span>&bull;</span>
                      <span>Kasir: {sale.cashier?.name || 'Kasir'}</span>
                    </div>

                    {isCancelled && sale.cancellation_reason && (
                      <div className="text-[10px] text-rose-600 mt-0.5 italic">
                        Alasan Batal: {sale.cancellation_reason}
                      </div>
                    )}
                  </div>

                  <div className="flex items-center justify-between sm:justify-end gap-3">
                    <div className="text-right">
                      <div className="text-xs font-bold text-gray-500">Total:</div>
                      <div className="text-sm font-black font-mono text-[#167A93]">
                        {formatRupiah(sale.grand_total)}
                      </div>
                    </div>

                    <div className="flex items-center gap-1.5">
                      <button
                        type="button"
                        onClick={() => handleOpenReceipt(sale.id)}
                        className="px-2.5 py-1.5 bg-[#EAF8FA] hover:bg-[#D7F1F6] text-[#167A93] font-bold text-xs rounded-lg transition cursor-pointer flex items-center gap-1"
                        title="Lihat / Cetak Struk"
                      >
                        <span>🧾</span>
                        <span>Struk</span>
                      </button>

                      {!isCancelled && isAdmin && (
                        <button
                          type="button"
                          onClick={() => setCancellingSaleId(sale.id)}
                          className="px-2 py-1.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 font-bold text-xs rounded-lg transition cursor-pointer"
                          title="Batalkan / Void Transaksi"
                        >
                          Batal
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Modal Prompt Alasan Batal Transaksi (Jika Admin klik Batal) */}
        {cancellingSaleId && (
          <div className="p-4 bg-rose-50 border-t border-rose-200">
            <h4 className="text-xs font-bold text-rose-900 mb-1">
              Konfirmasi Pembatalan Transaksi #{cancellingSaleId}
            </h4>
            <p className="text-[11px] text-rose-700 mb-2">
              Stok barang akan dikembalikan ke persediaan dan jurnal akuntansi penjualan akan dibalik otomatis.
            </p>
            <form onSubmit={handleConfirmCancel} className="flex gap-2">
              <input
                type="text"
                required
                value={cancellationReason}
                onChange={(e) => setCancellationReason(e.target.value)}
                placeholder="Tulis alasan pembatalan / retur..."
                className="flex-1 px-3 py-1.5 bg-white border border-rose-300 rounded-lg text-xs text-gray-900 focus:outline-none"
              />
              <button
                type="button"
                onClick={() => setCancellingSaleId(null)}
                className="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded-lg"
              >
                Tutup
              </button>
              <button
                type="submit"
                disabled={isSubmittingCancel}
                className="px-3.5 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition cursor-pointer"
              >
                {isSubmittingCancel ? 'Memproses...' : 'Ya, Batalkan'}
              </button>
            </form>
          </div>
        )}

        {/* Footer */}
        <div className="p-3 bg-gray-50 border-t border-gray-200 flex justify-end">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200 rounded-xl transition cursor-pointer"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  );
}

export default SalesHistoryModal;
