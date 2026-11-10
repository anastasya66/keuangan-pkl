import React, { useState, useEffect, useRef } from 'react';
import posService from '../../services/posService';
import { formatRupiah } from '../../utils/currency';

export function QrisPaymentModal({ grandTotal, customerName = 'Pelanggan Umum', itemsCount = 1, onClose, onPaymentSuccess }) {
  const [order, setOrder] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [secondsRemaining, setSecondsRemaining] = useState(900); // 15 mins default
  const [isPaid, setIsPaid] = useState(false);
  const [isSimulating, setIsSimulating] = useState(false);
  const pollIntervalRef = useRef(null);

  // 1. Generate QRIS Dynamic Code saat Modal Dibuka
  useEffect(() => {
    let isMounted = true;

    async function initQris() {
      setIsLoading(true);
      setError(null);
      try {
        const res = await posService.createQrisPayment({
          amount: grandTotal,
          customer_name: customerName,
          items_count: itemsCount,
        });

        if (isMounted && res.success && res.data) {
          setOrder(res.data);
          setSecondsRemaining(res.data.expires_in_seconds || 900);
        } else {
          throw new Error(res.message || 'Gagal menghasilkan kode QRIS');
        }
      } catch (err) {
        if (isMounted) {
          setError(err.response?.data?.message || err.message || 'Gagal membuat QRIS');
        }
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    initQris();

    return () => {
      isMounted = false;
    };
  }, [grandTotal, customerName, itemsCount]);

  // 2. Countdown Timer (1 detik per tick)
  useEffect(() => {
    if (!order || isPaid) return;

    const timer = setInterval(() => {
      setSecondsRemaining((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [order, isPaid]);

  // 3. Polling Status Pembayaran Setiap 3 Detik
  useEffect(() => {
    if (!order?.order_id || isPaid || secondsRemaining <= 0) return;

    const poll = async () => {
      try {
        const res = await posService.checkPaymentStatus(order.order_id);
        if (res.success && res.data?.is_paid) {
          setIsPaid(true);
          clearInterval(pollIntervalRef.current);
          setTimeout(() => {
            onPaymentSuccess({
              payment_method: 'qris',
              paid_amount: grandTotal,
              order_id: order.order_id,
            });
          }, 800);
        }
      } catch (e) {
        // Abaikan transient network error saat polling
      }
    };

    pollIntervalRef.current = setInterval(poll, 3000);

    return () => {
      if (pollIntervalRef.current) clearInterval(pollIntervalRef.current);
    };
  }, [order, isPaid, secondsRemaining, grandTotal, onPaymentSuccess]);

  // 4. Simulasi Bayar Berhasil (Demo Mode)
  const handleSimulatePayment = async () => {
    if (!order?.order_id || isSimulating || isPaid) return;
    setIsSimulating(true);
    try {
      const res = await posService.simulatePayment(order.order_id);
      if (res.success && res.data?.is_paid) {
        setIsPaid(true);
        setTimeout(() => {
          onPaymentSuccess({
            payment_method: 'qris',
            paid_amount: grandTotal,
            order_id: order.order_id,
          });
        }, 800);
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal simulasi pembayaran');
    } finally {
      setIsSimulating(false);
    }
  };

  // 5. Batalkan Transaksi
  const handleCancel = async () => {
    if (order?.order_id && !isPaid) {
      try {
        await posService.cancelPayment(order.order_id);
      } catch (e) {}
    }
    onClose();
  };

  // Format detik ke format mm:ss
  const formatTimer = (totalSec) => {
    const mins = Math.floor(totalSec / 60);
    const secs = totalSec % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
      <div className="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full overflow-hidden animate-in fade-in duration-200">
        {/* Header Modal */}
        <div className="p-4 bg-gradient-to-r from-[#167A93] to-[#27C5E5] text-white flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-xl">📱</span>
            <div>
              <h3 className="font-bold text-sm">QRIS Dinamis Interaktif</h3>
              <p className="text-[11px] text-cyan-100">BCA Mobile, GoPay, OVO, Dana, ShopeePay dll</p>
            </div>
          </div>
          <button
            type="button"
            onClick={handleCancel}
            className="text-white/80 hover:text-white p-1 rounded-lg text-lg cursor-pointer"
          >
            ✕
          </button>
        </div>

        <div className="p-5 text-center">
          {isLoading ? (
            <div className="py-12 space-y-3">
              <div className="w-10 h-10 border-4 border-[#27C5E5] border-t-transparent rounded-full animate-spin mx-auto" />
              <p className="text-xs font-semibold text-gray-700">Menghasilkan QRIS Dinamis Toko...</p>
              <p className="text-[11px] text-gray-400">Menghubungkan ke gateway pembayaran</p>
            </div>
          ) : error ? (
            <div className="py-8 space-y-3">
              <span className="text-3xl">⚠️</span>
              <p className="text-xs font-bold text-rose-600">{error}</p>
              <button
                type="button"
                onClick={handleCancel}
                className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-semibold rounded-xl"
              >
                Tutup
              </button>
            </div>
          ) : isPaid ? (
            <div className="py-8 space-y-3">
              <div className="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl animate-bounce">
                ✓
              </div>
              <h4 className="text-base font-bold text-gray-900">Pembayaran QRIS Berhasil!</h4>
              <p className="text-xs text-gray-500">Mencatat transaksi & menyiapkan struk belanja...</p>
            </div>
          ) : (
            <div className="space-y-4">
              {/* Nominal Tagihan */}
              <div>
                <span className="text-[11px] uppercase tracking-wider font-semibold text-gray-500">
                  Total Pembayaran QRIS
                </span>
                <div className="text-2xl font-black font-mono text-[#167A93]">
                  {formatRupiah(grandTotal)}
                </div>
                <div className="text-[10px] text-gray-400 font-mono mt-0.5">
                  Order ID: {order.order_id}
                </div>
              </div>

              {/* Tampilan QR Code Bersih & Jelas */}
              <div className="relative inline-block p-3 bg-white border-2 border-gray-200 rounded-2xl shadow-sm mx-auto">
                {order.qr_image_url ? (
                  <img
                    src={order.qr_image_url}
                    alt="QRIS Code"
                    className="w-52 h-52 mx-auto object-contain"
                  />
                ) : (
                  <div className="w-52 h-52 bg-gray-100 flex items-center justify-center text-xs text-gray-400 font-mono">
                    QR Payload Ready
                  </div>
                )}

                {/* Badge Logo FinToko / QRIS di Tengah */}
                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                  <span className="px-2 py-0.5 bg-white shadow-md border border-gray-200 rounded-full text-[9px] font-bold text-[#167A93]">
                    QRIS FinToko
                  </span>
                </div>
              </div>

              {/* Countdown Timer & Polling Status */}
              <div className="flex items-center justify-center gap-3">
                <div
                  className={`px-3 py-1 rounded-full text-xs font-mono font-bold flex items-center gap-1.5 ${
                    secondsRemaining < 120
                      ? 'bg-rose-50 text-rose-600 border border-rose-200'
                      : 'bg-cyan-50 text-[#167A93] border border-cyan-200'
                  }`}
                >
                  <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
                  <span>Sisa Waktu: {formatTimer(secondsRemaining)}</span>
                </div>
              </div>

              {/* Petunjuk Penggunaan Singkat */}
              <p className="text-[11px] text-gray-500 max-w-xs mx-auto">
                Buka aplikasi e-wallet atau mobile banking favorit Anda, lalu arahkan scanner ke kode QR di atas.
              </p>

              {/* Tombol Aksi: Simulasi Demo & Batal */}
              <div className="pt-2 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                <button
                  type="button"
                  onClick={handleCancel}
                  className="w-full sm:w-auto px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 rounded-xl hover:bg-gray-100 transition cursor-pointer"
                >
                  Batal Transaksi
                </button>

                <button
                  type="button"
                  onClick={handleSimulatePayment}
                  disabled={isSimulating}
                  className="w-full sm:w-auto px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer active:scale-95"
                  title="Gunakan untuk pengujian tanpa uang sungguhan"
                >
                  {isSimulating ? (
                    <span>Memverifikasi...</span>
                  ) : (
                    <>
                      <span>⚡</span>
                      <span>Simulasi Bayar Sukses</span>
                    </>
                  )}
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default QrisPaymentModal;
