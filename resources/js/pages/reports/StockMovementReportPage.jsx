import React, { useEffect, useState, useMemo } from 'react';
import reportService from '../../services/reportService';

export default function StockMovementReportPage() {
  // Rentang tanggal default: 30 hari ke belakang sampai hari ini agar semua transaksi terbaru langsung tampil
  const today = new Date().toISOString().split('T')[0];
  const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

  const [startDate, setStartDate] = useState(thirtyDaysAgo);
  const [endDate, setEndDate] = useState(today);
  const [movementType, setMovementType] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [exportingPdf, setExportingPdf] = useState(false);
  const [exportingExcel, setExportingExcel] = useState(false);
  const [error, setError] = useState(null);
  const [lastRefreshed, setLastRefreshed] = useState(null);

  // Ambil data laporan mutasi persediaan dari API
  const fetchReport = async () => {
    setLoading(true);
    setError(null);
    try {
      const params = {};
      if (startDate) params.start_date = startDate;
      if (endDate) params.end_date = endDate;
      if (movementType) params.movement_type = movementType;

      const res = await reportService.getStockMovements(params);
      setData(res.data);
      setLastRefreshed(new Date());
    } catch (err) {
      console.error('Gagal mengambil Riwayat Mutasi:', err);
      setError('Gagal memuat riwayat mutasi persediaan.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReport();
  }, [startDate, endDate, movementType]);

  const handleExportPdf = async () => {
    setExportingPdf(true);
    try {
      const params = {};
      if (startDate) params.start_date = startDate;
      if (endDate) params.end_date = endDate;
      if (movementType) params.movement_type = movementType;
      if (searchQuery) params.search = searchQuery;
      await reportService.downloadStockMovementsPdf(params);
    } catch (err) {
      alert(err.message || 'Gagal mengunduh file PDF Mutasi Stok.');
    } finally {
      setExportingPdf(false);
    }
  };

  const handleExportExcel = async () => {
    setExportingExcel(true);
    try {
      const params = {};
      if (startDate) params.start_date = startDate;
      if (endDate) params.end_date = endDate;
      if (movementType) params.movement_type = movementType;
      if (searchQuery) params.search = searchQuery;
      await reportService.downloadStockMovementsExcel(params);
    } catch (err) {
      alert(err.message || 'Gagal mengunduh file Excel Mutasi Stok.');
    } finally {
      setExportingExcel(false);
    }
  };

  // Ekstrak list mutasi secara aman dari response API
  const rawMovements = useMemo(() => {
    if (!data) return [];
    return data.movements || data.items || [];
  }, [data]);

  // Client-side quick search untuk performa responsif instan
  const filteredMovements = useMemo(() => {
    if (!searchQuery.trim()) return rawMovements;
    const q = searchQuery.toLowerCase().trim();
    return rawMovements.filter((m) => {
      const code = (m.code || m.movement_code || '').toLowerCase();
      const ref = (m.reference_number || m.ref || '').toLowerCase();
      const productName = (m.product_name || '').toLowerCase();
      const sku = (m.sku || m.product_sku || '').toLowerCase();
      const desc = (m.description || m.notes || '').toLowerCase();
      const creator = (m.creator_name || m.creator || '').toLowerCase();
      const typeLabel = (m.type_label || m.movement_type_label || '').toLowerCase();

      return (
        code.includes(q) ||
        ref.includes(q) ||
        productName.includes(q) ||
        sku.includes(q) ||
        desc.includes(q) ||
        creator.includes(q) ||
        typeLabel.includes(q)
      );
    });
  }, [rawMovements, searchQuery]);

  // Helper badge tampilan tipe mutasi
  const getBadgeStyle = (rawType, category) => {
    if (category === 'in' || ['stock_in', 'purchase', 'initial_stock', 'sale_return'].includes(rawType)) {
      return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    }
    if (category === 'out' || ['stock_out', 'sale', 'purchase_reversal', 'purchase_return'].includes(rawType)) {
      return 'bg-rose-50 text-rose-800 border-rose-200';
    }
    return 'bg-sky-50 text-sky-800 border-sky-200';
  };

  const formatDateTime = (dateStr) => {
    if (!dateStr || dateStr === '-') return '-';
    if (typeof dateStr === 'string' && (dateStr.includes('T') || (dateStr.includes('-') && dateStr.length >= 10))) {
      const d = new Date(dateStr);
      if (!isNaN(d.getTime())) {
        const day = String(d.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const month = months[d.getMonth()];
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const mins = String(d.getMinutes()).padStart(2, '0');
        return `${day} ${month} ${year}, ${hours}:${mins}`;
      }
    }
    return dateStr;
  };

  const handleResetDates = () => {
    setStartDate(thirtyDaysAgo);
    setEndDate(today);
  };

  return (
    <div className="space-y-4">
      {/* 1. Header Bar Proporsional & Ringkas */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-[#D7EBEF] rounded-2xl p-4 sm:p-5 shadow-xs">
        <div>
          <div className="flex items-center gap-3">
            <span className="p-2.5 bg-[#167A93] text-white rounded-xl shadow-xs shrink-0">
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
            </span>
            <div>
              <div className="flex flex-wrap items-center gap-2">
                <h1 className="text-xl sm:text-2xl font-bold text-[#16323B] tracking-tight">Riwayat Keluar Masuk</h1>
                <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Real-Time
                </span>
              </div>
              <p className="text-xs text-[#577782] mt-0.5">
                Catatan audit pergerakan keluar dan masuk inventaris toko.
              </p>
            </div>
          </div>
        </div>

        {/* Action Buttons Group */}
        <div className="flex items-center gap-2.5 shrink-0 self-start sm:self-center">
          {/* Real-time Refresh */}
          <button
            onClick={fetchReport}
            disabled={loading}
            title="Muat ulang dan sinkronkan data mutasi terbaru"
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#F8FDFE] hover:bg-[#D7EBEF]/50 text-[#167A93] border border-[#D7EBEF] font-semibold text-xs transition-all shadow-2xs cursor-pointer disabled:opacity-50"
          >
            <svg className={`w-3.5 h-3.5 ${loading ? 'animate-spin text-[#27C5E5]' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>{loading ? 'Menyinkronkan...' : 'Sinkronkan'}</span>
          </button>

          {/* Export PDF */}
          <button
            onClick={handleExportPdf}
            disabled={exportingPdf || loading}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-xs transition-all shadow-2xs disabled:opacity-50 cursor-pointer"
          >
            <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span>{exportingPdf ? 'Mengunduh...' : 'PDF'}</span>
          </button>

          {/* Export Excel */}
          <button
            onClick={handleExportExcel}
            disabled={exportingExcel || loading}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-semibold text-xs transition-all shadow-2xs disabled:opacity-50 cursor-pointer"
          >
            <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>{exportingExcel ? 'Mengunduh...' : 'Excel'}</span>
          </button>
        </div>
      </div>

      {/* 2. Summary Bar Minimalis */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-0 md:divide-x md:divide-[#D7EBEF]">
          {/* Metric 1: Total Mutasi */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">Total Transaksi Mutasi</span>
            <div className="text-xl sm:text-2xl font-extrabold font-mono text-[#16323B] mt-0.5">
              {loading ? '...' : (data?.summary?.total_movements || 0)}{' '}
              <span className="text-xs font-normal text-[#577782]">Catatan</span>
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Total pencatatan</span>
          </div>

          {/* Metric 2: Total Barang Masuk */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">Barang Masuk (Pembelian)</span>
            <div className="text-xl sm:text-2xl font-extrabold font-mono text-emerald-700 mt-0.5">
              {loading ? '...' : `+${Number(data?.summary?.total_stock_in || 0).toLocaleString('id-ID')}`}{' '}
              <span className="text-xs font-normal text-emerald-600">Unit</span>
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Stok bertambah</span>
          </div>

          {/* Metric 3: Total Barang Keluar */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">Barang Keluar (Penjualan)</span>
            <div className="text-xl sm:text-2xl font-extrabold font-mono text-rose-700 mt-0.5">
              {loading ? '...' : `-${Number(data?.summary?.total_stock_out || 0).toLocaleString('id-ID')}`}{' '}
              <span className="text-xs font-normal text-rose-600">Unit</span>
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Stok berkurang</span>
          </div>

          {/* Metric 4: Penyesuaian Opname */}
          <div className="px-3 py-1">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#577782] block">Penyesuaian Opname</span>
            <div className="text-xl sm:text-2xl font-extrabold font-mono text-[#167A93] mt-0.5">
              {loading ? '...' : Number(data?.summary?.total_adjustments || 0).toLocaleString('id-ID')}{' '}
              <span className="text-xs font-normal text-[#167A93]">Unit</span>
            </div>
            <span className="text-[10px] text-[#8EA6AF]">Selisih opname</span>
          </div>
        </div>
      </div>

      {/* 3. Filter & Instant Search Bar */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
        <div className="flex flex-col lg:flex-row items-center gap-3">
          {/* Quick Search Input */}
          <div className="relative flex-1 w-full">
            <span className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#577782]">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Cari Kode Mutasi, No. Ref (PO/POS), Produk, SKU, atau Keterangan..."
              className="w-full pl-9 pr-8 py-2 text-xs bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-[#16323B] placeholder-[#8EA6AF] focus:bg-white focus:outline-none focus:border-[#27C5E5] focus:ring-2 focus:ring-[#27C5E5]/20 transition-all"
            />
            {searchQuery && (
              <button
                type="button"
                onClick={() => setSearchQuery('')}
                className="absolute inset-y-0 right-0 pr-2.5 flex items-center text-[#8EA6AF] hover:text-[#16323B] cursor-pointer"
              >
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            )}
          </div>

          {/* Filter Tipe Mutasi */}
          <div className="w-full sm:w-48">
            <select
              value={movementType}
              onChange={(e) => setMovementType(e.target.value)}
              className="w-full py-2 px-3 text-xs bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-[#16323B] focus:bg-white focus:outline-none focus:border-[#27C5E5] focus:ring-2 focus:ring-[#27C5E5]/20 transition-all cursor-pointer"
            >
              <option value="">Semua Tipe Mutasi</option>
              <option value="in">Barang Masuk (Pembelian)</option>
              <option value="out">Barang Keluar (Penjualan)</option>
              <option value="adjustment">Penyesuaian Opname</option>
            </select>
          </div>

          {/* Filter Dari Tanggal */}
          <div className="w-full sm:w-auto flex items-center gap-2">
            <div className="flex items-center gap-1.5">
              <span className="text-[11px] text-[#577782] whitespace-nowrap">Dari:</span>
              <input
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
                className="py-1.5 px-2.5 text-xs bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-[#16323B] focus:bg-white focus:outline-none focus:border-[#27C5E5] transition-all cursor-pointer"
              />
            </div>
            <div className="flex items-center gap-1.5">
              <span className="text-[11px] text-[#577782] whitespace-nowrap">Sampai:</span>
              <input
                type="date"
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
                className="py-1.5 px-2.5 text-xs bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-[#16323B] focus:bg-white focus:outline-none focus:border-[#27C5E5] transition-all cursor-pointer"
              />
            </div>
            {(startDate !== thirtyDaysAgo || endDate !== today) && (
              <button
                type="button"
                onClick={handleResetDates}
                title="Kembalikan ke 30 hari terakhir"
                className="text-[11px] text-[#167A93] hover:underline cursor-pointer whitespace-nowrap"
              >
                Reset
              </button>
            )}
          </div>

          {/* Info baris */}
          <div className="text-xs text-[#577782] whitespace-nowrap self-end lg:self-center px-1 font-medium">
            {filteredMovements.length} transaksi
          </div>
        </div>
      </div>

      {/* 4. Tabel Riwayat Mutasi Persediaan Lengkap */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl shadow-xs overflow-hidden">
        {loading ? (
          <div className="text-center py-16 text-[#577782]">
            <div className="inline-block w-8 h-8 border-3 border-[#27C5E5] border-t-transparent rounded-full animate-spin mb-3"></div>
            <p className="text-xs font-semibold text-[#16323B]">Memuat riwayat pergerakan stok...</p>
            <p className="text-[11px] text-[#8EA6AF] mt-0.5">Menghubungkan arus masuk pembelian &amp; kasir penjualan</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 px-4">
            <p className="text-rose-600 font-semibold text-xs mb-3">{error}</p>
            <button
              onClick={fetchReport}
              className="px-3.5 py-1.5 rounded-xl bg-[#27C5E5] hover:bg-[#167A93] text-white text-xs font-semibold transition-all cursor-pointer shadow-xs"
            >
              Coba Lagi
            </button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-[#F8FDFE] text-[#577782] font-semibold border-b border-[#D7EBEF]">
                <tr className="uppercase tracking-wider text-[10px]">
                  <th className="py-3 px-3 text-center w-12 whitespace-nowrap">No</th>
                  <th className="py-3 px-3 w-40 whitespace-nowrap">Tanggal &amp; Waktu</th>
                  <th className="py-3 px-3 w-40 whitespace-nowrap">Kode Mutasi</th>
                  <th className="py-3 px-3 min-w-[200px]">Produk</th>
                  <th className="py-3 px-3 text-center w-36 whitespace-nowrap">Tipe</th>
                  <th className="py-3 px-3 text-right w-32 whitespace-nowrap">Kuantitas</th>
                  <th className="py-3 px-3 min-w-[220px]">Keterangan / Alasan</th>
                  <th className="py-3 px-3 w-36 whitespace-nowrap">Petugas</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#D7EBEF]/60">
                {filteredMovements.length === 0 ? (
                  <tr>
                    <td colSpan="8" className="py-12 text-center text-[#577782]">
                      <svg className="w-10 h-10 mx-auto text-[#8EA6AF]/60 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                      <p className="font-semibold text-xs text-[#16323B]">Tidak ada pergerakan stok tercatat</p>
                      <p className="text-[11px] text-[#8EA6AF] mt-0.5">
                        Coba sesuaikan rentang tanggal atau ubah kata kunci pencarian
                      </p>
                    </td>
                  </tr>
                ) : (
                  filteredMovements.map((m, idx) => {
                    const rawType = m.movement_type || m.type || '';
                    const isIn =
                      m.type_category === 'in' ||
                      ['stock_in', 'purchase', 'initial_stock', 'sale_return'].includes(rawType);
                    const isOut =
                      m.type_category === 'out' ||
                      ['stock_out', 'sale', 'purchase_reversal', 'purchase_return'].includes(rawType);

                    const unit = m.unit_symbol || m.unit || 'unit';
                    const code = m.movement_code || m.code || `MUT-${String(m.id).padStart(5, '0')}`;
                    const ref = m.reference_number || m.ref || '';
                    const dateFormatted = m.date || m.date_formatted || m.movement_date || '-';
                    const typeLabel = m.movement_type_label || m.type_label || (isIn ? 'Masuk' : 'Keluar');
                    const desc = m.description || m.notes || (isIn ? 'Pembelian Stok' : 'Penjualan Kasir POS');
                    const creator = m.creator_name || m.creator || 'Administrator';

                    return (
                      <tr key={m.id || idx} className="hover:bg-[#F8FDFE] transition-colors">
                        {/* No */}
                        <td className="py-3 px-3 text-center text-[#8EA6AF] font-mono text-[11px]">
                          {idx + 1}
                        </td>

                        {/* Tanggal & Waktu */}
                        <td className="py-3 px-3 text-[#16323B] whitespace-nowrap font-medium text-xs">
                          {formatDateTime(dateFormatted)}
                        </td>

                        {/* Kode Mutasi & Referensi */}
                        <td className="py-3 px-3 whitespace-nowrap">
                          <div className="font-mono font-bold text-[#167A93]">{code}</div>
                          {ref && ref !== code && (
                            <div className="text-[10px] text-[#577782] font-mono mt-0.5 whitespace-nowrap">
                              Ref: <span className="text-[#16323B] font-semibold">{ref}</span>
                            </div>
                          )}
                        </td>

                        {/* Produk & SKU */}
                        <td className="py-3 px-3">
                          <div className="font-semibold text-[#16323B]">{m.product_name || '-'}</div>
                          <div className="text-[10px] text-[#577782] font-mono mt-0.5 whitespace-nowrap">
                            SKU: {m.product_sku || m.sku || '-'}
                          </div>
                        </td>

                        {/* Tipe Mutasi Badge */}
                        <td className="py-3 px-3 text-center whitespace-nowrap">
                          <span
                            className={`inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border shadow-2xs whitespace-nowrap ${getBadgeStyle(
                              rawType,
                              m.type_category
                            )}`}
                          >
                            {typeLabel}
                          </span>
                        </td>

                        {/* Kuantitas (+ / -) */}
                        <td
                          className={`py-3 px-3 text-right font-mono tabular-nums font-extrabold whitespace-nowrap ${
                            isIn ? 'text-emerald-700 bg-emerald-50/30' : isOut ? 'text-rose-700 bg-rose-50/30' : 'text-[#167A93]'
                          }`}
                        >
                          {isIn ? '+' : isOut ? '-' : ''}
                          {Number(m.quantity || 0).toLocaleString('id-ID')}{' '}
                          <span className="text-[10px] font-normal text-[#577782]">{unit}</span>
                        </td>

                        {/* Keterangan / Alasan */}
                        <td className="py-3 px-3 text-[#16323B] max-w-sm">
                          <div className="text-xs leading-relaxed">{desc}</div>
                        </td>

                        {/* Petugas */}
                        <td className="py-3 px-3 text-[#577782] whitespace-nowrap">
                          <div className="flex items-center gap-1.5">
                            <span className="w-5 h-5 rounded-full bg-[#D7EBEF] text-[#167A93] font-bold text-[9px] flex items-center justify-center uppercase shrink-0">
                              {creator.charAt(0)}
                            </span>
                            <span className="text-xs font-medium text-[#16323B]">{creator}</span>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
