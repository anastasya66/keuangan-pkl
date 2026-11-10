import React from 'react';

export function DataTable({
  columns = [],
  data = [],
  isLoading = false,
  emptyMessage = 'Tidak ada data yang ditemukan.',
  renderRow,
}) {
  return (
    <div className="w-full overflow-hidden rounded-2xl border border-[#E7ECEE] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
      <div className="overflow-x-auto no-scrollbar">
        <table className="w-full text-left text-xs text-[#20282D]">
          <thead className="bg-[#F8FAFA] text-[11px] font-semibold uppercase tracking-wider text-[#879197] border-b border-[#E7ECEE]">
            <tr>
              {columns.map((col, index) => (
                <th
                  key={index}
                  scope="col"
                  className={`px-4 py-3.5 font-medium ${col.className || ''}`}
                >
                  {col.header || col.label}
                </th>
              ))}
            </tr>
          </thead>

          <tbody className="divide-y divide-[#F1F4F5]">
            {isLoading ? (
              // Skeleton loading rows
              Array.from({ length: 5 }).map((_, idx) => (
                <tr key={idx} className="animate-pulse">
                  {columns.map((_, colIdx) => (
                    <td key={colIdx} className="px-4 py-4">
                      <div className="h-4 bg-[#F1F4F5] rounded-md w-3/4"></div>
                    </td>
                  ))}
                </tr>
              ))
            ) : data.length === 0 ? (
              // Empty State
              <tr>
                <td
                  colSpan={columns.length}
                  className="px-6 py-12 text-center text-[#879197]"
                >
                  <div className="flex flex-col items-center justify-center gap-2">
                    <span className="text-3xl">📦</span>
                    <p className="text-xs font-medium text-[#879197]">{emptyMessage}</p>
                  </div>
                </td>
              </tr>
            ) : typeof renderRow === 'function' ? (
              // Custom Data Rows
              data.map((item, index) => renderRow(item, index))
            ) : (
              // Declarative Fallback Data Rows
              data.map((item, index) => (
                <tr key={item.id || index} className="hover:bg-[#F8FAFA] transition-colors">
                  {columns.map((col, colIdx) => (
                    <td
                      key={colIdx}
                      className={`px-4 py-3.5 ${col.className || (col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '')}`}
                    >
                      {typeof col.render === 'function'
                        ? col.render(item, index)
                        : (item[col.key || col.field] ?? '-')}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default DataTable;
