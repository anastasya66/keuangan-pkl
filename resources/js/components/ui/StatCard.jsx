import React from 'react';
import { TrendingUp, TrendingDown, MoreVertical } from 'lucide-react';

export function StatCard({
  icon: Icon,
  iconBg = 'bg-[#E5F6FA]',
  iconColor = 'text-[#55BFD8]',
  label,
  value,
  trend,
  trendDirection = 'up',
  trendPeriod = 'vs Bulan Lalu',
  onOptionsClick,
  className = '',
}) {
  const isPositive = trendDirection === 'up';

  return (
    <div
      className={`bg-white rounded-2xl p-5 border border-[#E7ECEE] shadow-[0_2px_10px_rgba(0,0,0,0.02)] hover:shadow-md hover:border-[#D5E3E7] transition-all card-interactive flex flex-col justify-between ${className}`}
    >
      <div className="flex items-center justify-between">
        {Icon ? (
          <div className={`w-10 h-10 rounded-xl ${iconBg} ${iconColor} flex items-center justify-center shrink-0`}>
            <Icon className="w-5 h-5 stroke-[2.2]" />
          </div>
        ) : <div />}
        <button
          type="button"
          onClick={onOptionsClick}
          className="p-1 rounded-lg text-[#A6AFB3] hover:text-[#20282D] hover:bg-[#F8FAFA] transition-colors"
          title="Opsi"
        >
          <MoreVertical className="w-4 h-4" />
        </button>
      </div>

      <div className="mt-3">
        <div className="text-xl sm:text-2xl font-black text-[#20282D] tracking-tight leading-tight">
          {value}
        </div>
        <div className="text-xs font-medium text-[#879197] mt-0.5">
          {label}
        </div>
      </div>

      {trend !== undefined && trend !== null ? (
        <div className="mt-3 pt-2.5 border-t border-[#F8FAFA] flex items-center gap-1.5 text-xs font-semibold">
          <span
            className={`inline-flex items-center gap-1 ${
              isPositive ? 'text-[#48B58B]' : 'text-[#E47777]'
            }`}
          >
            {isPositive ? (
              <TrendingUp className="w-3.5 h-3.5 stroke-[2.5]" />
            ) : (
              <TrendingDown className="w-3.5 h-3.5 stroke-[2.5]" />
            )}
            <span>{trend}</span>
          </span>
          {trendPeriod && (
            <span className="text-[#A6AFB3] font-normal text-[11px] truncate">
              {trendPeriod}
            </span>
          )}
        </div>
      ) : (
        <div className="mt-3 pt-2.5 border-t border-transparent" />
      )}
    </div>
  );
}

export default StatCard;
