import React, { useState } from 'react';
import { formatRupiah } from '../../utils/currency';

export default function FinancialChart({ points = [], title = 'Tren Keuangan & Arus Kas' }) {
  const [hoveredIdx, setHoveredIdx] = useState(null);

  if (!points || points.length === 0) {
    return (
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 flex flex-col items-center justify-center min-h-[300px] text-[#8EA6AF] shadow-xs">
        <span className="text-4xl mb-2">📊</span>
        <p className="text-sm font-medium">Belum ada data transaksi pada periode ini.</p>
      </div>
    );
  }

  const maxVal = Math.max(
    ...points.map((p) => Math.max(p.income || 0, p.expense || 0)),
    100000
  );

  const chartHeight = 220;
  const paddingBottom = 35;
  const paddingTop = 20;
  const usableHeight = chartHeight - paddingBottom - paddingTop;

  // Gutter & layout calculations to prevent Y-axis text collision and right-edge clipping
  const gutterLeft = 90; // Dedicated margin for Y-axis currency labels (e.g., "Rp 1.250.000")
  const gutterRight = 30; // Dedicated right margin so the last date label (e.g., "15 Nov") is never truncated
  const minChartWidth = 650;

  const contentWidthNeeded = points.length * 56;
  const totalSvgWidth = Math.max(gutterLeft + contentWidthNeeded + gutterRight, minChartWidth);
  const chartDrawableWidth = totalSvgWidth - gutterLeft - gutterRight;
  const colWidth = chartDrawableWidth / points.length;

  return (
    <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs relative">
      {/* Header & Legend */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
          <h3 className="text-sm font-bold text-[#10262E] flex items-center gap-2">
            <span>📈</span> {title}
          </h3>
          <p className="text-xs text-[#577782] mt-0.5">
            Analisis tren pemasukan dan pengeluaran bulanan.
          </p>
        </div>

        <div className="flex items-center gap-4 text-xs font-semibold">
          <div className="flex items-center gap-1.5">
            <span className="w-3 h-3 rounded-md bg-[#36B878]"></span>
            <span className="text-[#577782]">Pemasukan</span>
          </div>
          <div className="flex items-center gap-1.5">
            <span className="w-3 h-3 rounded-md bg-[#E66D79]"></span>
            <span className="text-[#577782]">Pengeluaran</span>
          </div>
        </div>
      </div>

      {/* SVG Chart Area */}
      <div className="relative overflow-x-auto no-scrollbar">
        <div style={{ minWidth: totalSvgWidth }}>
          <svg
            viewBox={`0 0 ${totalSvgWidth} ${chartHeight}`}
            className="w-full h-56 overflow-visible"
          >
            {/* Horizontal Grid lines & Y-Axis Currency Labels */}
            {[0, 0.25, 0.5, 0.75, 1].map((ratio, i) => {
              const y = paddingTop + usableHeight * (1 - ratio);
              return (
                <g key={i}>
                  <line
                    x1={gutterLeft}
                    y1={y}
                    x2={totalSvgWidth - gutterRight}
                    y2={y}
                    stroke="#EBF5F8"
                    strokeDasharray="4 4"
                    strokeWidth="1.2"
                  />
                  <text
                    x={gutterLeft - 10}
                    y={y + 3}
                    fill="#8EA6AF"
                    fontSize="10"
                    fontWeight="500"
                    fontFamily="monospace"
                    textAnchor="end"
                  >
                    {formatRupiah(maxVal * ratio)}
                  </text>
                </g>
              );
            })}

            {/* Bars */}
            {points.map((pt, idx) => {
              const xColStart = gutterLeft + idx * colWidth;
              const xCenter = xColStart + colWidth / 2;
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
                  {/* Column Hover Background */}
                  {isHovered && (
                    <rect
                      x={xColStart + 2}
                      y={paddingTop}
                      width={colWidth - 4}
                      height={usableHeight}
                      fill="#EAF8FA"
                      opacity="0.8"
                      rx="8"
                    />
                  )}

                  {/* Income Bar (Green Accent #36B878) */}
                  <rect
                    x={xCenter - barWidth - 2}
                    y={incomeY}
                    width={barWidth}
                    height={incomeH}
                    fill="#36B878"
                    rx="4"
                    className="transition-all duration-300"
                  />

                  {/* Expense Bar (Red Accent #E66D79) */}
                  <rect
                    x={xCenter + 2}
                    y={expenseY}
                    width={barWidth}
                    height={expenseH}
                    fill="#E66D79"
                    rx="4"
                    className="transition-all duration-300"
                  />

                  {/* X-Axis Date Label */}
                  <text
                    x={xCenter}
                    y={chartHeight - 10}
                    fill={isHovered ? '#10262E' : '#8EA6AF'}
                    fontSize="11"
                    fontWeight={isHovered ? '700' : '500'}
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
          <div className="absolute top-2 right-4 bg-white border border-[#D7EBEF] p-3 rounded-2xl shadow-xl text-xs z-10 pointer-events-none animate-in fade-in zoom-in-95 duration-150">
            <div className="font-bold text-[#10262E] border-b border-[#EBF5F8] pb-1.5 mb-2">
              📅 {points[hoveredIdx].label} ({points[hoveredIdx].date})
            </div>
            <div className="space-y-1.5">
              <div className="flex items-center justify-between gap-4">
                <span className="text-[#36B878] font-semibold flex items-center gap-1.5">
                  <span className="w-2 h-2 rounded-full bg-[#36B878]"></span> Pemasukan:
                </span>
                <span className="font-mono font-bold text-[#10262E]">
                  {formatRupiah(points[hoveredIdx].income)}
                </span>
              </div>
              <div className="flex items-center justify-between gap-4">
                <span className="text-[#E66D79] font-semibold flex items-center gap-1.5">
                  <span className="w-2 h-2 rounded-full bg-[#E66D79]"></span> Pengeluaran:
                </span>
                <span className="font-mono font-bold text-[#10262E]">
                  {formatRupiah(points[hoveredIdx].expense)}
                </span>
              </div>
              <div className="flex items-center justify-between gap-4 pt-1.5 border-t border-[#EBF5F8] font-bold">
                <span className="text-[#577782]">Arus Kas Bersih:</span>
                <span
                  className={`font-mono ${
                    points[hoveredIdx].profit >= 0 ? 'text-[#167A93]' : 'text-[#E66D79]'
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
