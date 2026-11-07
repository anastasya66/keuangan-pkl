import React from 'react';

export function Pagination({ pagination, onPageChange }) {
  if (!pagination || pagination.total === 0) return null;

  const { current_page, last_page, total, per_page } = pagination;
  if (last_page <= 1) {
    return (
      <div className="flex items-center justify-between text-xs text-[#577782] px-4 py-3 bg-[#F3FBFC]/40 border-t border-[#D7EBEF]">
        <span>Menampilkan seluruh <strong>{total}</strong> data</span>
      </div>
    );
  }

  const from = (current_page - 1) * per_page + 1;
  const to = Math.min(current_page * per_page, total);

  return (
    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 bg-[#F3FBFC]/40 border-t border-[#D7EBEF] text-xs text-[#577782]">
      <div>
        Menampilkan <strong>{from}</strong> - <strong>{to}</strong> dari <strong>{total}</strong> data
      </div>

      <div className="flex items-center gap-1.5 self-end sm:self-auto">
        <button
          type="button"
          disabled={current_page <= 1}
          onClick={() => onPageChange(current_page - 1)}
          className="px-3 py-1.5 rounded-lg bg-white border border-[#D7EBEF] text-[#16323B] hover:bg-[#F3FBFC] disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer shadow-2xs font-medium"
        >
          &larr; Sebelumnya
        </button>

        <span className="px-3 py-1.5 rounded-lg bg-[#167A93] text-white font-bold shadow-xs">
          Hal {current_page} / {last_page}
        </span>

        <button
          type="button"
          disabled={current_page >= last_page}
          onClick={() => onPageChange(current_page + 1)}
          className="px-3 py-1.5 rounded-lg bg-white border border-[#D7EBEF] text-[#16323B] hover:bg-[#F3FBFC] disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer shadow-2xs font-medium"
        >
          Selanjutnya &rarr;
        </button>
      </div>
    </div>
  );
}

export default Pagination;
