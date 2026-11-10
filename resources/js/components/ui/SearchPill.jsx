import React from 'react';
import { Search } from 'lucide-react';

export function SearchPill({
  value,
  onChange,
  placeholder = 'Search here...',
  className = '',
  autoFocus = false,
}) {
  return (
    <div className={`relative flex items-center ${className}`}>
      <Search className="w-4 h-4 text-[#879197] absolute left-3.5 pointer-events-none" />
      <input
        type="text"
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        autoFocus={autoFocus}
        className="w-full pl-10 pr-4 py-2 bg-[#F1F4F5] hover:bg-[#EAEFF1] focus:bg-white text-xs font-semibold text-[#20282D] placeholder-[#A6AFB3] rounded-full border border-transparent focus:border-[#55BFD8] focus:outline-none focus:ring-2 focus:ring-[#55BFD8]/20 transition-all"
      />
    </div>
  );
}

export default SearchPill;
