import React from 'react';
import { Search, X } from 'lucide-react';

export function SearchInput({ value, onChange, placeholder = 'Search here...', onClear, className = '' }) {
  return (
    <div className={`relative w-full max-w-sm ${className}`}>
      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#879197]">
        <Search className="w-4 h-4 stroke-[2]" />
      </div>

      <input
        type="text"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full pl-10 pr-9 py-2 rounded-full bg-white border border-[#E7ECEE] text-[#20282D] placeholder-[#879197] text-xs font-medium focus:outline-none focus:border-[#55BFD8] focus:ring-2 focus:ring-[#55BFD8]/20 shadow-xs transition-all"
      />

      {value && (
        <button
          type="button"
          onClick={onClear}
          className="absolute inset-y-0 right-0 pr-3 flex items-center text-[#879197] hover:text-[#20282D] transition-colors cursor-pointer"
          title="Hapus pencarian"
        >
          <X className="w-3.5 h-3.5" />
        </button>
      )}
    </div>
  );
}

export default SearchInput;
