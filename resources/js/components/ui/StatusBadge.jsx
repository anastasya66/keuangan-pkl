import React from 'react';

const STATUS_CONFIGS = {
  // Completed / Success / Selesai / Lunas
  completed: { bg: 'bg-[#EBF7F2]', text: 'text-[#48B58B]', label: 'Completed' },
  success: { bg: 'bg-[#EBF7F2]', text: 'text-[#48B58B]', label: 'Sukses' },
  lunas: { bg: 'bg-[#EBF7F2]', text: 'text-[#48B58B]', label: 'Lunas' },
  active: { bg: 'bg-[#EBF7F2]', text: 'text-[#48B58B]', label: 'Aktif' },
  diterima: { bg: 'bg-[#EBF7F2]', text: 'text-[#48B58B]', label: 'Diterima' },

  // Draft / Warning / Sebagian
  draft: { bg: 'bg-[#FDF3F3]', text: 'text-[#E47777]', label: 'Draft' },
  sebagian: { bg: 'bg-[#FEF7EB]', text: 'text-[#E9B45D]', label: 'Sebagian' },
  pending: { bg: 'bg-[#FEF7EB]', text: 'text-[#E9B45D]', label: 'Pending' },
  menipis: { bg: 'bg-[#FEF7EB]', text: 'text-[#E9B45D]', label: 'Stok Menipis' },

  // Danger / Dibatalkan / Habis / Belum Lunas
  cancelled: { bg: 'bg-[#FDF3F3]', text: 'text-[#E47777]', label: 'Dibatalkan' },
  dibatalkan: { bg: 'bg-[#FDF3F3]', text: 'text-[#E47777]', label: 'Dibatalkan' },
  belum_lunas: { bg: 'bg-[#FDF3F3]', text: 'text-[#E47777]', label: 'Belum Lunas' },
  inactive: { bg: 'bg-[#F1F4F5]', text: 'text-[#879197]', label: 'Nonaktif' },
  habis: { bg: 'bg-[#FDF3F3]', text: 'text-[#E47777]', label: 'Stok Habis' },

  // Info / Cyan
  info: { bg: 'bg-[#E5F6FA]', text: 'text-[#37AFCB]', label: 'Info' },
  diproses: { bg: 'bg-[#E5F6FA]', text: 'text-[#37AFCB]', label: 'Diproses' },
};

export function StatusBadge({ status, label, className = '' }) {
  const normalizedKey = String(status || '').toLowerCase().trim();
  const config = STATUS_CONFIGS[normalizedKey] || {
    bg: 'bg-[#F1F4F5]',
    text: 'text-[#879197]',
    label: label || status,
  };

  const displayLabel = label || config.label || status;

  return (
    <span
      className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap transition-colors ${config.bg} ${config.text} ${className}`}
    >
      {displayLabel}
    </span>
  );
}

export default StatusBadge;

