import React from 'react';

export function ConfirmDialog({
  isOpen,
  title = 'Konfirmasi Tindakan',
  message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
  confirmText = 'Konfirmasi',
  cancelText = 'Batal',
  confirmColor = 'danger', // 'danger' | 'primary'
  isLoading = false,
  onConfirm,
  onCancel,
}) {
  if (!isOpen) return null;

  const confirmBtnStyles =
    confirmColor === 'danger'
      ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-md shadow-rose-500/20'
      : 'bg-gradient-to-r from-[#27C5E5] to-[#38BFE0] hover:from-[#1FB2D0] hover:to-[#2FB0CF] text-white shadow-md shadow-[#38BFE0]/25';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/35 backdrop-blur-xs animate-backdrop">
      <div className="bg-white border border-[#D7EBEF] rounded-3xl shadow-2xl max-w-md w-full p-6 text-[#16323B] animate-modal-pop">
        <h3 className="text-base sm:text-lg font-bold tracking-tight text-[#10262E] flex items-center gap-2">
          {confirmColor === 'danger' ? '⚠️' : 'ℹ️'} {title}
        </h3>

        <p className="mt-2.5 text-xs sm:text-sm text-[#577782] leading-relaxed">
          {message}
        </p>

        <div className="mt-6 flex items-center justify-end gap-3 pt-3 border-t border-[#D7EBEF]">
          <button
            type="button"
            disabled={isLoading}
            onClick={onCancel}
            className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs sm:text-sm font-semibold text-[#577782] transition-colors cursor-pointer disabled:opacity-50 btn-press"
          >
            {cancelText}
          </button>

          <button
            type="button"
            disabled={isLoading}
            onClick={onConfirm}
            className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer disabled:opacity-50 flex items-center gap-2 btn-press ${confirmBtnStyles}`}
          >
            {isLoading && (
              <span className="w-3.5 h-3.5 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
            )}
            {confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}

export default ConfirmDialog;
