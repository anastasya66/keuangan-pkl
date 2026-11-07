import React from 'react';

export function StatusBadge({ isActive }) {
  if (isActive) {
    return (
      <span className="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-600 text-white whitespace-nowrap shadow-2xs">
        Aktif
      </span>
    );
  }

  return (
    <span className="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-500 text-white whitespace-nowrap shadow-2xs">
      Nonaktif
    </span>
  );
}

export default StatusBadge;
