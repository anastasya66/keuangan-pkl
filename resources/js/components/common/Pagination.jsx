export function Pagination({ pagination, onPageChange }) {
  if (!pagination || pagination.total === 0) return null;

  const { current_page, last_page, total, per_page } = pagination;
  if (last_page <= 1) {
    return (
      <div className="flex items-center justify-between text-xs text-slate-400 px-4 py-3">
        <span>Menampilkan seluruh <strong>{total}</strong> data</span>
      </div>
    );
  }

  const from = (current_page - 1) * per_page + 1;
  const to = Math.min(current_page * per_page, total);

  return (
    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-slate-800 text-xs text-slate-400">
      <div>
        Menampilkan <strong>{from}</strong> - <strong>{to}</strong> dari <strong>{total}</strong> data
      </div>

      <div className="flex items-center gap-1.5 self-end sm:self-auto">
        <button
          type="button"
          disabled={current_page <= 1}
          onClick={() => onPageChange(current_page - 1)}
          className="px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
        >
          &larr; Sebelumnya
        </button>

        <span className="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-200 font-semibold">
          Hal {current_page} / {last_page}
        </span>

        <button
          type="button"
          disabled={current_page >= last_page}
          onClick={() => onPageChange(current_page + 1)}
          className="px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
        >
          Selanjutnya &rarr;
        </button>
      </div>
    </div>
  );
}

export default Pagination;

