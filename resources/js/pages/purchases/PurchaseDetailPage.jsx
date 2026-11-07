import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import purchaseService from '../../services/purchaseService';
import { formatRupiah } from '../../utils/currency';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { IconPurchase } from '../../components/common/Icons';

export function PurchaseDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [purchase, setPurchase] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [toast, setToast] = useState(null);

  // Modals / Actions
  const [isReceiving, setIsReceiving] = useState(false);
  const [isPayDebtOpen, setIsPayDebtOpen] = useState(false);
  const [debtAmount, setDebtAmount] = useState('');
  const [debtMethod, setDebtMethod] = useState('cash');
  const [debtNotes, setDebtNotes] = useState('');
  const [isPayingDebt, setIsPayingDebt] = useState(false);

  const [isCancelOpen, setIsCancelOpen] = useState(false);
  const [cancelReason, setCancelReason] = useState('');
  const [isCancelling, setIsCancelling] = useState(false);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchDetail = useCallback(async () => {
    setIsLoading(true);
    try {
      const response = await purchaseService.getPurchase(id);
      if (response.success) {
        setPurchase(response.data);
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal memuat detail pembelian', 'error');
    } finally {
      setIsLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetchDetail();
  }, [fetchDetail]);

  const handleReceivePurchase = async () => {
    setIsReceiving(true);
    try {
      await purchaseService.receivePurchase(id);
      showToast('Barang berhasil dikonfirmasi diterima! Stok & HPP telah diperbarui.');
      fetchDetail();
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal mengonfirmasi penerimaan barang', 'error');
    } finally {
      setIsReceiving(false);
    }
  };

  const handlePayDebtSubmit = async (e) => {
    e.preventDefault();
    const amount = Number(debtAmount);
    if (!amount || amount <= 0) {
      showToast('Masukkan nominal pembayaran yang valid', 'error');
      return;
    }

    setIsPayingDebt(true);
    try {
      await purchaseService.payDebt(id, {
        amount,
        payment_method: debtMethod,
        notes: debtNotes,
      });
      showToast('Pembayaran utang berhasil dicatat!');
      setIsPayDebtOpen(false);
      setDebtAmount('');
      setDebtNotes('');
      fetchDetail();
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal memproses pembayaran utang', 'error');
    } finally {
      setIsPayingDebt(false);
    }
  };

  const handleCancelSubmit = async () => {
    setIsCancelling(true);
    try {
      await purchaseService.cancelPurchase(id, { reason: cancelReason });
      showToast('Pembelian berhasil dibatalkan dan stok telah dibalikkan.');
      setIsCancelOpen(false);
      setCancelReason('');
      fetchDetail();
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal membatalkan pembelian', 'error');
    } finally {
      setIsCancelling(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center p-12 text-xs text-[#577782]">
        Memuat detail pembelian...
      </div>
    );
  }

  if (!purchase) {
    return (
      <div className="p-8 text-center bg-white rounded-2xl border border-[#D7EBEF]">
        <p className="text-xs text-rose-500 font-semibold">Data pembelian tidak ditemukan.</p>
        <button
          onClick={() => navigate('/admin/purchases')}
          className="mt-4 px-4 py-2 bg-[#27C5E5] text-white rounded-xl text-xs font-semibold"
        >
          Kembali ke Daftar
        </button>
      </div>
    );
  }

  const remainingDebt = Math.max(
    0,
    roundNumber(Number(purchase.grand_total) - Number(purchase.paid_amount))
  );

  function roundNumber(num) {
    return Math.round((num + Number.EPSILON) * 100) / 100;
  }

  return (
    <div className="space-y-6">
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

      {/* Header Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div className="flex items-center gap-2 text-xs text-[#577782] mb-1">
            <Link to="/admin/purchases" className="hover:text-[#167A93]">
              Pembelian
            </Link>
            <span>/</span>
            <span className="font-mono text-[#16323B] font-semibold">{purchase.invoice_number}</span>
          </div>
          <h1 className="text-xl font-bold text-[#16323B] flex items-center gap-2.5">
            <span className="p-2 bg-[#EAF8FA] text-[#167A93] rounded-xl border border-[#38BFE0]/30">
              <IconPurchase className="w-5 h-5" />
            </span>
            Detail Pembelian {purchase.invoice_number}
          </h1>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-wrap items-center gap-2">
          {purchase.status === 'draft' && (
            <button
              onClick={handleReceivePurchase}
              disabled={isReceiving}
              className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition disabled:opacity-50"
            >
              {isReceiving ? 'Memproses...' : '✓ Konfirmasi Terima Barang'}
            </button>
          )}

          {purchase.payment_method === 'tempo' && remainingDebt > 0 && purchase.status !== 'cancelled' && (
            <button
              onClick={() => {
                setDebtAmount(remainingDebt);
                setIsPayDebtOpen(true);
              }}
              className="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
            >
              💵 Bayar Sisa Utang
            </button>
          )}

          {purchase.status !== 'cancelled' && (
            <button
              onClick={() => setIsCancelOpen(true)}
              className="px-4 py-2 text-rose-600 hover:bg-rose-50 border border-rose-200 font-semibold text-xs rounded-xl transition"
            >
              Batalkan Transaksi
            </button>
          )}
        </div>
      </div>

      {/* Status Banner */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
          <div className="text-[11px] font-semibold text-[#577782]">Status Fisik Barang</div>
          <div className="mt-1">
            {purchase.status === 'received' ? (
              <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                ✓ Diterima di Toko
              </span>
            ) : purchase.status === 'draft' ? (
              <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-200">
                ⏳ Draft (Menunggu Barang)
              </span>
            ) : (
              <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
                ✕ Dibatalkan
              </span>
            )}
          </div>
          {purchase.received_at && (
            <div className="text-[10px] text-[#577782] mt-1.5">
              Diterima: {new Date(purchase.received_at).toLocaleString('id-ID')}
            </div>
          )}
        </div>

        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
          <div className="text-[11px] font-semibold text-[#577782]">Status Pembayaran</div>
          <div className="mt-1">
            {purchase.payment_status === 'paid' ? (
              <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                ✓ Lunas
              </span>
            ) : purchase.payment_status === 'partial' ? (
              <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-200">
                Sebagian (DP)
              </span>
            ) : (
              <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
                Belum Bayar
              </span>
            )}
          </div>
          <div className="text-[11px] text-[#577782] mt-1.5 capitalize">
            Metode: <strong className="text-[#16323B]">{purchase.payment_method}</strong>
          </div>
        </div>

        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
          <div className="text-[11px] font-semibold text-[#577782]">Total Pembelian</div>
          <div className="text-lg font-bold font-mono text-[#167A93] mt-0.5">
            {formatRupiah(purchase.grand_total)}
          </div>
          <div className="text-[11px] text-[#577782] mt-1">
            Terbayar: <span className="font-mono">{formatRupiah(purchase.paid_amount)}</span>
          </div>
        </div>

        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
          <div className="text-[11px] font-semibold text-[#577782]">Sisa Utang Tempo</div>
          <div
            className={`text-lg font-bold font-mono mt-0.5 ${
              remainingDebt > 0 ? 'text-rose-600' : 'text-emerald-600'
            }`}
          >
            {formatRupiah(remainingDebt)}
          </div>
          {purchase.due_date && (
            <div className="text-[11px] text-[#577782] mt-1">
              Jatuh Tempo: {purchase.due_date}
            </div>
          )}
        </div>
      </div>

      {/* Supplier & Invoice Info */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 className="text-xs font-bold uppercase tracking-wider text-[#577782] mb-2">
            Informasi Mitra Supplier
          </h3>
          <div className="space-y-1 text-xs">
            <div className="text-base font-bold text-[#16323B]">
              {purchase.supplier?.name || '-'}
            </div>
            <div className="text-[#577782]">
              Kode: <span className="font-mono font-semibold">{purchase.supplier?.code}</span>
            </div>
            {purchase.supplier?.contact_person && (
              <div className="text-[#577782]">PIC: {purchase.supplier.contact_person}</div>
            )}
            {purchase.supplier?.phone && (
              <div className="text-[#577782]">Telepon: {purchase.supplier.phone}</div>
            )}
            {purchase.supplier?.address && (
              <div className="text-[#577782] mt-1">{purchase.supplier.address}</div>
            )}
          </div>
        </div>

        <div>
          <h3 className="text-xs font-bold uppercase tracking-wider text-[#577782] mb-2">
            Data Transaksi
          </h3>
          <div className="space-y-1.5 text-xs">
            <div className="flex justify-between py-1 border-b border-[#F0F9FB]">
              <span className="text-[#577782]">Nomor Invoice:</span>
              <span className="font-mono font-bold text-[#16323B]">{purchase.invoice_number}</span>
            </div>
            <div className="flex justify-between py-1 border-b border-[#F0F9FB]">
              <span className="text-[#577782]">Tanggal Transaksi:</span>
              <span className="font-semibold text-[#16323B]">{purchase.purchase_date}</span>
            </div>
            <div className="flex justify-between py-1 border-b border-[#F0F9FB]">
              <span className="text-[#577782]">Dibuat Oleh:</span>
              <span className="font-semibold text-[#16323B]">{purchase.creator?.name || 'Staf'}</span>
            </div>
            {purchase.notes && (
              <div className="py-1 text-[#577782]">
                <strong>Catatan:</strong> {purchase.notes}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Item List Table */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
        <h3 className="text-sm font-bold text-[#16323B] mb-3">
          Rincian Barang yang Dibeli
        </h3>

        <div className="overflow-x-auto">
          <table className="w-full text-xs text-left">
            <thead>
              <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold">
                <th className="py-2.5 px-3">No</th>
                <th className="py-2.5 px-3">SKU</th>
                <th className="py-2.5 px-3">Nama Produk</th>
                <th className="py-2.5 px-3 text-right">Kuantitas</th>
                <th className="py-2.5 px-3">Satuan</th>
                <th className="py-2.5 px-3 text-right">Harga Beli Satuan</th>
                <th className="py-2.5 px-3 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#EAF4F6]">
              {purchase.items?.map((item, idx) => (
                <tr key={item.id} className="hover:bg-[#F8FDFE]">
                  <td className="py-2.5 px-3 text-[#577782]">{idx + 1}</td>
                  <td className="py-2.5 px-3 font-mono font-semibold text-[#167A93]">
                    {item.product?.sku || '-'}
                  </td>
                  <td className="py-2.5 px-3 font-semibold text-[#16323B]">
                    {item.product?.name || 'Produk'}
                  </td>
                  <td className="py-2.5 px-3 text-right font-mono font-bold">
                    {Number(item.quantity).toLocaleString('id-ID')}
                  </td>
                  <td className="py-2.5 px-3 text-[#577782]">
                    {item.product?.unit?.symbol || 'pcs'}
                  </td>
                  <td className="py-2.5 px-3 text-right font-mono">
                    {formatRupiah(item.purchase_price)}
                  </td>
                  <td className="py-2.5 px-3 text-right font-mono font-bold text-[#16323B]">
                    {formatRupiah(item.subtotal)}
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="border-t-2 border-[#D7EBEF] font-semibold text-[#16323B]">
                <td colSpan="6" className="py-2.5 px-3 text-right">Subtotal:</td>
                <td className="py-2.5 px-3 text-right font-mono font-bold">
                  {formatRupiah(purchase.subtotal)}
                </td>
              </tr>
              {Number(purchase.discount_amount) > 0 && (
                <tr className="text-[#577782]">
                  <td colSpan="6" className="py-1 px-3 text-right">Potongan Diskon:</td>
                  <td className="py-1 px-3 text-right font-mono text-rose-500">
                    -{formatRupiah(purchase.discount_amount)}
                  </td>
                </tr>
              )}
              {Number(purchase.tax_amount) > 0 && (
                <tr className="text-[#577782]">
                  <td colSpan="6" className="py-1 px-3 text-right">Pajak Tambahan:</td>
                  <td className="py-1 px-3 text-right font-mono">
                    +{formatRupiah(purchase.tax_amount)}
                  </td>
                </tr>
              )}
              <tr className="bg-[#EAF8FA] text-[#167A93] font-bold text-sm">
                <td colSpan="6" className="py-3 px-3 text-right">Total Akhir Pembelian:</td>
                <td className="py-3 px-3 text-right font-mono">
                  {formatRupiah(purchase.grand_total)}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      {/* Debt Payments Section (if tempo) */}
      {purchase.debt_payments && purchase.debt_payments.length > 0 && (
        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
          <h3 className="text-sm font-bold text-[#16323B] mb-3">
            Riwayat Cicilan Pelunasan Utang
          </h3>
          <div className="overflow-x-auto">
            <table className="w-full text-xs text-left">
              <thead>
                <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold">
                  <th className="py-2 px-3">No Bayar</th>
                  <th className="py-2 px-3">Tanggal</th>
                  <th className="py-2 px-3">Metode</th>
                  <th className="py-2 px-3">Catatan</th>
                  <th className="py-2 px-3">Dicatat Oleh</th>
                  <th className="py-2 px-3 text-right">Nominal</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#EAF4F6]">
                {purchase.debt_payments.map((p) => (
                  <tr key={p.id}>
                    <td className="py-2 px-3 font-mono font-semibold text-[#167A93]">{p.payment_number}</td>
                    <td className="py-2 px-3">{p.payment_date}</td>
                    <td className="py-2 px-3 capitalize">{p.payment_method}</td>
                    <td className="py-2 px-3 text-[#577782]">{p.notes || '-'}</td>
                    <td className="py-2 px-3">{p.creator?.name || 'Staf'}</td>
                    <td className="py-2 px-3 text-right font-mono font-bold text-emerald-600">
                      {formatRupiah(p.amount)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Accounting Journal Entries */}
      {purchase.journal_entries && purchase.journal_entries.length > 0 && (
        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
          <h3 className="text-sm font-bold text-[#16323B] mb-3">
            Jurnal Akuntansi Pembelian
          </h3>
          <div className="space-y-4">
            {purchase.journal_entries.map((j) => (
              <div key={j.id} className="border border-[#EAF4F6] rounded-xl overflow-hidden">
                <div className="px-4 py-2 bg-[#F8FDFE] border-b border-[#EAF4F6] flex justify-between items-center text-xs">
                  <div>
                    <span className="font-mono font-bold text-[#167A93]">{j.journal_number}</span> &bull; Tanggal: {j.journal_date}
                  </div>
                  <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${j.status === 'posted' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`}>
                    {j.status.toUpperCase()}
                  </span>
                </div>
                <table className="w-full text-xs">
                  <thead>
                    <tr className="border-b border-[#EAF4F6] text-[#577782]">
                      <th className="py-2 px-4 text-left">Akun</th>
                      <th className="py-2 px-4 text-left">Keterangan</th>
                      <th className="py-2 px-4 text-right">Debit</th>
                      <th className="py-2 px-4 text-right">Kredit</th>
                    </tr>
                  </thead>
                  <tbody>
                    {j.details?.map((d) => (
                      <tr key={d.id} className="border-b border-[#F8FDFE]">
                        <td className="py-1.5 px-4 font-mono">
                          {d.account?.account_code} - {d.account?.account_name}
                        </td>
                        <td className="py-1.5 px-4 text-[#577782]">{d.description}</td>
                        <td className="py-1.5 px-4 text-right font-mono">
                          {Number(d.debit) > 0 ? formatRupiah(d.debit) : '-'}
                        </td>
                        <td className="py-1.5 px-4 text-right font-mono">
                          {Number(d.credit) > 0 ? formatRupiah(d.credit) : '-'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Pay Debt Modal */}
      {isPayDebtOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/40 backdrop-blur-xs animate-backdrop">
          <div className="bg-white rounded-2xl border border-[#D7EBEF] shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 className="text-base font-bold text-[#16323B]">Bayar Sisa Utang Supplier</h3>
            <p className="text-xs text-[#577782]">
              Sisa utang untuk invoice ini: <strong className="text-rose-600 font-mono">{formatRupiah(remainingDebt)}</strong>
            </p>

            <form onSubmit={handlePayDebtSubmit} className="space-y-3">
              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">
                  Nominal Pembayaran (Rp) <span className="text-rose-500">*</span>
                </label>
                <input
                  type="number"
                  min="1"
                  max={remainingDebt}
                  value={debtAmount}
                  onChange={(e) => setDebtAmount(e.target.value)}
                  className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs font-mono font-bold text-[#16323B]"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">
                  Metode Pembayaran
                </label>
                <select
                  value={debtMethod}
                  onChange={(e) => setDebtMethod(e.target.value)}
                  className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                >
                  <option value="cash">Tunai (Kas Toko)</option>
                  <option value="transfer">Transfer Bank Operasional</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">
                  Catatan Pembayaran
                </label>
                <input
                  type="text"
                  placeholder="Keterangan transfer / bukti setor..."
                  value={debtNotes}
                  onChange={(e) => setDebtNotes(e.target.value)}
                  className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                />
              </div>

              <div className="pt-3 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setIsPayDebtOpen(false)}
                  disabled={isPayingDebt}
                  className="px-4 py-2 text-xs font-semibold text-[#577782] hover:bg-[#F0F9FB] rounded-xl"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={isPayingDebt}
                  className="px-5 py-2 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs"
                >
                  {isPayingDebt ? 'Memproses...' : 'Simpan Pembayaran'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Cancel Confirm Dialog */}
      {isCancelOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/40 backdrop-blur-xs animate-backdrop">
          <div className="bg-white rounded-2xl border border-[#D7EBEF] shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 className="text-base font-bold text-rose-600">Batalkan Transaksi Pembelian?</h3>
            <p className="text-xs text-[#577782]">
              Jika barang sudah berstatus diterima, stok produk akan ditarik kembali dan jurnal akuntansi akan dibatalkan (reversal).
            </p>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Alasan Pembatalan:
              </label>
              <textarea
                rows="2"
                placeholder="Contoh: Barang rusak saat tiba / transaksi salah input..."
                value={cancelReason}
                onChange={(e) => setCancelReason(e.target.value)}
                className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
              />
            </div>

            <div className="pt-3 flex items-center justify-end gap-2">
              <button
                type="button"
                onClick={() => setIsCancelOpen(false)}
                disabled={isCancelling}
                className="px-4 py-2 text-xs font-semibold text-[#577782] hover:bg-[#F0F9FB] rounded-xl"
              >
                Kembali
              </button>
              <button
                type="button"
                onClick={handleCancelSubmit}
                disabled={isCancelling}
                className="px-5 py-2 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs"
              >
                {isCancelling ? 'Membatalkan...' : 'Ya, Batalkan Pembelian'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default PurchaseDetailPage;
