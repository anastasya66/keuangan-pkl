import React, { useState } from 'react';
import { MoreVertical, ChevronDown } from 'lucide-react';
import { formatRupiah } from '../../utils/currency';

export function SalesPurchaseChart({
  points = [],
  period = 'this_year',
  onPeriodChange,
}) {
  const [hoveredIdx, setHoveredIdx] = useState(null);

  // Normalize points or fallback to default distribution if empty
  const defaultPoints = [
    { label: 'Jan', income: 45000000, expense: 32000000 },
    { label: 'Feb', income: 38000000, expense: 28000000 },
    { label: 'Mar', income: 52000000, expense: 41000000 },
    { label: 'Apr', income: 61000000, expense: 48000000 },
    { label: 'May', income: 55000000, expense: 39000000 },
    { label: 'Jun', income: 72000000, expense: 50000000 },
    { label: 'Jul', income: 68000000, expense: 45000000 },
    { label: 'Aug', income: 80000000, expense: 58000000 },
    { label: 'Sep', income: 74000000, expense: 52000000 },
    { label: 'Oct', income: 89000000, expense: 62000000 },
    { label: 'Nov', income: 95000000, expense: 67000000 },
    { label: 'Dec', income: 105000000, expense: 71000000 },
  ];

  const data = points && points.length > 0 ? points : defaultPoints;

  const maxVal = Math.max(
    ...data.map((d) => Math.max(Number(d.income) || 0, Number(d.expense) || 0)),
    100000
  );

  // Helper for compact currency formatting like 100K, 20M, etc.
  const formatCompact = (val) => {
    if (val >= 1000000000) return `${(val / 1000000000).toFixed(1)}M`;
    if (val >= 1000000) return `${(val / 1000000).toFixed(0)}M`;
    if (val >= 1000) return `${(val / 1000).toFixed(0)}K`;
    return `${val}`;
  };

  const chartHeight = 220;
  const paddingBottom = 28;
  const paddingTop = 20;
  const usableHeight = chartHeight - paddingBottom - paddingTop;
  const gutterLeft = 55;
  const gutterRight = 20;
  const totalSvgWidth = Math.max(gutterLeft + data.length * 52 + gutterRight, 580);
  const chartDrawableWidth = totalSvgWidth - gutterLeft - gutterRight;
  const colWidth = chartDrawableWidth / data.length;

  return (
    <div className="bg-white rounded-2xl p-6 border border-[#E7ECEE] shadow-[0_2px_10px_rgba(0,0,0,0.02)] hover:shadow-md hover:border-[#D5E3E7] transition-all flex flex-col justify-between">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
        <div>
          <h3 className="text-base font-bold text-[#20282D]">Sales &amp; Purchase</h3>
          <p className="text-xs text-[#879197] mt-0.5">Analisis tren penjualan dan pembelian toko</p>
        </div>

        <div className="flex items-center flex-wrap gap-4">
          {/* Legend */}
          <div className="flex items-center gap-3 text-xs font-medium text-[#879197]">
            <div className="flex items-center gap-1.5">
              <span className="w-3 h-3 rounded-[3px] bg-[#B6ECF6] border border-[#79D5E8]" />
              <span>Total Purchase</span>
            </div>
            <div className="flex items-center gap-1.5">
              <span className="w-3 h-3 rounded-[3px] bg-[#55BFD8]" />
              <span className="text-[#20282D] font-semibold">Total Sales</span>
            </div>
          </div>

          {/* Period selector dropdown pill */}
          <div className="flex items-center gap-2">
            <div className="relative">
              <select
                value={period}
                onChange={(e) => onPeriodChange?.(e.target.value)}
                className="appearance-none bg-[#F1F4F5] hover:bg-[#E7ECEE] text-[#20282D] text-xs font-semibold px-3 py-1.5 pr-7 rounded-full border-none cursor-pointer focus:outline-none transition-colors"
              >
                <option value="this_month">This Month</option>
                <option value="this_year">This Year</option>
                <option value="this_week">This Week</option>
                <option value="today">Today</option>
              </select>
              <ChevronDown className="w-3.5 h-3.5 text-[#879197] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>

            <button
              type="button"
              className="p-1 rounded-lg text-[#A6AFB3] hover:text-[#20282D] hover:bg-[#F8FAFA] transition-colors"
            >
              <MoreVertical className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      {/* Dual Bar Chart (SVG) */}
      <div className="relative overflow-x-auto no-scrollbar">
        <div style={{ minWidth: totalSvgWidth }}>
          <svg viewBox={`0 0 ${totalSvgWidth} ${chartHeight}`} className="w-full h-56 overflow-visible">
            <defs>
              {/* Pattern for Purchase Bars (like the reference image striped/tint) */}
              <pattern id="purchaseStripes" width="4" height="4" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                <line x1="0" y1="0" x2="0" y2="4" stroke="#90E0F0" strokeWidth="2" />
                <line x1="2" y1="0" x2="2" y2="4" stroke="#D1F5FD" strokeWidth="2" />
              </pattern>
            </defs>

            {/* Horizontal Grid Lines */}
            {[0, 0.25, 0.5, 0.75, 1].map((ratio, i) => {
              const y = paddingTop + usableHeight * (1 - ratio);
              return (
                <g key={i}>
                  <line
                    x1={gutterLeft}
                    y1={y}
                    x2={totalSvgWidth - gutterRight}
                    y2={y}
                    stroke="#F1F4F5"
                    strokeWidth="1.2"
                  />
                  <text
                    x={gutterLeft - 8}
                    y={y + 3.5}
                    fill="#879197"
                    fontSize="10"
                    fontWeight="500"
                    fontFamily="inherit"
                    textAnchor="end"
                  >
                    {formatCompact(maxVal * ratio)}
                  </text>
                </g>
              );
            })}

            {/* Dual Bars */}
            {data.map((d, idx) => {
              const xCol = gutterLeft + idx * colWidth;
              const xCenter = xCol + colWidth / 2;

              const barW = Math.min(colWidth * 0.32, 14);
              const spacing = 3;

              // Purchase Bar (left bar)
              const expVal = Number(d.expense) || 0;
              const expHeight = maxVal > 0 ? (expVal / maxVal) * usableHeight : 0;
              const expY = paddingTop + usableHeight - expHeight;
              const expX = xCenter - barW - spacing / 2;

              // Sales Bar (right bar)
              const incVal = Number(d.income) || 0;
              const incHeight = maxVal > 0 ? (incVal / maxVal) * usableHeight : 0;
              const incY = paddingTop + usableHeight - incHeight;
              const incX = xCenter + spacing / 2;

              const isHovered = hoveredIdx === idx;

              return (
                <g
                  key={idx}
                  onMouseEnter={() => setHoveredIdx(idx)}
                  onMouseLeave={() => setHoveredIdx(null)}
                  className="cursor-pointer transition-all"
                >
                  {/* Subtle column hover background */}
                  {isHovered && (
                    <rect
                      x={xCol + 4}
                      y={paddingTop}
                      width={colWidth - 8}
                      height={usableHeight}
                      fill="#F1F4F5"
                      opacity="0.6"
                      rx="6"
                    />
                  )}

                  {/* Purchase Bar (Patterned / Light Cyan) */}
                  <rect
                    x={expX}
                    y={expY}
                    width={barW}
                    height={Math.max(expHeight, 2)}
                    rx="3"
                    fill="url(#purchaseStripes)"
                    stroke="#79D5E8"
                    strokeWidth="1"
                    className="transition-all duration-300"
                  />

                  {/* Sales Bar (Solid Cyan) */}
                  <rect
                    x={incX}
                    y={incY}
                    width={barW}
                    height={Math.max(incHeight, 2)}
                    rx="3"
                    fill="#55BFD8"
                    className="transition-all duration-300 hover:fill-[#37AFCB]"
                  />

                  {/* X Axis Label */}
                  <text
                    x={xCenter}
                    y={chartHeight - 6}
                    fill={isHovered ? '#20282D' : '#879197'}
                    fontSize="11"
                    fontWeight={isHovered ? '700' : '500'}
                    textAnchor="middle"
                  >
                    {d.label}
                  </text>
                </g>
              );
            })}
          </svg>
        </div>
      </div>

      {/* Tooltip on Hover */}
      {hoveredIdx !== null && data[hoveredIdx] && (
        <div className="mt-3 pt-3 border-t border-[#F1F4F5] flex items-center justify-between text-xs animate-fade-in">
          <span className="font-bold text-[#20282D]">
            Periode: {data[hoveredIdx].label}
          </span>
          <div className="flex items-center gap-4">
            <span className="text-[#37AFCB] font-bold">
              Sales: {formatRupiah(data[hoveredIdx].income || 0)}
            </span>
            <span className="text-[#879197] font-medium">
              Purchase: {formatRupiah(data[hoveredIdx].expense || 0)}
            </span>
          </div>
        </div>
      )}
    </div>
  );
}

export default SalesPurchaseChart;

