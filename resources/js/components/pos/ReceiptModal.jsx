import React, { useState } from 'react';
import { formatRupiah } from '../../utils/currency';

export function ReceiptModal({ receipt, onClose, onNewTransaction }) {
  const [paperWidth, setPaperWidth] = useState('58mm'); // '58mm' or '80mm'

  if (!receipt) return null;

  const handlePrint = () => {
    window.print();
  };

  const is80 = paperWidth === '80mm';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs overflow-y-auto print:p-0 print:bg-transparent">
      {/* Container Dialog (Sembunyikan backdrop saat print) */}
      <div className="bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-lg w-full overflow-hidden flex flex-col max-h-[92vh] print:max-h-none print:shadow-none print:border-none print:w-auto">
        {/* Header Modal (Diabaikan saat Print) */}
        <div className="p-4 bg-gradient-to-r from-[#16323B] to-[#1E434F] text-white flex items-center justify-between print:hidden">
          <div className="flex items-center gap-2">
            <span className="text-xl">🧾</span>
            <div>
              <h3 className="font-bold text-sm">Struk Pembayaran Kasir</h3>
              <p className="text-[11px] text-[#A2CBD4]">No. {receipt.transaction_number}</p>
            </div>
          </div>

          {/* Lebar Thermal Toggle */}
          <div className="flex items-center gap-1 bg-black/25 p-0.5 rounded-lg border border-white/10">
            <button
              type="button"
              onClick={() => setPaperWidth('58mm')}
              className={`px-2 py-0.5 text-[10px] font-bold rounded cursor-pointer transition ${
                paperWidth === '58mm' ? 'bg-[#55BFD8] text-[#16323B]' : 'text-white/80 hover:text-white'
              }`}
            >
              58mm
            </button>
            <button
              type="button"
              onClick={() => setPaperWidth('80mm')}
              className={`px-2 py-0.5 text-[10px] font-bold rounded cursor-pointer transition ${
                paperWidth === '80mm' ? 'bg-[#55BFD8] text-[#16323B]' : 'text-white/80 hover:text-white'
              }`}
            >
              80mm
            </button>
          </div>
        </div>

        {/* Scrollable Receipt Body (Tampilan Kertas Thermal) */}
        <div className="flex-1 overflow-y-auto p-4 bg-[#F1F4F5] flex justify-center print:p-0 print:bg-white print:overflow-visible">
          {/* Card Kertas Struk Thermal */}
          <div
            id="printable-receipt"
            style={{ width: is80 ? '80mm' : '58mm' }}
            className="bg-white shadow-md p-3 sm:p-4 text-[#111827] font-mono text-[11px] leading-tight border border-gray-200 rounded-sm print:shadow-none print:border-none print:p-0 print:w-full"
          >
            {/* Header Toko */}
            <div className="text-center pb-2 border-b border-dashed border-gray-400">
              {receipt.store_logo ? (
                <img
                  src={receipt.store_logo.startsWith('http') || receipt.store_logo.startsWith('/') ? receipt.store_logo : `/storage/${receipt.store_logo}`}
                  alt="Logo"
                  className="h-10 mx-auto mb-1.5 object-contain"
                />
              ) : null}
              <div className="font-bold text-sm uppercase tracking-wide text-black">
                {receipt.store_name || 'FinToko Retail'}
              </div>
              {receipt.store_address && (
                <div className="text-[10px] text-gray-700 mt-0.5">
                  {receipt.store_address}
                </div>
              )}
              {receipt.store_phone && (
                <div className="text-[10px] text-gray-700">
                  Telp: {receipt.store_phone}
                </div>
              )}
              {receipt.receipt_header && (
                <div className="text-[9px] text-gray-600 mt-1 whitespace-pre-line italic">
                  {receipt.receipt_header}
                </div>
              )}
            </div>

            {/* Info Transaksi */}
            <div className="py-2 text-[10px] space-y-0.5 border-b border-dashed border-gray-400">
              <div className="flex justify-between">
                <span>No:</span>
                <span className="font-bold text-black">{receipt.transaction_number}</span>
              </div>
              <div className="flex justify-between">
                <span>Waktu:</span>
                <span>{receipt.sale_date} {receipt.sale_time}</span>
              </div>
              <div className="flex justify-between">
                <span>Kasir:</span>
                <span>{receipt.cashier_name || 'Kasir'}</span>
              </div>
              <div className="flex justify-between">
                <span>Pelanggan:</span>
                <span>{receipt.customer_name || 'Pelanggan Umum'}</span>
              </div>
            </div>

            {/* Tabel Item Pembelian */}
            <div className="py-2 border-b border-dashed border-gray-400">
              <div className="flex justify-between font-bold pb-1 text-[10px] border-b border-gray-300 mb-1">
                <span>ITEM</span>
                <span>TOTAL</span>
              </div>

              <div className="space-y-1.5">
                {(receipt.items || []).map((item, idx) => (
                  <div key={idx} className="text-[10px]">
                    <div className="font-semibold truncate text-black">{item.name}</div>
                    <div className="flex justify-between text-gray-700 pl-1 text-[9px]">
                      <span>
                        {item.quantity} {item.unit || 'pcs'} x {formatRupiah(item.unit_price)}
                        {Number(item.discount_amount) > 0 ? ` (disc -${formatRupiah(item.discount_amount)})` : ''}
                      </span>
                      <span className="font-bold text-black">{formatRupiah(item.subtotal)}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Rincian Finansial Struk */}
            <div className="py-2 text-[10px] space-y-1 border-b border-dashed border-gray-400">
              <div className="flex justify-between">
                <span>Subtotal:</span>
                <span>{formatRupiah(receipt.subtotal)}</span>
              </div>

              {Number(receipt.discount_amount) > 0 && (
                <div className="flex justify-between text-gray-700">
                  <span>Diskon:</span>
                  <span>-{formatRupiah(receipt.discount_amount)}</span>
                </div>
              )}

              {Number(receipt.tax_amount) > 0 && (
                <div className="flex justify-between text-gray-700">
                  <span>PPN / Pajak:</span>
                  <span>+{formatRupiah(receipt.tax_amount)}</span>
                </div>
              )}

              <div className="flex justify-between font-extrabold text-[12px] pt-1 border-t border-gray-300 text-black">
                <span>TOTAL:</span>
                <span>{formatRupiah(receipt.grand_total)}</span>
              </div>

              <div className="flex justify-between pt-1">
                <span>Metode:</span>
                <span className="font-semibold uppercase">{receipt.payment_method || 'TUNAI'}</span>
              </div>

              <div className="flex justify-between">
                <span>Bayar:</span>
                <span>{formatRupiah(receipt.paid_amount)}</span>
              </div>

              <div className="flex justify-between font-bold text-black">
                <span>Kembali:</span>
                <span>{formatRupiah(receipt.change_amount)}</span>
              </div>
            </div>

            {/* Footer Ucapan Terima Kasih & Barcode Simulasi */}
            <div className="text-center pt-3 text-[10px] space-y-1">
              <div className="italic text-gray-700 whitespace-pre-line">
                {receipt.footer_message || 'Terima kasih telah berbelanja di toko kami!'}
              </div>

              {/* Barcode visual monospaced */}
              <div className="pt-1.5 flex flex-col items-center">
                <div className="text-[14px] tracking-widest font-mono select-none opacity-80">
                  ||| | |||| | |||||| || | ||| ||
                </div>
                <div className="text-[8px] text-gray-500 font-mono tracking-wider">
                  {receipt.transaction_number}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Action Buttons Footer (Diabaikan saat Print) */}
        <div className="p-3 bg-white border-t border-gray-200 flex flex-wrap items-center justify-between gap-2 print:hidden">
          <button
            type="button"
            onClick={onClose}
            className="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition cursor-pointer"
          >
            Tutup
          </button>

          <div className="flex items-center gap-2">
            {onNewTransaction && (
              <button
                type="button"
                onClick={() => {
                  onClose();
                  onNewTransaction();
                }}
                className="px-3.5 py-2 text-xs font-bold text-[#167A93] bg-[#EAF8FA] hover:bg-[#D8F2F6] rounded-xl transition cursor-pointer"
              >
                + Transaksi Baru
              </button>
            )}

            <button
              type="button"
              onClick={handlePrint}
              className="px-4 py-2 text-xs font-bold text-white bg-[#167A93] hover:bg-[#116276] rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer active:scale-95"
            >
              <span>🖨️</span>
              <span>Cetak Struk ({paperWidth})</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default ReceiptModal;
