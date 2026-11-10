import React from 'react';

export function PageHeader({
  title,
  subtitle,
  actions,
  className = '',
}) {
  return (
    <div className={`flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 ${className}`}>
      <div>
        <h1 className="text-xl sm:text-2xl font-black text-[#20282D] tracking-tight">
          {title}
        </h1>
        {subtitle && (
          <p className="text-xs font-medium text-[#879197] mt-1">
            {subtitle}
          </p>
        )}
      </div>

      {actions && (
        <div className="flex items-center gap-2.5 flex-wrap">
          {actions}
        </div>
      )}
    </div>
  );
}

export default PageHeader;
