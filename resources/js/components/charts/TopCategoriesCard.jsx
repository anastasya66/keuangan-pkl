import React, { useState } from 'react';
import { ChevronDown } from 'lucide-react';

export function TopCategoriesCard({
  distribution,
  inventorySummary,
  totalCategories = 0,
  totalProducts = 0,
}) {
  const [period, setPeriod] = useState('weekly');

  const categories = distribution?.categories || [];
  const topCategories = categories.length > 0
    ? categories.slice(0, 3)
    : [
        { category_name: 'Makanan & Sembako', transaction_count: 698, percentage: 45, color: '#55BFD8' },
        { category_name: 'Minuman Ringan', transaction_count: 545, percentage: 32, color: '#E9B45D' },
        { category_name: 'Kebutuhan Harian', transaction_count: 455, percentage: 23, color: '#48B58B' },
      ];

  const colors = ['#55BFD8', '#E9B45D', '#48B58B', '#79D5E8', '#E47777'];

  const catCount = totalCategories || categories.length || 12;
  const prodCount = totalProducts || inventorySummary?.total_products || 148;

  // Calculate score or total transactions
  const totalScore = topCategories.reduce((acc, c) => acc + (c.transaction_count || 100), 0);

  return (
    <div className="bg-white rounded-2xl p-6 border border-[#E7ECEE] shadow-[0_2px_10px_rgba(0,0,0,0.02)] hover:shadow-md hover:border-[#D5E3E7] transition-all flex flex-col justify-between">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-base font-bold text-[#20282D]">Top Categories</h3>
        <div className="relative">
          <select
            value={period}
            onChange={(e) => setPeriod(e.target.value)}
            className="appearance-none bg-[#F1F4F5] hover:bg-[#E7ECEE] text-[#20282D] text-xs font-semibold px-3 py-1.5 pr-7 rounded-full border-none cursor-pointer focus:outline-none transition-colors"
          >
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="all">All Time</option>
          </select>
          <ChevronDown className="w-3.5 h-3.5 text-[#879197] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      </div>

      {/* Body: Donut / Concentric Chart + Category items */}
      <div className="grid grid-cols-1 sm:grid-cols-2 items-center gap-4 py-2">
        {/* Radial Concentric SVG */}
        <div className="relative flex items-center justify-center">
          <svg viewBox="0 0 160 160" className="w-36 h-36 transform -rotate-90">
            {/* Outer track: Green */}
            <circle
              cx="80"
              cy="80"
              r="68"
              fill="transparent"
              stroke="#E7ECEE"
              strokeWidth="7"
              strokeDasharray="4 3"
            />
            <circle
              cx="80"
              cy="80"
              r="68"
              fill="transparent"
              stroke="#48B58B"
              strokeWidth="7"
              strokeDasharray="180 300"
              strokeLinecap="round"
            />

            {/* Middle track: Coral / Orange */}
            <circle
              cx="80"
              cy="80"
              r="54"
              fill="transparent"
              stroke="#E7ECEE"
              strokeWidth="7"
              strokeDasharray="4 3"
            />
            <circle
              cx="80"
              cy="80"
              r="54"
              fill="transparent"
              stroke="#E9B45D"
              strokeWidth="7"
              strokeDasharray="140 300"
              strokeLinecap="round"
            />

            {/* Inner track: Cyan */}
            <circle
              cx="80"
              cy="80"
              r="40"
              fill="transparent"
              stroke="#E7ECEE"
              strokeWidth="7"
              strokeDasharray="4 3"
            />
            <circle
              cx="80"
              cy="80"
              r="40"
              fill="transparent"
              stroke="#55BFD8"
              strokeWidth="7"
              strokeDasharray="120 300"
              strokeLinecap="round"
            />
          </svg>

          {/* Center text */}
          <div className="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
            <span className="text-xl font-black text-[#20282D] leading-none">
              {totalScore > 999 ? '1000+' : totalScore}
            </span>
            <span className="text-[10px] font-semibold text-[#879197] uppercase tracking-wider mt-0.5">
              Top Score
            </span>
          </div>
        </div>

        {/* Categories List */}
        <div className="space-y-3">
          {topCategories.map((cat, i) => {
            const color = colors[i % colors.length];
            return (
              <div key={cat.category_id || i} className="space-y-1">
                <div className="flex items-center justify-between text-xs">
                  <span className="font-semibold text-[#20282D] truncate max-w-[120px]">
                    {cat.category_name}
                  </span>
                  <span className="font-bold text-[#20282D]">
                    {cat.transaction_count || Math.round(cat.percentage * 10)} Sales
                  </span>
                </div>
                {/* Horizontal patterned progress bar */}
                <div className="h-2 w-full bg-[#F1F4F5] rounded-full overflow-hidden">
                  <div
                    style={{
                      width: `${Math.min(cat.percentage || 40, 100)}%`,
                      backgroundColor: color,
                    }}
                    className="h-full rounded-full transition-all duration-500"
                  />
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Footer Metrics */}
      <div className="mt-4 pt-3.5 border-t border-[#F1F4F5] space-y-2">
        <div className="flex items-center justify-between text-xs">
          <div className="flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-[#20282D]" />
            <span className="text-[#879197] font-medium">Total Number of Categories</span>
          </div>
          <span className="font-black text-[#20282D]">{catCount}</span>
        </div>

        <div className="flex items-center justify-between text-xs">
          <div className="flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-[#E9B45D]" />
            <span className="text-[#879197] font-medium">Total Number of Products</span>
          </div>
          <span className="font-black text-[#20282D]">{prodCount}</span>
        </div>
      </div>
    </div>
  );
}

export default TopCategoriesCard;

