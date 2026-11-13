import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Link, useLocation, useSearchParams } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import productService from '../../services/productService';
import stockCardService from '../../services/stockCardService';
import { formatRupiah } from '../../utils/currency';
import { IconStockCard, IconPos } from '../../components/common/Icons';

/**
 * Format tanggal ramah pengguna (contoh: 10 Nov 2026)
 */
function formatDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return new Intl.DateTimeFormat('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(d);
  } catch {
    return dateStr;
  }
}

/**
 * Helper default tanggal awal bulan berjalan
 */
function getStartOfMonth() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  return `${y}-${m}-01`;
}

/**
 * Helper default tanggal hari ini
 */
function getToday() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

export function StockCardPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const location = useLocation();
  const [searchParams] = useSearchParams();

  const [products, setProducts] = useState([]);
  const [selectedProductId, setSelectedProductId] = useState('');
  const [startDate, setStartDate] = useState(getStartOfMonth());
  const [endDate, setEndDate] = useState(getToday());
  const [stockCardData, setStockCardData] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  // Toast
  const [toast, setToast] = useState(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  // Load all products for dropdown
  useEffect(() => {
    async function loadProducts() {
      try {
        const res = await productService.getProducts({ all: true, is_active: true });
        if (res.success && res.data.length > 0) {
          setProducts(res.data);
          const initialId = searchParams.get('product_id') || location.state?.productId || res.data[0].id;
          setSelectedProductId(initialId);
        }
      } catch (err) {
        console.error('Gagal memuat produk:', err);
      }
    }
    loadProducts();
  }, [searchParams, location.state]);

  const fetchStockCard = useCallback(
    async (productId = selectedProductId, start = startDate, end = endDate) => {
      if (!productId) return;
      setIsLoading(true);
      try {
        const params = {};
        if (start) params.start_date = start;
        if (end) params.end_date = end;

        const res = await stockCardService.getStockCard(productId, params);
        if (res.success) {
          setStockCardData(res.data);
        }
      } catch (err) {
        showToast(err.response?.data?.message || 'Gagal memuat kartu stok', 'error');
      } finally {
        setIsLoading(false);
      }
    },
    [selectedProductId, startDate, endDate]
  );

  useEffect(() => {
    if (selectedProductId) {
      fetchStockCard(selectedProductId, startDate, endDate);
    }
  }, [selectedProductId, startDate, endDate, fetchStockCard]);

  const getMovementBadgeClass = (type) => {
    switch (type) {
      case 'purchase':
      case 'stock_in':
        return 'bg-[#48B58B] text-white font-bold';
      case 'initial_stock':
        return 'bg-[#20282D] text-white font-bold';
      case 'sale':
      case 'stock_out':
        return 'bg-[#55BFD8] text-white font-bold';
      case 'adjustment_in':
        return 'bg-[#37AFCB] text-white font-bold';
      case 'adjustment_out':
        return 'bg-[#E9B45D] text-white font-bold';
      case 'purchase_reversal':
      case 'purchase_return':
      case 'sale_return':
        return 'bg-[#8B5CF6] text-white font-bold';
      default:
        return 'bg-[#20282D] text-white font-bold';
    }
  };

  const handleResetDateFilter = () => {
    setStartDate('');
    setEndDate('');
  };

  // Filter pergerakan mutasi: sembunyikan baris dari tahun 2020 lampau
  const displayMovements = useMemo(() => {
    if (!stockCardData?.movements) return [];
    return stockCardData.movements.filter((mov) => !mov.movement_date?.startsWith('2020'));
  }, [stockCardData]);

  // Sembunyikan baris saldo awal jika tanggalnya adalah tahun 2020 lampau
  const showOpeningBalanceRow = useMemo(() => {
    if (!stockCardData?.period?.start_date) return false;
    if (stockCardData.period.start_date.startsWith('2020')) return false;
    return Boolean(startDate);
  }, [stockCardData, startDate]);

  return (
    <div className="space-y-4">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2 ${
            toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-700'
              : 'bg-emerald-50 border-emerald-200 text-emerald-700'
          }`}
        >
          <span>{toast.type === 'error' ? '⚠️' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      {/* Header Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-[#D7EBEF] rounded-2xl p-4 sm:p-5 shadow-xs">
        <div>
          <div className="flex items-center gap-2.5">
            <span className="p-2 bg-[#167A93] text-white rounded-xl shadow-xs">
              <IconStockCard className="w-5 h-5" />
            </span>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-xl font-bold text-[#16323B]">Kartu Stok</h1>
                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Sistem Mutasi
                </span>
              </div>
              <p className="text-xs text-[#577782] mt-0.5">
                Laporan pergerakan keluar dan masuk stok barang.
              </p>
            </div>
          </div>
        </div>

        {/* Action Buttons Group */}
        <div className="flex items-center gap-2 shrink-0">
          <button
            type="button"
            onClick={() => fetchStockCard(selectedProductId, startDate, endDate)}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-[#F8FDFE] border border-[#D7EBEF] hover:bg-[#EAF8FA] text-[#16323B] font-semibold rounded-xl text-xs transition cursor-pointer"
            title="Segarkan data kartu stok"
          >
            <span>🔄</span>
            <span>Segarkan</span>
          </button>

          <Link
            to={isAdmin ? '/admin/pos' : '/staff/pos'}
            className="flex items-center gap-1.5 px-4 py-2 bg-[#27C5E5] hover:bg-[#167A93] text-white font-bold rounded-xl text-xs shadow-xs transition cursor-pointer"
          >
            <IconPos className="w-4 h-4" />
            <span>+ Kasir</span>
          </Link>
        </div>
      </div>

      {/* Baris Filter Produk & Tanggal */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-end">
          {/* Pemilihan Produk */}
          <div className="lg:col-span-6">
            <label className="block text-[11px] font-bold text-[#167A93] uppercase tracking-wider mb-1">
              Pilih Produk yang Ingin Dilihat:
            </label>
            <select
              value={selectedProductId}
              onChange={(e) => setSelectedProductId(e.target.value)}
              className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] focus:border-[#27C5E5] rounded-xl text-xs text-[#16323B] font-semibold focus:outline-none transition"
            >
              {products.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.name} ({p.sku}) &bull; Sisa Stok: {p.stock} {p.unit?.symbol || 'pcs'}
                </option>
              ))}
            </select>
          </div>

          {/* Filter Rentang Tanggal */}
          <div className="lg:col-span-6">
            <div className="flex items-center justify-between mb-1">
              <label className="block text-[11px] font-bold text-[#577782] uppercase tracking-wider">
                Periode Mutasi:
              </label>
              {(startDate || endDate) && (
                <button
                  type="button"
                  onClick={handleResetDateFilter}
                  className="text-[10px] text-rose-500 hover:text-rose-700 font-semibold cursor-pointer"
                >
                  Reset Tanggal
                </button>
              )}
            </div>

            <div className="grid grid-cols-2 gap-2">
              <div className="relative">
                <input
                  type="date"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                  className="w-full px-2.5 py-1.5 bg-[#F8FDFE] border border-[#D7EBEF] focus:border-[#27C5E5] rounded-xl text-xs text-[#16323B] focus:outline-none"
                  title="Dari Tanggal"
                />
              </div>
              <div className="relative">
                <input
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="w-full px-2.5 py-1.5 bg-[#F8FDFE] border border-[#D7EBEF] focus:border-[#27C5E5] rounded-xl text-xs text-[#16323B] focus:outline-none"
                  title="Sampai Tanggal"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Informasi Detail Produk & Summary Bar Statistik */}
      {stockCardData && (
        <div className="bg-white border border-[#D7EBEF] rounded-2xl shadow-xs overflow-hidden">
          {/* Header Info Produk Terpilih */}
          <div className="p-4 sm:p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <div className="flex flex-wrap items-center gap-2">
                <span className="font-mono text-xs font-bold text-[#20282D] bg-[#E5F6FA] px-2.5 py-1 rounded-lg border border-[#55BFD8] shadow-xs">
                  {stockCardData.product.sku}
                </span>
                {stockCardData.product.barcode && (
                  <span className="font-mono text-xs text-[#879197] bg-[#F1F4F5] px-2.5 py-1 rounded-lg border border-[#E7ECEE]">
                    Barcode: {stockCardData.product.barcode}
                  </span>
                )}
                <span className="text-xs text-[#879197]">
                  &bull; Kategori: <strong className="text-[#20282D]">{stockCardData.product.category_name}</strong>
                </span>
              </div>
              <h2 className="text-lg font-bold text-[#20282D] mt-2">
                {stockCardData.product.name}
              </h2>
            </div>

            {/* Financial Parameters & Current Stock Pill */}
            <div className="flex flex-wrap items-center gap-3 sm:gap-4 text-xs">
              {isAdmin && (
                <div className="bg-[#F8FAFA] border border-[#E7ECEE] px-3.5 py-2 rounded-xl">
                  <span className="text-[#879197] text-[10px] block uppercase font-semibold">HPP Rata-rata</span>
                  <span className="font-mono font-bold text-[#20282D]">
                    {formatRupiah(stockCardData.product.average_cost)}
                  </span>
                </div>
              )}

              <div className="bg-[#F8FAFA] border border-[#E7ECEE] px-3.5 py-2 rounded-xl">
                <span className="text-[#879197] text-[10px] block uppercase font-semibold">Harga Jual</span>
                <span className="font-mono font-bold text-[#37AFCB]">
                  {formatRupiah(stockCardData.product.selling_price)}
                </span>
              </div>

              <div className="px-4 py-2.5 bg-[#20282D] text-white rounded-xl shadow-xs text-right shrink-0">
                <span className="text-[10px] font-semibold text-[#879197] block uppercase tracking-wider">Stok Saat Ini</span>
                <div className="text-base font-black font-mono text-white">
                  {stockCardData.product.current_stock} {stockCardData.product.unit_symbol}
                </div>
              </div>
            </div>
          </div>

          {/* Panel Ringkasan Metrik Mutasi Periode */}
          <div className="grid grid-cols-2 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-[#EAF4F6] bg-[#F8FDFE] border-t border-[#D7EBEF]">
            {/* Saldo Awal Periode */}
            <div className="p-3.5 sm:px-5">
              <span className="text-[11px] font-semibold text-[#577782] uppercase tracking-wider">
                Saldo Awal Periode
              </span>
              <div className="text-lg font-bold font-mono text-[#16323B] mt-0.5">
                {stockCardData.summary.opening_balance}{' '}
                <span className="text-xs font-normal text-[#577782]">
                  {stockCardData.product.unit_symbol}
                </span>
              </div>
            </div>

            {/* Total Barang Masuk */}
            <div className="p-3.5 sm:px-5 bg-emerald-50/20">
              <span className="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">
                Total Masuk (+)
              </span>
              <div className="text-lg font-bold font-mono text-emerald-600 mt-0.5">
                +{stockCardData.summary.total_in}{' '}
                <span className="text-xs font-normal text-[#577782]">
                  {stockCardData.product.unit_symbol}
                </span>
              </div>
            </div>

            {/* Total Barang Keluar */}
            <div className="p-3.5 sm:px-5 bg-rose-50/20">
              <span className="text-[11px] font-semibold text-rose-700 uppercase tracking-wider">
                Total Keluar (&minus;)
              </span>
              <div className="text-lg font-bold font-mono text-rose-600 mt-0.5">
                -{stockCardData.summary.total_out}{' '}
                <span className="text-xs font-normal text-[#577782]">
                  {stockCardData.product.unit_symbol}
                </span>
              </div>
            </div>

            {/* Saldo Akhir Periode */}
            <div className="p-3.5 sm:px-5 bg-gradient-to-br from-[#F8FDFE] to-[#EAF8FA]/50">
              <span className="text-[11px] font-bold text-[#167A93] uppercase tracking-wider">
                Saldo Akhir Periode
              </span>
              <div className="text-lg font-extrabold font-mono text-[#167A93] mt-0.5">
                {stockCardData.summary.closing_balance}{' '}
                <span className="text-xs font-normal text-[#577782]">
                  {stockCardData.product.unit_symbol}
                </span>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Tabel Kronologis Pergerakan Fisik Barang */}
      {stockCardData && (
        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-xs">
            <h3 className="font-bold uppercase tracking-wider text-[#16323B]">
              Riwayat Pergerakan Fisik Barang (Kronologis)
            </h3>
            <span className="text-[#577782]">
              Periode:{' '}
              <strong className="text-[#16323B]">
                {stockCardData.period.start_date?.startsWith('2020') && !startDate
                  ? `Hingga ${formatDate(stockCardData.period.end_date)}`
                  : `${formatDate(stockCardData.period.start_date)} s/d ${formatDate(stockCardData.period.end_date)}`}
              </strong>
            </span>
          </div>

          {isLoading ? (
            <div className="py-12 text-center text-xs text-[#577782]">
              <div className="animate-spin text-2xl mb-1.5">🔄</div>
              Memuat mutasi kartu stok...
            </div>
          ) : displayMovements.length === 0 && !showOpeningBalanceRow ? (
            <div className="py-12 text-center text-xs text-[#577782] bg-[#F8FDFE] rounded-xl border border-[#D7EBEF]">
              <div className="text-2xl mb-1">📦</div>
              Tidak ada mutasi pergerakan barang pada periode tanggal ini.
            </div>
          ) : (
            <div className="overflow-x-auto rounded-xl border border-[#D7EBEF]">
              <table className="w-full text-xs text-left border-collapse">
                <thead>
                  <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold bg-[#F0F9FB]">
                    <th className="py-2.5 px-3.5 text-left whitespace-nowrap">Tanggal</th>
                    <th className="py-2.5 px-3.5 text-left whitespace-nowrap">Jenis Mutasi</th>
                    <th className="py-2.5 px-3.5 text-left whitespace-nowrap">No Referensi</th>
                    <th className="py-2.5 px-3.5 text-left">Keterangan</th>
                    <th className="py-2.5 px-3.5 text-right whitespace-nowrap w-28">Masuk (+)</th>
                    <th className="py-2.5 px-3.5 text-right whitespace-nowrap w-28">Keluar (&minus;)</th>
                    <th className="py-2.5 px-3.5 text-right whitespace-nowrap w-28">Saldo Stok</th>
                    <th className="py-2.5 px-3.5 text-left whitespace-nowrap w-32">Operator</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#F0F9FB]">
                  {/* Row Saldo Awal Periode (Hanya tampil jika ada filter periode dan bukan tahun 2020 lampau) */}
                  {showOpeningBalanceRow && (
                    <tr className="bg-[#FAFDFE] font-semibold">
                      <td className="py-2.5 px-3.5 text-[#577782] whitespace-nowrap">
                        {formatDate(stockCardData.period.start_date)}
                      </td>
                      <td className="py-2.5 px-3.5 whitespace-nowrap">
                        <span className="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-slate-700 text-white whitespace-nowrap inline-flex items-center">
                          SALDO AWAL
                        </span>
                      </td>
                      <td className="py-2.5 px-3.5 font-mono text-[#8FA8B0]">-</td>
                      <td className="py-2.5 px-3.5 text-[#577782]">Saldo awal sebelum periode pencarian</td>
                      <td className="py-2.5 px-3.5 text-right font-mono text-[#8FA8B0]">-</td>
                      <td className="py-2.5 px-3.5 text-right font-mono text-[#8FA8B0]">-</td>
                      <td className="py-2.5 px-3.5 text-right font-mono tabular-nums font-bold text-xs text-[#16323B] whitespace-nowrap">
                        {stockCardData.summary.opening_balance}
                      </td>
                      <td className="py-2.5 px-3.5 text-[#577782] whitespace-nowrap">Sistem</td>
                    </tr>
                  )}

                  {/* Movement Rows */}
                  {displayMovements.map((mov) => (
                    <tr key={mov.id} className="hover:bg-[#F8FDFE] transition border-b border-[#F0F9FB]">
                      <td className="py-2.5 px-3.5 text-[#577782] whitespace-nowrap font-medium">
                        {formatDate(mov.movement_date)}
                      </td>
                      <td className="py-2.5 px-3.5 whitespace-nowrap">
                        <span
                          className={`px-2.5 py-0.5 text-[10px] font-bold rounded-md shadow-2xs whitespace-nowrap inline-flex items-center gap-1 ${getMovementBadgeClass(
                            mov.movement_type
                          )}`}
                        >
                          {mov.movement_type_label}
                        </span>
                      </td>
                      <td className="py-2.5 px-3.5 font-mono font-semibold text-xs text-[#167A93] whitespace-nowrap">
                        {mov.reference_number || mov.movement_code}
                      </td>
                      <td className="py-2.5 px-3.5 text-xs text-[#577782] max-w-xs truncate" title={mov.description}>
                        {mov.description || '-'}
                      </td>
                      <td className="py-2.5 px-3.5 text-right font-mono tabular-nums font-bold text-xs text-emerald-600 whitespace-nowrap">
                        {mov.quantity_in > 0 ? `+${mov.quantity_in}` : '-'}
                      </td>
                      <td className="py-2.5 px-3.5 text-right font-mono tabular-nums font-bold text-xs text-rose-600 whitespace-nowrap">
                        {mov.quantity_out > 0 ? `-${mov.quantity_out}` : '-'}
                      </td>
                      <td className="py-2.5 px-3.5 text-right font-mono tabular-nums font-bold text-xs text-[#16323B] whitespace-nowrap">
                        {mov.balance}
                      </td>
                      <td className="py-2.5 px-3.5 text-xs text-[#577782] whitespace-nowrap">
                        <div className="flex items-center gap-1.5">
                          <span className="w-1.5 h-1.5 rounded-full bg-[#167A93]"></span>
                          <span className="font-medium text-[#16323B]">{mov.user_name || 'Sistem'}</span>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
                <tfoot>
                  <tr className="border-t-2 border-[#D7EBEF] font-bold bg-[#EAF8FA] text-[#167A93]">
                    <td colSpan="4" className="py-3 px-3.5 text-right uppercase tracking-wider text-[11px]">
                      Total Mutasi Periode:
                    </td>
                    <td className="py-3 px-3.5 text-right font-mono tabular-nums text-xs text-emerald-700 whitespace-nowrap">
                      +{stockCardData.summary.total_in}
                    </td>
                    <td className="py-3 px-3.5 text-right font-mono tabular-nums text-xs text-rose-700 whitespace-nowrap">
                      -{stockCardData.summary.total_out}
                    </td>
                    <td className="py-3 px-3.5 text-right font-mono tabular-nums text-xs text-[#16323B] whitespace-nowrap">
                      {stockCardData.summary.closing_balance} {stockCardData.product.unit_symbol}
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default StockCardPage;
