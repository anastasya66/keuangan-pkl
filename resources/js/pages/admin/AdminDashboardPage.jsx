import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import categoryService from '../../services/categoryService';
import productService from '../../services/productService';
import unitService from '../../services/unitService';
import userService from '../../services/userService';

export function AdminDashboardPage() {
  const { user } = useAuth();
  const [stats, setStats] = useState({
    totalUsers: 0,
    totalProducts: 0,
    totalCategories: 0,
    totalUnits: 0,
    isLoading: true,
  });

  useEffect(() => {
    async function loadStats() {
      try {
        const [usersRes, prodRes, catRes, unitRes] = await Promise.all([
          userService.getUsers({ per_page: 1 }),
          productService.getProducts({ per_page: 1 }),
          categoryService.getCategories({ per_page: 1 }),
          unitService.getUnits({ per_page: 1 }),
        ]);

        setStats({
          totalUsers: usersRes.pagination?.total ?? 0,
          totalProducts: prodRes.pagination?.total ?? 0,
          totalCategories: catRes.pagination?.total ?? 0,
          totalUnits: unitRes.pagination?.total ?? 0,
          isLoading: false,
        });
      } catch (err) {
        console.error('Gagal memuat ringkasan:', err);
        setStats((prev) => ({ ...prev, isLoading: false }));
      }
    }
    loadStats();
  }, []);

  return (
    <div className="space-y-6">
      {/* Welcome Banner */}
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
              Kelola master data produk, kategori, satuan, serta otorisasi staf toko FinToko secara terpusat.
            </p>
          </div>
          <Link
            to="/admin/products"
            className="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition-all shadow-lg shadow-emerald-900/30 cursor-pointer self-start sm:self-auto"
          >
            📦 Kelola Master Produk
          </Link>
        </div>
      </div>

      {/* Quick Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {/* Products Card */}
        <Link
          to="/admin/products"
          className="bg-slate-800/60 hover:bg-slate-800/90 border border-slate-700/70 rounded-2xl p-5 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-2xl">📦</span>
            <span className="text-xs text-slate-400 group-hover:text-emerald-400 transition-colors">
              Buka &rarr;
            </span>
          </div>
          <div className="text-2xl font-bold text-white mt-3">
            {stats.isLoading ? '...' : stats.totalProducts}
          </div>
          <div className="text-xs font-semibold text-slate-300 mt-0.5">Master Produk</div>
          <p className="text-[11px] text-slate-400 mt-1">Total produk katalog terdaftar</p>
        </Link>

        {/* Categories Card */}
        <Link
          to="/admin/categories"
          className="bg-slate-800/60 hover:bg-slate-800/90 border border-slate-700/70 rounded-2xl p-5 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-2xl">🏷️</span>
            <span className="text-xs text-slate-400 group-hover:text-emerald-400 transition-colors">
              Buka &rarr;
            </span>
          </div>
          <div className="text-2xl font-bold text-emerald-400 mt-3">
            {stats.isLoading ? '...' : stats.totalCategories}
          </div>
          <div className="text-xs font-semibold text-slate-300 mt-0.5">Kategori Produk</div>
          <p className="text-[11px] text-slate-400 mt-1">Pengelompokan barang toko</p>
        </Link>

        {/* Units Card */}
        <Link
          to="/admin/units"
          className="bg-slate-800/60 hover:bg-slate-800/90 border border-slate-700/70 rounded-2xl p-5 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-2xl">📏</span>
            <span className="text-xs text-slate-400 group-hover:text-emerald-400 transition-colors">
              Buka &rarr;
            </span>
          </div>
          <div className="text-2xl font-bold text-cyan-400 mt-3">
            {stats.isLoading ? '...' : stats.totalUnits}
          </div>
          <div className="text-xs font-semibold text-slate-300 mt-0.5">Satuan Ukuran</div>
          <p className="text-[11px] text-slate-400 mt-1">Standarisasi pcs, kg, liter, dll</p>
        </Link>

        {/* Users Card */}
        <Link
          to="/admin/users"
          className="bg-slate-800/60 hover:bg-slate-800/90 border border-slate-700/70 rounded-2xl p-5 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-2xl">👥</span>
            <span className="text-xs text-slate-400 group-hover:text-emerald-400 transition-colors">
              Buka &rarr;
            </span>
          </div>
          <div className="text-2xl font-bold text-amber-400 mt-3">
            {stats.isLoading ? '...' : stats.totalUsers}
          </div>
          <div className="text-xs font-semibold text-slate-300 mt-0.5">Pengguna Sistem</div>
          <p className="text-[11px] text-slate-400 mt-1">Akun admin & staf kasir</p>
        </Link>
      </div>
    </div>
  );
}

export default AdminDashboardPage;
