import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';

export function StaffDashboardPage() {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [summary, setSummary] = useState(null);
  const [inventorySummary, setInventorySummary] = useState(null);
  const [lowStock, setLowStock] = useState([]);

  useEffect(() => {
    async function loadData() {
      try {
        const [sumRes, invRes, lowRes] = await Promise.all([
          reportService.getFinancialSummary({ period: 'today' }),
          reportService.getInventorySummary(),
          reportService.getLowStock(6),
        ]);
        setSummary(sumRes.data);
        setInventorySummary(invRes.data);
        setLowStock(lowRes.data || []);
      } catch (err) {
        console.error('Gagal memuat ringkasan staff:', err);
      } finally {
        setLoading(false);
      }
    }
    loadData();
  }, []);

  return (
    <div className="space-y-6">
      {/* Welcome Card */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 sm:p-8 backdrop-blur shadow-xl">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 mb-2">
              <span className="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
              Level Akses: Staff Toko
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
              Halo, {user?.name}!
            </h1>
            <p className="mt-1 text-sm text-slate-400">
              Selamat datang di portal operasional FinToko. Pantau arus kas harian dan status stok barang toko.
            </p>
          </div>
          <Link
            to="/staff/products"
            className="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-semibold text-sm transition-all shadow-lg shadow-cyan-900/30 cursor-pointer self-start sm:self-auto"
          >
            📦 Lihat Katalog Produk
          </Link>
        </div>
      </div>

      {/* Operational Summary (Today) */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
              Pemasukan Hari Ini
            </span>
            <span className="text-xl">💰</span>
          </div>
          <div className="text-2xl font-black text-emerald-400 mt-2 font-mono">
            {loading ? '...' : formatRupiah(summary?.total_income || 0)}
          </div>
          <p className="text-[11px] text-slate-400 mt-1">Transaksi penjualan hari ini</p>
        </div>

        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
              Pengeluaran Hari Ini
            </span>
            <span className="text-xl">💸</span>
          </div>
          <div className="text-2xl font-black text-rose-400 mt-2 font-mono">
            {loading ? '...' : formatRupiah(summary?.total_expense || 0)}
          </div>
          <p className="text-[11px] text-slate-400 mt-1">Biaya operasional kas hari ini</p>
        </div>

        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 backdrop-blur shadow-xl">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
              Total Varian Produk
            </span>
            <span className="text-xl">📦</span>
          </div>
          <div className="text-2xl font-black text-cyan-400 mt-2 font-mono">
            {loading ? '...' : (inventorySummary?.total_products || 0) + ' SKU'}
          </div>
          <p className="text-[11px] text-slate-400 mt-1">
            Total {inventorySummary?.total_units || 0} unit fisik di toko
          </p>
        </div>
      </div>

      {/* Low Stock Warning Table for Staff */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-base font-bold text-white flex items-center gap-2">
            <span>⚠️</span> Peringatan Stok Menipis & Habis
          </h3>
          <span className="text-xs text-slate-400">Segera lakukan restock atau hubungi admin</span>
        </div>

        {lowStock.length === 0 ? (
          <div className="text-center py-8 text-slate-400 text-xs">
            🎉 Semua stok produk dalam batas aman.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="border-b border-slate-700 text-slate-400 uppercase tracking-wider text-[10px]">
                  <th className="py-2.5 px-3">SKU</th>
                  <th className="py-2.5 px-3">Nama Produk</th>
                  <th className="py-2.5 px-3">Kategori</th>
                  <th className="py-2.5 px-3 text-right">Sisa Stok</th>
                  <th className="py-2.5 px-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800">
                {lowStock.map((prod) => (
                  <tr key={prod.id} className="hover:bg-slate-700/40 transition-colors">
                    <td className="py-2.5 px-3 font-mono font-semibold text-slate-300">
                      {prod.sku}
                    </td>
                    <td className="py-2.5 px-3 font-medium text-white">{prod.name}</td>
                    <td className="py-2.5 px-3 text-slate-400">{prod.category || '-'}</td>
                    <td className="py-2.5 px-3 text-right font-mono font-bold text-rose-400">
                      {prod.stock} {prod.unit}
                    </td>
                    <td className="py-2.5 px-3 text-center">
                      <span
                        className={`inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold ${
                          prod.stock <= 0
                            ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                            : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'
                        }`}
                      >
                        {prod.status_label || (prod.stock <= 0 ? 'Habis' : 'Menipis')}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

export default StaffDashboardPage;
