import React from 'react';

export function DataTable({
  columns = [],
  data = [],
  isLoading = false,
  emptyMessage = 'Tidak ada data yang ditemukan.',
  renderRow,
}) {
  return (
    <div className="w-full overflow-hidden rounded-2xl border border-[#D7EBEF] bg-white shadow-xs">
      <div className="overflow-x-auto no-scrollbar">
        <table className="w-full text-left text-sm text-[#16323B]">
          <thead className="bg-[#F3FBFC] text-xs font-semibold uppercase tracking-wider text-[#577782] border-b border-[#D7EBEF]">
            <tr>
              {columns.map((col, index) => (
                <th
                  key={index}
                  scope="col"
                  className={`px-4 py-3.5 ${col.className || ''}`}
                >
                  {col.header || col.label}
                </th>
              ))}
            </tr>
          </thead>

          <tbody className="divide-y divide-[#EBF5F8]">
            {isLoading ? (
              // Skeleton loading rows
              Array.from({ length: 5 }).map((_, idx) => (
                <tr key={idx} className="animate-pulse">
                  {columns.map((_, colIdx) => (
                    <td key={colIdx} className="px-4 py-4">
                      <div className="h-4 bg-[#E5F5F8] rounded-md w-3/4"></div>
                    </td>
                  ))}
                </tr>
              ))
            ) : data.length === 0 ? (
              // Empty State
              <tr>
                <td
                  colSpan={columns.length}
                  className="px-6 py-12 text-center text-[#8EA6AF]"
                >
                  <div className="flex flex-col items-center justify-center gap-2">
                    <span className="text-3xl">📦</span>
                    <p className="text-sm font-medium text-[#577782]">{emptyMessage}</p>
                  </div>
                </td>
              </tr>
            ) : typeof renderRow === 'function' ? (
              // Custom Data Rows
              data.map((item, index) => renderRow(item, index))
            ) : (
              // Declarative Fallback Data Rows
              data.map((item, index) => (
                <tr key={item.id || index} className="hover:bg-[#F3FBFC] transition-colors">
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
