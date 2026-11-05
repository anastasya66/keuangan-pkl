import React from 'react';
import { formatRupiah } from '../../utils/currency';

const CATEGORY_COLORS = [
  'bg-[#E66D79]', // soft red
  'bg-[#F0B75A]', // soft amber
  'bg-[#38BFE0]', // brand primary blue
  'bg-[#36B878]', // soft green
  'bg-[#79CFE0]', // soft cyan
  'bg-[#8B5CF6]', // purple
  'bg-[#EC4899]', // pink
  'bg-[#64748B]', // slate
];

export default function ExpenseDistributionChart({
  distribution = { total_expense: 0, categories: [] },
}) {
  const { total_expense = 0, categories = [] } = distribution || {};

  if (!categories || categories.length === 0 || total_expense === 0) {
    return (
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 flex flex-col items-center justify-center min-h-[300px] text-[#8EA6AF] shadow-xs">
        <span className="text-4xl mb-2">💸</span>
        <p className="text-sm font-medium">Belum ada data pengeluaran pada periode ini.</p>
      </div>
    );
  }

  return (
    <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h3 className="text-sm font-bold text-[#10262E] flex items-center gap-2">
            <span>🏷️</span> Distribusi Pengeluaran
          </h3>
          <p className="text-xs text-[#577782] mt-0.5">
            Komposisi biaya per kategori transaksi
          </p>
        </div>
        <div className="text-right">
          <span className="text-[11px] text-[#8EA6AF] font-medium">Total Beban</span>
          <div className="text-xs font-mono font-bold text-[#E66D79]">
            {formatRupiah(total_expense)}
          </div>
        </div>
      </div>

      {/* Segmented Progress Bar */}
      <div className="h-3.5 w-full bg-[#F3FBFC] rounded-full overflow-hidden flex mb-5 p-0.5 gap-0.5 border border-[#D7EBEF]">
        {categories.map((cat, idx) => {
          const color = CATEGORY_COLORS[idx % CATEGORY_COLORS.length];
          return (
            <div
              key={cat.category_id || idx}
              style={{ width: `${Math.max(cat.percentage, 2)}%` }}
              className={`h-full ${color} rounded-sm transition-all hover:opacity-80`}
              title={`${cat.category_name}: ${cat.percentage}% (${formatRupiah(cat.amount)})`}
            />
          );
        })}
      </div>

      {/* Categories List */}
      <div className="space-y-2.5 max-h-56 overflow-y-auto pr-1 no-scrollbar">
        {categories.map((cat, idx) => {
          const color = CATEGORY_COLORS[idx % CATEGORY_COLORS.length];
          return (
            <div
              key={cat.category_id || idx}
              className="flex items-center justify-between p-2.5 rounded-xl bg-[#F8FDFF] hover:bg-[#F0FAFC] border border-[#EBF5F8] transition-colors"
            >
              <div className="flex items-center gap-3">
                <span className={`w-3 h-3 rounded-md ${color} shrink-0`}></span>
                <div>
                  <div className="text-xs font-bold text-[#10262E]">
                    {cat.category_name}
                  </div>
                  <div className="text-[11px] text-[#577782]">
                    {cat.transaction_count} transaksi ({cat.percentage}%)
                  </div>
                </div>
              </div>
              <div className="text-xs font-mono font-bold text-[#10262E]">
                {formatRupiah(cat.amount)}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
