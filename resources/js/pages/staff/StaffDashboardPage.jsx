import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';
import {
  IconBox,
  IconTrendingUp,
  IconTrendingDown,
} from '../../components/common/Icons';

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
      <div className="bg-gradient-to-r from-white via-[#F8FDFF] to-[#EBF8FB] border border-[#D7EBEF] rounded-3xl p-6 sm:p-8 shadow-xs relative overflow-hidden">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#E3F6FA] text-[#167A93] border border-[#38BFE0]/30 mb-2">
              <span className="w-1.5 h-1.5 rounded-full bg-[#27C5E5] animate-pulse"></span>
              Portal Staf Toko
            </div>
            <h1 className="text-2xl sm:text-3xl font-black text-[#10262E] tracking-tight">
              Halo, {user?.name}! 👋
            </h1>
            <p className="mt-1 text-xs sm:text-sm text-[#577782]">
              Semangat bekerja! Pantau uang kas masuk, keluar hari ini, dan stok barang toko dengan mudah.
            </p>
          </div>
          <Link
            to="/staff/products"
            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-semibold text-xs shadow-md shadow-[#27C5E5]/20 transition-all cursor-pointer self-start sm:self-auto btn-press"
          >
            <IconBox className="w-4 h-4 text-white" />
            <span>Katalog Barang</span>
          </Link>
        </div>
      </div>

      {/* Operational Summary Cards (Today) */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div className="bg-white border border-[#D7EBEF] rounded-3xl p-5 shadow-xs hover:border-[#36B878]/50 card-interactive">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-[#577782] uppercase tracking-wider">
              Uang Masuk Hari Ini
            </span>
            <div className="w-9 h-9 rounded-xl bg-emerald-50 text-[#36B878] flex items-center justify-center border border-emerald-100">
              <IconTrendingUp className="w-4 h-4" />
            </div>
          </div>
          <div className="text-2xl font-black text-[#10262E] mt-3 font-mono">
            {loading ? '...' : formatRupiah(summary?.total_income || 0)}
          </div>
          <p className="text-xs text-[#36B878] mt-1 font-semibold">Kas masuk tercatat hari ini</p>
        </div>

        <div className="bg-white border border-[#D7EBEF] rounded-3xl p-5 shadow-xs hover:border-[#E66D79]/50 card-interactive">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-[#577782] uppercase tracking-wider">
              Uang Keluar Hari Ini
            </span>
            <div className="w-9 h-9 rounded-xl bg-rose-50 text-[#E66D79] flex items-center justify-center border border-rose-100">
              <IconTrendingDown className="w-4 h-4" />
            </div>
          </div>
          <div className="text-2xl font-black text-[#10262E] mt-3 font-mono">
            {loading ? '...' : formatRupiah(summary?.total_expense || 0)}
          </div>
          <p className="text-xs text-[#E66D79] mt-1 font-semibold">Biaya belanja toko hari ini</p>
        </div>

        <div className="bg-white border border-[#D7EBEF] rounded-3xl p-5 shadow-xs hover:border-[#38BFE0]/50 card-interactive">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold text-[#577782] uppercase tracking-wider">
              Barang &amp; Sisa Stok
            </span>
            <div className="w-9 h-9 rounded-xl bg-[#F0FAFC] text-[#27C5E5] flex items-center justify-center border border-[#D7EBEF]">
              <IconBox className="w-4 h-4" />
            </div>
          </div>
          <div className="text-2xl font-black text-[#167A93] mt-3 font-mono">
            {loading ? '...' : `${inventorySummary?.total_products || 0} Barang`}
          </div>
          <p className="text-xs text-[#577782] mt-1">
            Total {inventorySummary?.total_units || 0} unit fisik di etalase
          </p>
        </div>
      </div>

      {/* Low Stock Warning Table for Staff */}
      <div className="bg-white border border-[#D7EBEF] rounded-3xl p-6 shadow-xs">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-sm font-bold text-[#10262E] flex items-center gap-2">
            <span className="p-1 rounded-md bg-amber-50 text-amber-600">⚠️</span>
            <span>Barang yang Perlu Ditambah (Stok Menipis)</span>
          </h3>
          <span className="text-xs text-[#8EA6AF]">Segera lakukan restock barang</span>
        </div>

        {lowStock.length === 0 ? (
          <div className="text-center py-8 text-[#8EA6AF] text-xs">
            🎉 Bagus! Seluruh stok barang masih aman di atas 10 unit.
          </div>
        ) : (
          <div className="overflow-x-auto no-scrollbar">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="border-b border-[#D7EBEF] text-[#8EA6AF] uppercase tracking-wider text-[10px]">
                  <th className="py-2.5 px-3">Kode SKU</th>
                  <th className="py-2.5 px-3">Nama Barang</th>
                  <th className="py-2.5 px-3">Kategori</th>
                  <th className="py-2.5 px-3 text-right">Sisa Stok</th>
                  <th className="py-2.5 px-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#EBF5F8]">
                {lowStock.map((prod) => (
                  <tr key={prod.id} className="hover:bg-[#F3FBFC] transition-colors">
                    <td className="py-2.5 px-3 font-mono font-bold text-[#16323B]">
                      {prod.sku}
                    </td>
                    <td className="py-2.5 px-3 font-medium text-[#10262E]">{prod.name}</td>
                    <td className="py-2.5 px-3 text-[#577782]">{prod.category || '-'}</td>
                    <td className="py-2.5 px-3 text-right font-mono font-bold text-[#E66D79]">
                      {prod.stock} {prod.unit}
                    </td>
                    <td className="py-2.5 px-3 text-center">
                      <span
                        className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                          prod.stock <= 0
                            ? 'bg-rose-50 text-rose-700 border border-rose-200'
                            : 'bg-amber-50 text-amber-700 border border-amber-200'
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
