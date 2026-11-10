import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import purchaseService from '../../services/purchaseService';
import { formatRupiah } from '../../utils/currency';
import { IconPurchase, IconPrinter } from '../../components/common/Icons';

export function PurchaseDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

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

  // Accordion for accounting journals (compact)
  const [showJournals, setShowJournals] = useState(false);

  const purchasesPath = user?.role === 'staff' ? '/staff/purchases' : '/admin/purchases';

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
      showToast('✓ Barang berhasil dikonfirmasi diterima! Stok & HPP telah diperbarui.');
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
      showToast('✓ Pembayaran utang berhasil dicatat!');
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

  const handlePrint = () => {
    window.print();
  };

  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center p-16 text-xs text-[#577782] bg-white rounded-2xl border border-[#D7EBEF] max-w-4xl mx-auto">
        <div className="animate-spin text-2xl mb-2">🔄</div>
        Memuat nota pembelian...
      </div>
    );
  }

  if (!purchase) {
    return (
      <div className="p-8 text-center bg-white rounded-2xl border border-[#D7EBEF] max-w-md mx-auto">
        <p className="text-xs text-rose-500 font-semibold mb-3">Data pembelian tidak ditemukan.</p>
        <button
          onClick={() => navigate(purchasesPath)}
          className="px-4 py-2 bg-[#167A93] hover:bg-[#27C5E5] text-white rounded-xl text-xs font-semibold cursor-pointer"
        >
          ← Kembali ke Daftar Pembelian
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
    <div className="space-y-4 max-w-4xl mx-auto">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2 print:hidden ${toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-700'
              : 'bg-emerald-50 border-emerald-200 text-emerald-700'
            }`}
        >
          <span>{toast.type === 'error' ? '⚠️' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      {/* Top Action & Navigation Bar (Hidden on Print) */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white border border-[#D7EBEF] rounded-2xl p-3.5 shadow-xs print:hidden">
        {/* Tombol Kembali ke Daftar Pembelian */}
        <button
          type="button"
          onClick={() => navigate(purchasesPath)}
          className="inline-flex items-center gap-2 text-xs font-bold text-[#167A93] hover:text-[#16323B] bg-[#EAF8FA] hover:bg-[#D7EBEF] px-3.5 py-2 rounded-xl transition cursor-pointer shrink-0"
        >
          <span>←</span>
          <span>Kembali ke Daftar</span>
        </button>

        {/* Action Buttons Group */}
        <div className="flex flex-wrap items-center gap-2">
          {/* Tombol Konfirmasi Terima Barang (jika draft) */}
          {purchase.status === 'draft' && (
            <button
              onClick={handleReceivePurchase}
              disabled={isReceiving}
              className="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition disabled:opacity-50 cursor-pointer"
            >
              {isReceiving ? 'Memproses...' : '✓ Konfirmasi Terima'}
            </button>
          )}

          {/* Tombol Bayar Sisa Utang (jika tempo dan ada utang) */}
          {purchase.payment_method === 'tempo' && remainingDebt > 0 && purchase.status !== 'cancelled' && (
            <button
              onClick={() => {
                setDebtAmount(remainingDebt);
                setIsPayDebtOpen(true);
              }}
              className="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer"
            >
              💵 Bayar Sisa Utang
            </button>
          )}

          {/* Tombol Cetak Nota */}
          <button
            type="button"
            onClick={handlePrint}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#F8FDFE] hover:bg-[#EAF8FA] text-[#167A93] border border-[#D7EBEF] font-semibold text-xs rounded-xl transition cursor-pointer shadow-2xs"
            title="Cetak Nota Pembelian"
          >
            <IconPrinter className="w-3.5 h-3.5" />
            <span>Cetak Nota</span>
          </button>

          {/* Tombol Selesai / Simpan */}
          <button
            type="button"
            onClick={() => navigate(purchasesPath)}
            className="px-4 py-2 bg-[#27C5E5] hover:bg-[#167A93] text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
            title="Selesai dan kembali ke daftar pembelian"
          >
            ✓ Selesai
          </button>

          {/* Tombol Batalkan Transaksi */}
          {purchase.status !== 'cancelled' && (
            <button
              onClick={() => setIsCancelOpen(true)}
              className="px-3 py-2 text-rose-600 hover:bg-rose-50 border border-rose-200 font-semibold text-xs rounded-xl transition cursor-pointer ml-1"
              title="Batalkan transaksi pembelian ini"
            >
              Batalkan
            </button>
          )}
        </div>
      </div>

      {/* ========================================================
          CARD UTAMA — FORMAT NOTA / INVOICE PEMBELIAN RINGKAS & ELEGAN
         ======================================================== */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl shadow-sm overflow-hidden p-6 sm:p-8 space-y-6 print:p-0 print:border-none print:shadow-none">
        {/* 1. Header Ringkas Nota */}
        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 pb-5 border-b border-[#D7EBEF]">
          <div>
            <div className="flex items-center gap-2 mb-1">
              <span className="p-1.5 bg-[#EAF8FA] text-[#167A93] rounded-lg border border-[#38BFE0]/30 print:border-none">
                <IconPurchase className="w-4 h-4" />
              </span>
              <span className="text-xs font-bold tracking-wider text-[#577782] uppercase">
                FinToko &bull; Nota Pembelian
              </span>
            </div>
            <h2 className="text-xl font-extrabold text-[#16323B] font-mono tracking-tight">
              {purchase.invoice_number}
            </h2>
            <div className="text-xs text-[#577782] mt-0.5">
              Tanggal Transaksi: <strong className="text-[#16323B]">{purchase.purchase_date}</strong>
            </div>
          </div>

          {/* Status Badges Ringkas & Elegan (Menggantikan 4 Kotak Raksasa yang Boros Ruang) */}
          <div className="flex flex-wrap sm:flex-col sm:items-end gap-2">
            {/* Badge Status Fisik Barang */}
            <div>
              {purchase.status === 'received' ? (
                <span className="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                  <span>✓</span> Barang Diterima
                </span>
              ) : purchase.status === 'draft' ? (
                <span className="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                  <span>⏳</span> Menunggu Barang (Draft)
                </span>
              ) : (
                <span className="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs">
                  <span>✕</span> Dibatalkan
                </span>
              )}
            </div>

            {/* Badge Status Pembayaran */}
            <div>
              {purchase.payment_status === 'paid' ? (
                <span className="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                  <span>✓</span> Lunas ({purchase.payment_method?.toUpperCase()})
                </span>
              ) : purchase.payment_status === 'partial' ? (
                <span className="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs">
                  Sebagian (Sisa: {formatRupiah(remainingDebt)})
                </span>
              ) : (
                <span className="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs">
                  Belum Lunas ({formatRupiah(remainingDebt)})
                </span>
              )}
            </div>
          </div>
        </div>

        {/* 2. Informasi Supplier & Transaksi Toko (Tersusun Berdampingan Secara Padat) */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-[#F8FDFE] border border-[#EAF4F6] rounded-xl p-4 text-xs">
          {/* Kolom Kiri: Mitra Supplier */}
          <div className="space-y-1">
            <span className="font-bold text-[10px] tracking-wider text-[#167A93] uppercase">
              Mitra Supplier / Distributor:
            </span>
            <div className="text-sm font-bold text-[#16323B]">
              {purchase.supplier?.name || 'Supplier Umum'}
            </div>
            <div className="text-[#577782]">
              Kode: <span className="font-mono font-semibold">{purchase.supplier?.code || '-'}</span>
              {purchase.supplier?.phone && <span> &bull; Telp: {purchase.supplier.phone}</span>}
            </div>
            {purchase.supplier?.address && (
              <div className="text-[#8FA8B0] text-[11px] leading-relaxed">
                {purchase.supplier.address}
              </div>
            )}
          </div>

          {/* Kolom Kanan: Data Transaksi Toko */}
          <div className="space-y-1 sm:border-l sm:border-[#EAF4F6] sm:pl-4">
            <span className="font-bold text-[10px] tracking-wider text-[#167A93] uppercase">
              Detail Transaksi Toko:
            </span>
            <div className="flex justify-between py-0.5">
              <span className="text-[#577782]">Metode Bayar:</span>
              <span className="font-bold text-[#16323B] uppercase">{purchase.payment_method}</span>
            </div>
            <div className="flex justify-between py-0.5">
              <span className="text-[#577782]">Dicatat Oleh:</span>
              <span className="font-semibold text-[#16323B]">{purchase.creator?.name || 'Administrator'}</span>
            </div>
            {purchase.due_date && (
              <div className="flex justify-between py-0.5">
                <span className="text-[#577782]">Jatuh Tempo:</span>
                <span className="font-semibold text-rose-600">{purchase.due_date}</span>
              </div>
            )}
            {purchase.notes && (
              <div className="text-[11px] text-[#577782] pt-0.5">
                Catatan: <em>{purchase.notes}</em>
              </div>
            )}
          </div>
        </div>

        {/* 3. Tabel Rincian Barang yang Dibeli (Ringkas, Rapi, & Padat) */}
        <div className="space-y-2">
          <div className="text-xs font-bold uppercase tracking-wider text-[#16323B]">
            Rincian Barang yang Dibeli
          </div>

          <div className="overflow-x-auto rounded-xl border border-[#D7EBEF]">
            <table className="w-full text-left text-xs border-collapse">
              <thead>
                <tr className="bg-[#F0F9FB] border-b border-[#D7EBEF] text-[#577782] font-semibold">
                  <th className="py-2.5 px-3 text-center w-10">No</th>
                  <th className="py-2.5 px-3">SKU / Nama Produk</th>
                  <th className="py-2.5 px-3 text-center w-32">Kuantitas & Satuan</th>
                  <th className="py-2.5 px-3 text-right w-32">Harga Beli Satuan</th>
                  <th className="py-2.5 px-3 text-right w-36">Subtotal</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#F0F9FB]">
                {purchase.items?.map((item, idx) => (
                  <tr key={item.id} className="hover:bg-[#F8FDFE]">
                    <td className="py-2.5 px-3 text-center text-[#8FA8B0] font-mono">{idx + 1}</td>
                    <td className="py-2.5 px-3">
                      <div className="font-bold text-[#16323B]">{item.product?.name || 'Produk'}</div>
                      <div className="text-[10px] font-mono text-[#167A93]">
                        {item.product?.sku || '-'}
                      </div>
                    </td>
                    <td className="py-2.5 px-3 text-center font-mono font-bold text-[#16323B]">
                      {Number(item.quantity).toLocaleString('id-ID')} <span className="font-normal text-xs text-[#577782]">{item.product?.unit?.symbol || 'pcs'}</span>
                    </td>
                    <td className="py-2.5 px-3 text-right font-mono text-[#577782]">
                      {formatRupiah(item.purchase_price)}
                    </td>
                    <td className="py-2.5 px-3 text-right font-mono font-bold text-[#16323B]">
                      {formatRupiah(item.subtotal)}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="border-t-2 border-[#D7EBEF] text-xs">
                {/* Subtotal */}
                <tr className="text-[#577782]">
                  <td colSpan="4" className="py-2 px-3 text-right font-semibold">Subtotal:</td>
                  <td className="py-2 px-3 text-right font-mono font-semibold text-[#16323B]">
                    {formatRupiah(purchase.subtotal)}
                  </td>
                </tr>

                {/* Diskon jika ada */}
                {Number(purchase.discount_amount) > 0 && (
                  <tr className="text-[#577782]">
                    <td colSpan="4" className="py-1 px-3 text-right font-semibold">Potongan Diskon:</td>
                    <td className="py-1 px-3 text-right font-mono text-rose-600 font-semibold">
                      -{formatRupiah(purchase.discount_amount)}
                    </td>
                  </tr>
                )}

                {/* Pajak jika ada */}
                {Number(purchase.tax_amount) > 0 && (
                  <tr className="text-[#577782]">
                    <td colSpan="4" className="py-1 px-3 text-right font-semibold">Pajak Tambahan:</td>
                    <td className="py-1 px-3 text-right font-mono text-[#16323B]">
                      +{formatRupiah(purchase.tax_amount)}
                    </td>
                  </tr>
                )}

                {/* Total Keseluruhan Nota */}
                <tr className="bg-[#EAF8FA] text-[#167A93] font-bold text-sm">
                  <td colSpan="4" className="py-3 px-3 text-right uppercase tracking-wider">
                    Total Keseluruhan:
                  </td>
                  <td className="py-3 px-3 text-right font-mono text-base font-extrabold text-[#167A93]">
                    {formatRupiah(purchase.grand_total)}
                  </td>
                </tr>

                {/* Rincian Terbayar & Sisa Utang (jika tempo / partial) */}
                {purchase.payment_method === 'tempo' && (
                  <>
                    <tr className="text-xs text-[#577782] bg-[#F8FDFE]">
                      <td colSpan="4" className="py-1.5 px-3 text-right">Nominal Terbayar (DP/Cicilan):</td>
                      <td className="py-1.5 px-3 text-right font-mono font-semibold text-emerald-600">
                        {formatRupiah(purchase.paid_amount)}
                      </td>
                    </tr>
                    <tr className="text-xs bg-[#F8FDFE]">
                      <td colSpan="4" className="py-1.5 px-3 text-right font-bold text-rose-600">Sisa Utang Tempo:</td>
                      <td className="py-1.5 px-3 text-right font-mono font-bold text-rose-600">
                        {formatRupiah(remainingDebt)}
                      </td>
                    </tr>
                  </>
                )}
              </tfoot>
            </table>
          </div>
        </div>

        {/* 4. Riwayat Cicilan Pelunasan Utang (Jika ada pembayaran tempo) */}
        {purchase.debt_payments && purchase.debt_payments.length > 0 && (
          <div className="space-y-2 pt-2 border-t border-[#EAF4F6]">
            <div className="text-xs font-bold uppercase tracking-wider text-[#16323B]">
              Riwayat Pembayaran Cicilan Utang
            </div>
            <div className="overflow-x-auto rounded-xl border border-[#D7EBEF]">
              <table className="w-full text-xs text-left">
                <thead>
                  <tr className="bg-[#F0F9FB] border-b border-[#D7EBEF] text-[#577782] font-semibold">
                    <th className="py-2 px-3">No Bayar</th>
                    <th className="py-2 px-3">Tanggal</th>
                    <th className="py-2 px-3">Metode</th>
                    <th className="py-2 px-3">Catatan</th>
                    <th className="py-2 px-3 text-right">Nominal Dibayar</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#F0F9FB]">
                  {purchase.debt_payments.map((p) => (
                    <tr key={p.id} className="hover:bg-[#F8FDFE]">
                      <td className="py-2 px-3 font-mono font-semibold text-[#167A93]">{p.payment_number}</td>
                      <td className="py-2 px-3">{p.payment_date}</td>
                      <td className="py-2 px-3 uppercase text-[11px]">{p.payment_method}</td>
                      <td className="py-2 px-3 text-[#577782]">{p.notes || '-'}</td>
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

        {/* 5. Jurnal Akuntansi (Dibuat Kompak / Toggle agar Tidak Memakan Tempat) */}
        {purchase.journal_entries && purchase.journal_entries.length > 0 && (
          <div className="pt-2 border-t border-[#EAF4F6] print:hidden">
            <button
              type="button"
              onClick={() => setShowJournals(!showJournals)}
              className="text-xs font-semibold text-[#577782] hover:text-[#167A93] flex items-center gap-1.5 cursor-pointer"
            >
              <span>{showJournals ? '▼' : '▶'}</span>
              <span>Jurnal Akuntansi Pembelian ({purchase.journal_entries.length} jurnal)</span>
            </button>

            {showJournals && (
              <div className="mt-3 space-y-3">
                {purchase.journal_entries.map((j) => (
                  <div key={j.id} className="border border-[#EAF4F6] rounded-xl overflow-hidden text-xs">
                    <div className="px-3.5 py-1.5 bg-[#F8FDFE] border-b border-[#EAF4F6] flex justify-between items-center">
                      <span className="font-mono font-bold text-[#167A93]">
                        {j.journal_number} &bull; {j.journal_date}
                      </span>
                      <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">
                        {j.status.toUpperCase()}
                      </span>
                    </div>
                    <table className="w-full text-xs">
                      <thead>
                        <tr className="border-b border-[#EAF4F6] text-[#577782] bg-white">
                          <th className="py-1.5 px-3 text-left">Akun</th>
                          <th className="py-1.5 px-3 text-left">Keterangan</th>
                          <th className="py-1.5 px-3 text-right">Debit</th>
                          <th className="py-1.5 px-3 text-right">Kredit</th>
                        </tr>
                      </thead>
                      <tbody>
                        {j.details?.map((d) => (
                          <tr key={d.id} className="border-b border-[#F8FDFE]">
                            <td className="py-1.5 px-3 font-mono">
                              {d.account?.account_code} - {d.account?.account_name}
                            </td>
                            <td className="py-1.5 px-3 text-[#577782]">{d.description}</td>
                            <td className="py-1.5 px-3 text-right font-mono">
                              {Number(d.debit) > 0 ? formatRupiah(d.debit) : '-'}
                            </td>
                            <td className="py-1.5 px-3 text-right font-mono">
                              {Number(d.credit) > 0 ? formatRupiah(d.credit) : '-'}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </div>

      {/* Tombol Navigasi Bawah Halaman (Hidden on Print) */}
      <div className="flex items-center justify-between p-3.5 bg-white border border-[#D7EBEF] rounded-2xl shadow-xs print:hidden">
        <button
          type="button"
          onClick={() => navigate(purchasesPath)}
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-[#577782] hover:text-[#16323B] px-3 py-2 rounded-xl hover:bg-[#F0F9FB] transition cursor-pointer"
        >
          <span>←</span>
          <span>Kembali ke Daftar Pembelian</span>
        </button>

        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={handlePrint}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-[#F8FDFE] hover:bg-[#EAF8FA] text-[#167A93] border border-[#D7EBEF] font-semibold text-xs rounded-xl transition cursor-pointer"
          >
            <IconPrinter className="w-3.5 h-3.5" />
            <span>Cetak Nota</span>
          </button>

          <button
            type="button"
            onClick={() => navigate(purchasesPath)}
            className="px-5 py-2 bg-[#27C5E5] hover:bg-[#167A93] text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
          >
            ✓ Selesai
          </button>
        </div>
      </div>

      {/* Modal Bayar Sisa Utang */}
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
                  className="px-4 py-2 text-xs font-semibold text-[#577782] hover:bg-[#F0F9FB] rounded-xl cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={isPayingDebt}
                  className="px-5 py-2 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs cursor-pointer"
                >
                  {isPayingDebt ? 'Memproses...' : 'Simpan Pembayaran'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Batalkan Transaksi */}
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
                className="px-4 py-2 text-xs font-semibold text-[#577782] hover:bg-[#F0F9FB] rounded-xl cursor-pointer"
              >
                Kembali
              </button>
              <button
                type="button"
                onClick={handleCancelSubmit}
                disabled={isCancelling}
                className="px-5 py-2 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs cursor-pointer"
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
