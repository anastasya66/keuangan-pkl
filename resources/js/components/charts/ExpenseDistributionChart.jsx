import React from 'react';
import { formatRupiah } from '../../utils/currency';

const CATEGORY_COLORS = [
  'bg-rose-500',
  'bg-amber-500',
  'bg-indigo-500',
  'bg-teal-500',
  'bg-blue-500',
  'bg-purple-500',
  'bg-cyan-500',
  'bg-pink-500',
];

export default function ExpenseDistributionChart({
  distribution = { total_expense: 0, categories: [] },
}) {
  const { total_expense = 0, categories = [] } = distribution || {};

  if (!categories || categories.length === 0 || total_expense === 0) {
    return (
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 flex flex-col items-center justify-center min-h-[300px] text-slate-400">
        <span className="text-4xl mb-2">💸</span>
        <p className="text-sm">Belum ada pengeluaran pada periode ini.</p>
      </div>
    );
  }

  return (
    <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h3 className="text-base font-bold text-white flex items-center gap-2">
            <span>🏷️</span> Distribusi Pengeluaran
          </h3>
          <p className="text-xs text-slate-400 mt-0.5">
            Komposisi biaya berdasarkan kategori transaksi
          </p>
        </div>
        <div className="text-right">
          <span className="text-xs text-slate-400">Total Pengeluaran</span>
          <div className="text-sm font-mono font-bold text-rose-400">
            {formatRupiah(total_expense)}
          </div>
        </div>
      </div>

      {/* Segmented Progress Bar */}
      <div className="h-4 w-full bg-slate-900 rounded-full overflow-hidden flex mb-6 p-0.5 gap-0.5">
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
      <div className="space-y-3 max-h-56 overflow-y-auto pr-1">
        {categories.map((cat, idx) => {
          const color = CATEGORY_COLORS[idx % CATEGORY_COLORS.length];
          return (
            <div
              key={cat.category_id || idx}
              className="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/50 hover:bg-slate-900 transition-colors"
            >
              <div className="flex items-center gap-3">
                <span className={`w-3 h-3 rounded-full ${color} shrink-0`}></span>
                <div>
                  <div className="text-xs font-semibold text-white">
                    {cat.category_name}
                  </div>
                  <div className="text-[11px] text-slate-400">
                    {cat.transaction_count} transaksi ({cat.percentage}%)
                  </div>
                </div>
              </div>
              <div className="text-xs font-mono font-bold text-slate-200">
                {formatRupiah(cat.amount)}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
