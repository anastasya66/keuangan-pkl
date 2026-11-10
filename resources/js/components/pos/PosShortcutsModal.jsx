import React from 'react';

export function PosShortcutsModal({ onClose }) {
  const shortcuts = [
    { key: 'F2', desc: 'Fokus ke kotak pencarian produk / scan barcode' },
    { key: 'F4', desc: 'Buka / fokus pilihan pelanggan' },
    { key: 'F8', desc: 'Buka riwayat penjualan & cetak ulang struk' },
    { key: 'F9', desc: 'Pilih & bayar dengan Uang Tunai (Cash)' },
    { key: 'F10', desc: 'Pilih & bayar dengan QRIS Dinamis' },
    { key: 'F11', desc: 'Pilih & bayar dengan Transfer Bank' },
    { key: 'Esc', desc: 'Tutup jendela modal / bersihkan keranjang' },
  ];

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/55 backdrop-blur-xs">
      <div className="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full overflow-hidden animate-in fade-in duration-200">
        <div className="p-4 bg-gradient-to-r from-[#16323B] to-[#1E434F] text-white flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-xl">⌨️</span>
            <div>
              <h3 className="font-bold text-sm">Shortcut Keyboard Kasir POS</h3>
              <p className="text-[11px] text-[#A2CBD4]">Operasional cepat kasir tanpa mouse</p>
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

        <div className="p-5 space-y-2.5">
          {shortcuts.map((item, idx) => (
            <div key={idx} className="flex items-center justify-between p-2 rounded-xl bg-gray-50 border border-gray-100 text-xs">
              <span className="text-gray-700">{item.desc}</span>
              <kbd className="px-2.5 py-1 bg-white border border-gray-300 rounded-lg font-mono font-bold text-gray-900 shadow-2xs">
                {item.key}
              </kbd>
            </div>
          ))}
        </div>

        <div className="p-3 bg-gray-50 border-t border-gray-200 flex justify-end">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200 rounded-xl transition cursor-pointer"
          >
            Mengerti
          </button>
        </div>
      </div>
    </div>
  );
}

export default PosShortcutsModal;
