import React from 'react';

export function Pagination({ pagination, onPageChange, currentPage, lastPage, total: propTotal, perPage }) {
  const current_page = pagination?.current_page ?? currentPage ?? 1;
  const last_page = pagination?.last_page ?? lastPage ?? 1;
  const total = pagination?.total ?? propTotal ?? 0;
  const per_page = pagination?.per_page ?? perPage ?? 15;

  if (!total || total === 0) return null;
  if (last_page <= 1) {
    return (
      <div className="flex items-center justify-between text-xs text-[#879197] px-4 py-3 bg-[#F8FAFA] border-t border-[#E7ECEE] rounded-b-2xl">
        <span>Menampilkan seluruh <strong>{total}</strong> data</span>
      </div>
    );
  }

  const from = (current_page - 1) * per_page + 1;
  const to = Math.min(current_page * per_page, total);

  return (
    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 bg-[#F8FAFA] border-t border-[#E7ECEE] text-xs text-[#879197] rounded-b-2xl">
      <div>
        Menampilkan <strong>{from}</strong> - <strong>{to}</strong> dari <strong>{total}</strong> data
      </div>

      <div className="flex items-center gap-1.5 self-end sm:self-auto">
        <button
          type="button"
          disabled={current_page <= 1}
          onClick={() => onPageChange(current_page - 1)}
          className="px-3 py-1.5 rounded-full bg-white border border-[#E7ECEE] text-[#20282D] hover:bg-[#F1F4F5] disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer font-medium text-xs shadow-xs"
        >
          &larr; Sebelumnya
        </button>

        <span className="px-3 py-1.5 rounded-full bg-[#20282D] text-white font-bold text-xs shadow-xs">
          Hal {current_page} / {last_page}
        </span>

        <button
          type="button"
          disabled={current_page >= last_page}
          onClick={() => onPageChange(current_page + 1)}
          className="px-3 py-1.5 rounded-full bg-white border border-[#E7ECEE] text-[#20282D] hover:bg-[#F1F4F5] disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer font-medium text-xs shadow-xs"
        >
          Selanjutnya &rarr;
        </button>
      </div>
    </div>
  );
}

export default Pagination;
