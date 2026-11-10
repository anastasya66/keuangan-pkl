import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Layers,
  Clock,
  ArrowDownRight,
  Package,
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  ShoppingBag,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';
import StatCard from '../../components/ui/StatCard';
import StatusBadge from '../../components/ui/StatusBadge';

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
    <div className="space-y-6 animate-fade-in">
      {/* 3 Metric Cards for Staff */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <StatCard
          icon={TrendingUp}
          iconBg="bg-[#EBF7F2]"
          iconColor="text-[#48B58B]"
          label="Uang Masuk Hari Ini"
          value={loading ? '...' : formatRupiah(summary?.total_income || 0)}
          trend="+12%"
          trendDirection="up"
          trendPeriod="vs Kemarin"
        />

        <StatCard
          icon={TrendingDown}
          iconBg="bg-[#FDF3F3]"
          iconColor="text-[#E47777]"
          label="Uang Keluar Hari Ini"
          value={loading ? '...' : formatRupiah(summary?.total_expense || 0)}
          trend="-5%"
          trendDirection="down"
          trendPeriod="vs Kemarin"
        />

        <StatCard
          icon={Package}
          iconBg="bg-[#E5F6FA]"
          iconColor="text-[#37AFCB]"
          label="Total Produk Aktif"
          value={loading ? '...' : `${inventorySummary?.total_products || 0} Barang`}
          trend={`${inventorySummary?.total_units || 0} unit`}
          trendDirection="up"
          trendPeriod="stok fisik"
        />
      </div>

      {/* Low Stock Warning Table */}
      <div className="bg-white rounded-2xl p-6 border border-[#E7ECEE] shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 text-[#E9B45D]" />
            <h3 className="text-base font-bold text-[#20282D]">
              Produk Perlu Ditambah (Stok Menipis)
            </h3>
          </div>
          <span className="text-xs text-[#879197] font-medium">Batas: &le; 10 unit</span>
        </div>

        {lowStock.length === 0 ? (
          <div className="text-center py-8 text-[#879197] text-xs">
            🎉 Bagus! Semua stok barang masih aman.
          </div>
        ) : (
          <div className="overflow-x-auto no-scrollbar">
            <table className="w-full text-left">
              <thead>
                <tr className="border-b border-[#F1F4F5] text-[11px] font-semibold text-[#879197] uppercase tracking-wider">
                  <th className="py-3 px-4 font-medium">Kode SKU</th>
                  <th className="py-3 px-4 font-medium">Nama Barang</th>
                  <th className="py-3 px-4 font-medium">Kategori</th>
                  <th className="py-3 px-4 font-medium text-right">Sisa Stok</th>
                  <th className="py-3 px-4 font-medium text-center">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#F8FAFA] text-xs">
                {lowStock.map((prod) => (
                  <tr key={prod.id} className="hover:bg-[#F8FAFA] transition-colors">
                    <td className="py-3 px-4 font-mono font-bold text-[#20282D]">
                      {prod.sku}
                    </td>
                    <td className="py-3 px-4 font-semibold text-[#20282D]">
                      {prod.name}
                    </td>
                    <td className="py-3 px-4 text-[#879197]">
                      {prod.category || '-'}
                    </td>
                    <td className="py-3 px-4 text-right font-bold text-[#E47777]">
                      {prod.stock} {prod.unit}
                    </td>
                    <td className="py-3 px-4 text-center">
                      <StatusBadge
                        status={prod.stock <= 0 ? 'habis' : 'menipis'}
                        label={prod.stock <= 0 ? 'Stok Habis' : 'Stok Menipis'}
                      />
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
