import { Link } from 'react-router-dom';
import useAuth from '../hooks/useAuth';

export function NotFoundPage() {
  const { user, isAuthenticated } = useAuth();

  const homePath = !isAuthenticated
    ? '/login'
    : user?.role === 'admin'
    ? '/admin/dashboard'
    : '/staff/dashboard';

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col items-center justify-center p-6 text-center">
      <div className="text-7xl font-extrabold text-slate-800 tracking-widest mb-4">404</div>
      <h1 className="text-2xl font-bold text-white mb-2">Halaman Tidak Ditemukan</h1>
      <p className="text-slate-400 max-w-sm mb-6 text-sm">
        Halaman yang Anda tuju tidak tersedia atau telah dipindahkan.
      </p>
      <Link
        to={homePath}
        className="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition-all shadow-lg"
      >
        Kembali ke Beranda
      </Link>
    </div>
  );
}

export default NotFoundPage;
