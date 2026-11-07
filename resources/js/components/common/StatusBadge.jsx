import React from 'react';

export function StatusBadge({ isActive }) {
  if (isActive) {
    return (
      <span className="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
        Aktif
      </span>
    );
  }

  return (
    <span className="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200">
      Nonaktif
    </span>
  );
}

export default StatusBadge;
