import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';

export function StaffDashboardPage() {
  const { user } = useAuth();

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
              Selamat datang di portal operasional FinToko. Akun Anda memiliki izin akses kasir dan operasional harian.
            </p>
          </div>
          <Link
            to="/profile"
            className="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-semibold text-sm transition-all shadow-lg shadow-cyan-900/30 cursor-pointer"
          >
            👤 Pengaturan Profil
          </Link>
        </div>
      </div>

      {/* Info Status Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div className="bg-slate-800/50 border border-slate-700/70 rounded-2xl p-6">
          <div className="text-xs font-semibold uppercase text-slate-400">Status Akun</div>
          <div className="flex items-center gap-2 mt-2">
            <span className="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
            <span className="text-lg font-bold text-white">Aktif & Terverifikasi</span>
          </div>
          <p className="text-xs text-slate-500 mt-2">
            Email terdaftar: <span className="text-slate-300 font-mono">{user?.email}</span>
          </p>
        </div>

        <div className="bg-slate-800/50 border border-slate-700/70 rounded-2xl p-6">
          <div className="text-xs font-semibold uppercase text-slate-400">Batasan Otorisasi</div>
          <div className="flex items-center gap-2 mt-2">
            <span className="text-amber-400 text-base">🛡️</span>
            <span className="text-sm font-semibold text-slate-200">Staff Restricted Scope</span>
          </div>
          <p className="text-xs text-slate-500 mt-2">
            Anda dapat melihat dan memperbarui profil pribadi, namun tidak memiliki hak mengelola pengguna lain.
          </p>
        </div>
      </div>
    </div>
  );
}

export default StaffDashboardPage;

