import React, { useState, useEffect, useCallback } from 'react';
import productService from '../../services/productService';
import stockCardService from '../../services/stockCardService';
import { formatRupiah } from '../../utils/currency';
import { IconStockCard } from '../../components/common/Icons';

export function StockCardPage() {
  const [products, setProducts] = useState([]);
  const [selectedProductId, setSelectedProductId] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
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
          setSelectedProductId(res.data[0].id);
        }
      } catch (err) {
        console.error('Gagal memuat produk:', err);
      }
    }
    loadProducts();
  }, []);

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
      case 'initial_stock':
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      case 'sale':
      case 'stock_out':
        return 'bg-blue-50 text-blue-700 border-blue-200';
      case 'adjustment_in':
        return 'bg-teal-50 text-teal-700 border-teal-200';
      case 'adjustment_out':
        return 'bg-amber-50 text-amber-700 border-amber-200';
      case 'purchase_reversal':
      case 'sale_return':
        return 'bg-purple-50 text-purple-700 border-purple-200';
      default:
        return 'bg-gray-50 text-gray-700 border-gray-200';
    }
  };

  return (
    <div className="space-y-6">
      {/* Toast */}
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

      {/* Header */}
      <div>
        <h1 className="text-xl font-bold text-[#16323B] flex items-center gap-2.5">
          <span className="p-2 bg-[#EAF8FA] text-[#167A93] rounded-xl border border-[#38BFE0]/30">
            <IconStockCard className="w-5 h-5" />
          </span>
          Kartu Stok Barang (Stock Card)
        </h1>
        <p className="text-xs text-[#577782] mt-1">
          Buku pembantu pergerakan fisik barang masuk, keluar, dan saldo berjalan per produk
        </p>
      </div>

      {/* Filter Selector Bar */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        <div className="md:col-span-6">
          <label className="block text-xs font-semibold text-[#16323B] mb-1">
            Pilih Produk yang Ingin Dilihat:
          </label>
          <select
            value={selectedProductId}
            onChange={(e) => setSelectedProductId(e.target.value)}
            className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:outline-none focus:border-[#27C5E5] font-semibold"
          >
            {products.map((p) => (
              <option key={p.id} value={p.id}>
                {p.name} ({p.sku}) &bull; Sisa Stok: {p.stock} {p.unit?.symbol || 'pcs'}
              </option>
            ))}
          </select>
        </div>

        <div className="md:col-span-3">
          <label className="block text-xs font-semibold text-[#577782] mb-1">
            Dari Tanggal
          </label>
          <input
            type="date"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
            className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
          />
        </div>

        <div className="md:col-span-3">
          <label className="block text-xs font-semibold text-[#577782] mb-1">
            Sampai Tanggal
          </label>
          <input
            type="date"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
            className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
          />
        </div>
      </div>

      {/* Product Summary Card */}
      {stockCardData && (
        <>
          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <div className="flex items-center gap-2">
                <span className="font-mono text-xs font-bold text-[#167A93] bg-[#EAF8FA] px-2 py-0.5 rounded border border-[#38BFE0]/30">
                  {stockCardData.product.sku}
                </span>
                {stockCardData.product.barcode && (
                  <span className="font-mono text-xs text-[#577782]">
                    Barcode: {stockCardData.product.barcode}
                  </span>
                )}
                <span className="text-xs text-[#577782]">
                  &bull; {stockCardData.product.category_name}
                </span>
              </div>
              <h2 className="text-lg font-bold text-[#16323B] mt-1">
                {stockCardData.product.name}
              </h2>
            </div>

            <div className="flex flex-wrap items-center gap-4 text-xs">
              <div>
                <span className="text-[#577782]">HPP Rata-rata:</span>
                <div className="font-mono font-bold text-[#16323B]">
                  {formatRupiah(stockCardData.product.average_cost)}
                </div>
              </div>
              <div>
                <span className="text-[#577782]">Harga Jual:</span>
                <div className="font-mono font-bold text-[#167A93]">
                  {formatRupiah(stockCardData.product.selling_price)}
                </div>
              </div>
              <div className="px-3 py-1.5 bg-[#EAF8FA] rounded-xl border border-[#38BFE0]/30 text-right">
                <span className="text-[11px] font-semibold text-[#167A93]">Stok Saat Ini</span>
                <div className="text-base font-bold font-mono text-[#16323B]">
                  {stockCardData.product.current_stock} {stockCardData.product.unit_symbol}
                </div>
              </div>
            </div>
          </div>

          {/* 4 Stat Metric Cards */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
              <span className="text-[11px] font-semibold text-[#577782]">Saldo Awal Periode</span>
              <div className="text-xl font-bold font-mono text-[#16323B] mt-1">
                {stockCardData.summary.opening_balance}{' '}
                <span className="text-xs font-normal text-[#577782]">
                  {stockCardData.product.unit_symbol}
                </span>
              </div>
            </div>

            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
              <span className="text-[11px] font-semibold text-emerald-700">Total Barang Masuk (+)</span>
              <div className="text-xl font-bold font-mono text-emerald-600 mt-1">
                +{stockCardData.summary.total_in}{' '}
                <span className="text-xs font-normal text-[#577782]">
                  {stockCardData.product.unit_symbol}
                </span>
              </div>
            </div>

            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs">
              <span className="text-[11px] font-semibold text-rose-600">Total Barang Keluar (-)</span>
              <div className="text-xl font-bold font-mono text-rose-600 mt-1">
                -{stockCardData.summary.total_out}{' '}
                <span className="text-xs font-normal text-[#577782]">
                  {stockCardData.product.unit_symbol}
                </span>
              </div>
            </div>

            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs bg-linear-to-br from-white to-[#EAF8FA]">
              <span className="text-[11px] font-semibold text-[#167A93]">Saldo Akhir Periode</span>
              <div className="text-xl font-bold font-mono text-[#167A93] mt-1">
                {stockCardData.summary.closing_balance}{' '}
                <span className="text-xs font-normal text-[#577782]">
                  {stockCardData.product.unit_symbol}
                </span>
              </div>
            </div>
          </div>

          {/* Chronological Ledger Table */}
          <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs">
            <h3 className="text-sm font-bold text-[#16323B] mb-3">
              Riwayat Pergerakan Fisik Barang (Kronologis)
            </h3>

            {isLoading ? (
              <div className="py-12 text-center text-xs text-[#577782]">
                Memuat riwayat kartu stok...
              </div>
            ) : stockCardData.movements.length === 0 ? (
              <div className="py-12 text-center text-xs text-[#577782]">
                Tidak ada mutasi pergerakan barang pada periode tanggal ini.
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead>
                    <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold bg-[#F8FDFE]">
                      <th className="py-2.5 px-3">Tanggal</th>
                      <th className="py-2.5 px-3">Jenis Mutasi</th>
                      <th className="py-2.5 px-3">No Referensi</th>
                      <th className="py-2.5 px-3">Keterangan</th>
                      <th className="py-2.5 px-3 text-right">Masuk (+)</th>
                      <th className="py-2.5 px-3 text-right">Keluar (-)</th>
                      <th className="py-2.5 px-3 text-right">Saldo Stok</th>
                      <th className="py-2.5 px-3">Operator</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#EAF4F6]">
                    {/* Opening Balance Row */}
                    <tr className="bg-[#FAFDFE] font-semibold">
                      <td className="py-2 px-3 text-[#577782]">{stockCardData.period.start_date}</td>
                      <td className="py-2 px-3">
                        <span className="px-2 py-0.5 text-[10px] font-bold rounded bg-gray-100 text-gray-700">
                          SALDO AWAL
                        </span>
                      </td>
                      <td className="py-2 px-3 font-mono text-[#577782]">-</td>
                      <td className="py-2 px-3 text-[#577782]">Saldo awal sebelum periode pencarian</td>
                      <td className="py-2 px-3 text-right font-mono text-[#577782]">-</td>
                      <td className="py-2 px-3 text-right font-mono text-[#577782]">-</td>
                      <td className="py-2 px-3 text-right font-mono font-bold text-[#16323B]">
                        {stockCardData.summary.opening_balance}
                      </td>
                      <td className="py-2 px-3 text-[#577782]">Sistem</td>
                    </tr>

                    {/* Movement Rows */}
                    {stockCardData.movements.map((mov) => (
                      <tr key={mov.id} className="hover:bg-[#F8FDFE] transition">
                        <td className="py-2.5 px-3 whitespace-nowrap">{mov.movement_date}</td>
                        <td className="py-2.5 px-3">
                          <span
                            className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getMovementBadgeClass(
                              mov.movement_type
                            )}`}
                          >
                            {mov.movement_type_label}
                          </span>
                        </td>
                        <td className="py-2.5 px-3 font-mono font-semibold text-[#167A93]">
                          {mov.reference_number || mov.movement_code}
                        </td>
                        <td className="py-2.5 px-3 text-[#577782] max-w-xs truncate" title={mov.description}>
                          {mov.description || '-'}
                        </td>
                        <td className="py-2.5 px-3 text-right font-mono font-bold text-emerald-600">
                          {mov.quantity_in > 0 ? `+${mov.quantity_in}` : '-'}
                        </td>
                        <td className="py-2.5 px-3 text-right font-mono font-bold text-rose-600">
                          {mov.quantity_out > 0 ? `-${mov.quantity_out}` : '-'}
                        </td>
                        <td className="py-2.5 px-3 text-right font-mono font-bold text-[#16323B]">
                          {mov.balance}
                        </td>
                        <td className="py-2.5 px-3 text-[#577782] whitespace-nowrap">{mov.user_name}</td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot>
                    <tr className="border-t-2 border-[#D7EBEF] font-bold bg-[#EAF8FA] text-[#167A93]">
                      <td colSpan="4" className="py-2.5 px-3 text-right">
                        TOTAL MUTASI PERIODE:
                      </td>
                      <td className="py-2.5 px-3 text-right font-mono text-emerald-700">
                        +{stockCardData.summary.total_in}
                      </td>
                      <td className="py-2.5 px-3 text-right font-mono text-rose-700">
                        -{stockCardData.summary.total_out}
                      </td>
                      <td className="py-2.5 px-3 text-right font-mono text-[#16323B]">
                        {stockCardData.summary.closing_balance} {stockCardData.product.unit_symbol}
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}

export default StockCardPage;
