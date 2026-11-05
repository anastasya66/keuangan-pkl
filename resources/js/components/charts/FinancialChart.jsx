import React, { useState } from 'react';
import { formatRupiah } from '../../utils/currency';

export default function FinancialChart({ points = [], title = 'Tren Keuangan' }) {
  const [hoveredIdx, setHoveredIdx] = useState(null);

  if (!points || points.length === 0) {
    return (
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 flex flex-col items-center justify-center min-h-[300px] text-slate-400">
        <span className="text-4xl mb-2">📊</span>
        <p className="text-sm">Belum ada data transaksi pada periode ini.</p>
      </div>
    );
  }

  // Cari nilai maksimum untuk normalisasi skala Y
  const maxVal = Math.max(
    ...points.map((p) => Math.max(p.income || 0, p.expense || 0)),
    100000
  );

  const chartHeight = 220;
  const paddingBottom = 40;
  const paddingTop = 20;
  const usableHeight = chartHeight - paddingBottom - paddingTop;

  return (
    <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl relative">
      {/* Header & Legend */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
          <h3 className="text-base font-bold text-white flex items-center gap-2">
            <span>📈</span> {title}
          </h3>
          <p className="text-xs text-slate-400 mt-0.5">
            Perbandingan pemasukan, pengeluaran, dan tren pergerakan kas toko
          </p>
        </div>

        <div className="flex items-center gap-4 text-xs">
          <div className="flex items-center gap-1.5">
            <span className="w-3 h-3 rounded-sm bg-emerald-500"></span>
            <span className="text-slate-300">Pemasukan</span>
          </div>
          <div className="flex items-center gap-1.5">
            <span className="w-3 h-3 rounded-sm bg-rose-500"></span>
            <span className="text-slate-300">Pengeluaran</span>
          </div>
        </div>
      </div>

      {/* SVG Chart Area */}
      <div className="relative overflow-x-auto no-scrollbar">
        <div style={{ minWidth: Math.max(points.length * 48, 500) }}>
          <svg
            viewBox={`0 0 ${Math.max(points.length * 60, 600)} ${chartHeight}`}
            className="w-full h-56 overflow-visible"
          >
            {/* Grid horizontal lines */}
            {[0, 0.25, 0.5, 0.75, 1].map((ratio, i) => {
              const y = paddingTop + usableHeight * (1 - ratio);
              return (
                <g key={i}>
                  <line
                    x1="0"
                    y1={y}
                    x2="100%"
                    y2={y}
                    stroke="#334155"
                    strokeDasharray="4 4"
                    strokeWidth="1"
                  />
                  <text
                    x="4"
                    y={y - 4}
                    fill="#64748b"
                    fontSize="10"
                    fontFamily="monospace"
                  >
                    {formatRupiah(maxVal * ratio)}
                  </text>
                </g>
              );
            })}

            {/* Bars */}
            {points.map((pt, idx) => {
              const totalWidth = Math.max(points.length * 60, 600);
              const colWidth = totalWidth / points.length;
              const xCenter = idx * colWidth + colWidth / 2;
              const barWidth = Math.min(colWidth * 0.35, 18);

              const incomeH = (pt.income / maxVal) * usableHeight;
              const expenseH = (pt.expense / maxVal) * usableHeight;

              const incomeY = paddingTop + (usableHeight - incomeH);
              const expenseY = paddingTop + (usableHeight - expenseH);

              const isHovered = hoveredIdx === idx;

              return (
                <g
                  key={idx}
                  className="cursor-pointer transition-opacity"
                  onMouseEnter={() => setHoveredIdx(idx)}
                  onMouseLeave={() => setHoveredIdx(null)}
                >
                  {/* Hover Column Background */}
                  {isHovered && (
                    <rect
                      x={idx * colWidth + 4}
                      y={paddingTop}
                      width={colWidth - 8}
                      height={usableHeight}
                      fill="#1e293b"
                      opacity="0.6"
                      rx="6"
                    />
                  )}

                  {/* Income Bar (Emerald) */}
                  <rect
                    x={xCenter - barWidth - 2}
                    y={incomeY}
                    width={barWidth}
                    height={incomeH}
                    fill="#10b981"
                    rx="3"
                    className="transition-all duration-300"
                  />

                  {/* Expense Bar (Rose) */}
                  <rect
                    x={xCenter + 2}
                    y={expenseY}
                    width={barWidth}
                    height={expenseH}
                    fill="#f43f5e"
                    rx="3"
                    className="transition-all duration-300"
                  />

                  {/* X-Axis Label */}
                  <text
                    x={xCenter}
                    y={chartHeight - 12}
                    fill={isHovered ? '#f8fafc' : '#94a3b8'}
                    fontSize="11"
                    fontWeight={isHovered ? 'bold' : 'normal'}
                    textAnchor="middle"
                  >
                    {pt.label}
                  </text>
                </g>
              );
            })}
          </svg>
        </div>

        {/* Hover Tooltip Overlay */}
        {hoveredIdx !== null && points[hoveredIdx] && (
          <div
            className="absolute top-2 right-4 bg-slate-900/95 border border-slate-700 p-3 rounded-xl shadow-2xl text-xs z-10 pointer-events-none backdrop-blur animate-in fade-in duration-150"
          >
            <div className="font-bold text-white border-b border-slate-800 pb-1 mb-2">
              📅 {points[hoveredIdx].label} ({points[hoveredIdx].date})
            </div>
            <div className="space-y-1">
              <div className="flex items-center justify-between gap-4">
                <span className="text-emerald-400 flex items-center gap-1.5">
                  <span className="w-2 h-2 rounded-full bg-emerald-400"></span> Pemasukan:
                </span>
                <span className="font-mono font-semibold text-white">
                  {formatRupiah(points[hoveredIdx].income)}
                </span>
              </div>
              <div className="flex items-center justify-between gap-4">
                <span className="text-rose-400 flex items-center gap-1.5">
                  <span className="w-2 h-2 rounded-full bg-rose-400"></span> Pengeluaran:
                </span>
                <span className="font-mono font-semibold text-white">
                  {formatRupiah(points[hoveredIdx].expense)}
                </span>
              </div>
              <div className="flex items-center justify-between gap-4 pt-1 border-t border-slate-800/80 font-bold">
                <span className="text-slate-300">Laba Bersih:</span>
                <span
                  className={`font-mono ${
                    points[hoveredIdx].profit >= 0 ? 'text-teal-400' : 'text-rose-400'
                  }`}
                >
                  {formatRupiah(points[hoveredIdx].profit)}
                </span>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
