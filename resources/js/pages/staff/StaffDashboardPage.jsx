import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import productService from '../../services/productService';

export function StaffDashboardPage() {
  const { user } = useAuth();
  const [activeProductsCount, setActiveProductsCount] = useState(0);

  useEffect(() => {
    async function loadStats() {
      try {
        const response = await productService.getProducts({ per_page: 1 });
        if (response.success) {
          setActiveProductsCount(response.pagination?.total ?? 0);
        }
      } catch (err) {
        console.error('Gagal memuat produk aktif:', err);
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
            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 mb-2">
              <span className="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
              Level Akses: Staff Toko
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
              Halo, {user?.name}!
            </h1>
            <p className="mt-1 text-sm text-slate-400">
              Selamat datang di portal operasional FinToko. Anda dapat melihat katalog produk aktif, kategori, dan satuan toko.
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

      {/* Quick Nav Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <Link
          to="/staff/products"
          className="bg-slate-800/60 hover:bg-slate-800/90 border border-slate-700/70 rounded-2xl p-5 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-2xl">📦</span>
            <span className="text-xs text-cyan-400 group-hover:underline">Buka &rarr;</span>
          </div>
          <div className="text-xl font-bold text-white mt-3">Katalog Produk</div>
          <p className="text-xs text-slate-400 mt-1">
            {activeProductsCount} produk aktif siap ditransaksikan
          </p>
        </Link>

        <Link
          to="/staff/categories"
          className="bg-slate-800/60 hover:bg-slate-800/90 border border-slate-700/70 rounded-2xl p-5 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-2xl">🏷️</span>
            <span className="text-xs text-cyan-400 group-hover:underline">Buka &rarr;</span>
          </div>
          <div className="text-xl font-bold text-white mt-3">Kategori Produk</div>
          <p className="text-xs text-slate-400 mt-1">Daftar kelompok barang aktif</p>
        </Link>

        <Link
          to="/staff/units"
          className="bg-slate-800/60 hover:bg-slate-800/90 border border-slate-700/70 rounded-2xl p-5 transition-all group"
        >
          <div className="flex items-center justify-between">
            <span className="text-2xl">📏</span>
            <span className="text-xs text-cyan-400 group-hover:underline">Buka &rarr;</span>
          </div>
          <div className="text-xl font-bold text-white mt-3">Satuan Ukuran</div>
          <p className="text-xs text-slate-400 mt-1">Daftar satuan aktif toko</p>
        </Link>
      </div>
    </div>
  );
}

export default StaffDashboardPage;
