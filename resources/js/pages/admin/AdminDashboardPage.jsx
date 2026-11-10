import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Layers,
  Clock,
  ArrowDownRight,
  Hash,
  ShoppingBag,
  TrendingUp,
  TrendingDown,
  ArrowUpRight,
  Package,
  Calendar,
  Filter,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';
import StatCard from '../../components/ui/StatCard';
import StatusBadge from '../../components/ui/StatusBadge';
import TabPills from '../../components/ui/TabPills';
import SalesPurchaseChart from '../../components/charts/SalesPurchaseChart';
import TopCategoriesCard from '../../components/charts/TopCategoriesCard';

export function AdminDashboardPage() {
  const { user } = useAuth();

  // Period filter state
  const [period, setPeriod] = useState('this_year');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [isCustomDate, setIsCustomDate] = useState(false);

  // Active tab for Recent Transactions
  const [activeTrxTab, setActiveTrxTab] = useState('all');

  // Data states
  const [loading, setLoading] = useState(true);
  const [summary, setSummary] = useState(null);
  const [chartData, setChartData] = useState([]);
  const [distribution, setDistribution] = useState(null);
  const [activities, setActivities] = useState([]);
  const [inventorySummary, setInventorySummary] = useState(null);
  const [lowStock, setLowStock] = useState([]);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      const params = { period };
      if (period === 'custom' && startDate && endDate) {
        params.start_date = startDate;
        params.end_date = endDate;
      }

      const [sumRes, chartRes, distRes, actRes, invRes, lowRes] = await Promise.all([
        reportService.getFinancialSummary(params),
        reportService.getFinancialChart(params),
        reportService.getExpenseDistribution(params),
        reportService.getRecentActivities(12),
        reportService.getInventorySummary(),
        reportService.getLowStock(6),
      ]);

      setSummary(sumRes.data);
      setChartData(chartRes.data?.points || []);
      setDistribution(distRes.data);
      setActivities(actRes.data || []);
      setInventorySummary(invRes.data);
      setLowStock(lowRes.data || []);
    } catch (err) {
      console.error('Gagal memuat data dashboard:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDashboardData();
  }, [period]);

  const handlePeriodChange = (newPeriod) => {
    setPeriod(newPeriod);
    setIsCustomDate(newPeriod === 'custom');
  };

  const handleCustomFilterSubmit = (e) => {
    e.preventDefault();
    if (startDate && endDate) {
      loadDashboardData();
    }
  };

  // Filter activities based on tab
  const transactionTabs = [
    { id: 'all', label: 'All Transactions' },
    { id: 'sale', label: 'Sale' },
    { id: 'purchase', label: 'Purchase' },
    { id: 'expense', label: 'Expenses' },
    { id: 'invoice', label: 'Invoices' },
  ];

  const filteredActivities = activities.filter((act) => {
    if (activeTrxTab === 'all') return true;
    if (activeTrxTab === 'sale') return act.type === 'income' || act.type === 'sale';
    if (activeTrxTab === 'purchase') return act.type === 'expense' || act.type === 'purchase';
    if (activeTrxTab === 'expense') return act.type === 'expense';
    if (activeTrxTab === 'invoice') return true;
    return true;
  });

  return (
    <div className="space-y-6 animate-fade-in">
      {/* 1. Four Statistics Cards (Reference Image Row 1) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {/* Card 1: Profit */}
        <StatCard
          icon={Layers}
          iconBg="bg-[#E5F6FA]"
          iconColor="text-[#37AFCB]"
          label="Profit"
          value={loading ? '...' : formatRupiah(summary?.net_profit || 0)}
          trend="+35%"
          trendDirection="up"
          trendPeriod="vs Last Month"
        />

        {/* Card 2: Total Sales / Invoice Due */}
        <StatCard
          icon={Clock}
          iconBg="bg-[#EBF7F2]"
          iconColor="text-[#48B58B]"
          label="Invoice Due / Total Sales"
          value={loading ? '...' : formatRupiah(summary?.total_income || 0)}
          trend="+35%"
          trendDirection="up"
          trendPeriod="vs Last Month"
        />

        {/* Card 3: Total Expenses */}
        <StatCard
          icon={ArrowDownRight}
          iconBg="bg-[#FEF7EB]"
          iconColor="text-[#E9B45D]"
          label="Total Expenses"
          value={loading ? '...' : formatRupiah(summary?.total_expense || 0)}
          trend="+35%"
          trendDirection="up"
          trendPeriod="vs Last Month"
        />

        {/* Card 4: Total Payment Returns / Purchase */}
        <StatCard
          icon={Hash}
          iconBg="bg-[#F3EFFE]"
          iconColor="text-[#8B5CF6]"
          label="Total Purchases / Liabilities"
          value={loading ? '...' : formatRupiah(summary?.total_liabilities || 0)}
          trend="-20%"
          trendDirection="down"
          trendPeriod="vs Last Month"
        />
      </div>

      {/* 2. Middle Row: Sales & Purchase Chart + Top Categories (Reference Image Row 2) */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
        <div className="lg:col-span-2">
          <SalesPurchaseChart
            points={chartData}
            period={period}
            onPeriodChange={handlePeriodChange}
          />
        </div>
        <div className="lg:col-span-1">
          <TopCategoriesCard
            distribution={distribution}
            inventorySummary={inventorySummary}
          />
        </div>
      </div>

      {/* 3. Bottom Row: Recent Transactions (Reference Image Row 3) */}
      <div className="bg-white rounded-2xl p-6 border border-[#E7ECEE] shadow-[0_2px_10px_rgba(0,0,0,0.02)] hover:shadow-md hover:border-[#D5E3E7] transition-all">
        {/* Header with Title & View All button */}
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-base font-bold text-[#20282D]">Recent Transactions</h3>
          <Link
            to="/admin/financial/income"
            className="px-4 py-1.5 rounded-full text-xs font-semibold border border-[#E7ECEE] text-[#20282D] hover:bg-[#F1F4F5] transition-all"
          >
            View All
          </Link>
        </div>

        {/* Tab Pills (Sale, Purchase, Quotation, Expenses, Invoices) */}
        <div className="mb-5">
          <TabPills
            tabs={transactionTabs}
            activeTab={activeTrxTab}
            onChange={setActiveTrxTab}
          />
        </div>

        {/* Transactions Table */}
        <div className="overflow-x-auto no-scrollbar">
          <table className="w-full text-left">
            <thead>
              <tr className="border-b border-[#F1F4F5] text-[11px] font-semibold text-[#879197] uppercase tracking-wider">
                <th className="py-3 px-4 font-medium">Customer / Deskripsi</th>
                <th className="py-3 px-4 font-medium">Date</th>
                <th className="py-3 px-4 font-medium">Status</th>
                <th className="py-3 px-4 font-medium text-right">Total</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#F8FAFA] text-xs">
              {loading ? (
                <tr>
                  <td colSpan="4" className="py-8 text-center text-[#879197]">
                    Memuat transaksi terbaru...
                  </td>
                </tr>
              ) : filteredActivities.length === 0 ? (
                <tr>
                  <td colSpan="4" className="py-8 text-center text-[#879197]">
                    Belum ada data transaksi yang cocok pada kategori ini.
                  </td>
                </tr>
              ) : (
                filteredActivities.slice(0, 5).map((act, idx) => {
                  const isPositive = act.type === 'income' || act.type === 'sale';
                  const initials = (act.title || 'TRX')
                    .split(' ')
                    .map((n) => n[0])
                    .join('')
                    .substring(0, 2)
                    .toUpperCase();

                  const avatarColors = [
                    'bg-[#E5F6FA] text-[#37AFCB]',
                    'bg-[#FEF7EB] text-[#E9B45D]',
                    'bg-[#EBF7F2] text-[#48B58B]',
                    'bg-[#F3EFFE] text-[#8B5CF6]',
                    'bg-[#FDF3F3] text-[#E47777]',
                  ];
                  const avatarColor = avatarColors[idx % avatarColors.length];

                  return (
                    <tr
                      key={act.id || idx}
                      className="hover:bg-[#F8FAFA] transition-colors group"
                    >
                      {/* Customer / Avatar */}
                      <td className="py-3.5 px-4">
                        <div className="flex items-center gap-3">
                          <div
                            className={`w-9 h-9 rounded-full ${avatarColor} flex items-center justify-center font-bold text-xs shrink-0`}
                          >
                            {initials}
                          </div>
                          <div>
                            <div className="font-bold text-[#20282D] group-hover:text-[#37AFCB] transition-colors">
                              {act.title}
                            </div>
                            <div className="text-[11px] text-[#879197]">
                              #{act.id ? String(act.id).padStart(5, '0') : '114589'} &bull; {act.type_label || act.type}
                            </div>
                          </div>
                        </div>
                      </td>

                      {/* Date */}
                      <td className="py-3.5 px-4 text-[#20282D] font-medium whitespace-nowrap">
                        {act.date}
                      </td>

                      {/* Status */}
                      <td className="py-3.5 px-4 whitespace-nowrap">
                        <StatusBadge
                          status={isPositive ? 'completed' : 'draft'}
                          label={isPositive ? 'Completed' : 'Draft'}
                        />
                      </td>

                      {/* Total */}
                      <td className="py-3.5 px-4 text-right font-black text-[#20282D] whitespace-nowrap">
                        {act.amount !== null ? formatRupiah(act.amount) : '-'}
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

export default AdminDashboardPage;
