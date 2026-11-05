import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import userService from '../../services/userService';

export function AdminDashboardPage() {
  const { user } = useAuth();
  const [stats, setStats] = useState({
    totalUsers: 0,
    activeStaff: 0,
    isLoading: true,
  });

  useEffect(() => {
    async function loadStats() {
      try {
        const response = await userService.getUsers({ per_page: 100 });
        if (response.success && response.data) {
          const total = response.data.length;
          const staffCount = response.data.filter(
            (u) => u.role === 'staff' && u.is_active
          ).length;
          setStats({ totalUsers: total, activeStaff: staffCount, isLoading: false });
        }
      } catch (err) {
        console.error('Gagal memuat ringkasan user:', err);
        setStats((prev) => ({ ...prev, isLoading: false }));
      }
    }
    loadStats();
  }, []);

  return (
    <div className="space-y-6">
      {/* Welcome Card */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 sm:p-8 backdrop-blur shadow-xl">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 mb-2">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              Level Akses: Administrator
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
              Selamat Datang, {user?.name}!
            </h1>
            <p className="mt-1 text-sm text-slate-400">
              Anda memiliki wewenang penuh untuk mengelola pengguna, peran, dan pengaturan sistem FinToko.
            </p>
          </div>
          <Link
            to="/admin/users"
            className="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition-all shadow-lg shadow-emerald-900/30 cursor-pointer"
          >
            👥 Kelola Pengguna
          </Link>
        </div>
      </div>

      {/* Quick Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div className="bg-slate-800/50 border border-slate-700/70 rounded-2xl p-5">
          <div className="text-xs font-semibold uppercase text-slate-400">Total Akun Terdaftar</div>
          <div className="text-3xl font-bold text-white mt-2">
            {stats.isLoading ? '...' : stats.totalUsers}
          </div>
          <p className="text-xs text-slate-500 mt-1">Akun administrator & staff toko</p>
        </div>

        <div className="bg-slate-800/50 border border-slate-700/70 rounded-2xl p-5">
          <div className="text-xs font-semibold uppercase text-slate-400">Staff Aktif</div>
          <div className="text-3xl font-bold text-emerald-400 mt-2">
            {stats.isLoading ? '...' : stats.activeStaff}
          </div>
          <p className="text-xs text-slate-500 mt-1">Staf kasir siap bertugas</p>
        </div>

        <div className="bg-slate-800/50 border border-slate-700/70 rounded-2xl p-5">
          <div className="text-xs font-semibold uppercase text-slate-400">Status Fondasi Tahap 2</div>
          <div className="inline-flex items-center gap-1.5 text-sm font-semibold text-teal-300 mt-3">
            <span className="w-2 h-2 rounded-full bg-teal-400"></span>
            Authentication & User Verified
          </div>
          <p className="text-xs text-slate-500 mt-1">Sanctum Token & Policies Active</p>
        </div>
      </div>
    </div>
  );
}

export default AdminDashboardPage;
