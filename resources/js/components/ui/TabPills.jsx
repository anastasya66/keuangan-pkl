import React from 'react';

export function TabPills({ tabs = [], activeTab, onChange, className = '' }) {
  return (
    <div className={`flex items-center gap-2 overflow-x-auto no-scrollbar py-1 ${className}`}>
      {tabs.map((tab) => {
        const id = typeof tab === 'object' ? tab.id : tab;
        const label = typeof tab === 'object' ? tab.label : tab;
        const count = typeof tab === 'object' ? tab.count : undefined;
        const isActive = activeTab === id;

        return (
          <button
            key={id}
            type="button"
            onClick={() => onChange(id)}
            className={`px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 cursor-pointer shrink-0 select-none ${
              isActive
                ? 'bg-[#20282D] text-white shadow-sm'
                : 'bg-[#F1F4F5] text-[#879197] hover:text-[#20282D] hover:bg-[#E7ECEE]'
            }`}
          >
            <span>{label}</span>
            {count !== undefined && (
              <span
                className={`ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold ${
                  isActive ? 'bg-white/20 text-white' : 'bg-[#E7ECEE] text-[#879197]'
                }`}
              >
                {count}
              </span>
            )}
          </button>
        );
      })}
    </div>
  );
}

export default TabPills;

