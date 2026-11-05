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
    <div className="min-h-screen bg-[#EAF8FA] flex flex-col items-center justify-center p-6 text-center">
      <div className="max-w-md w-full bg-white border border-[#D7EBEF] rounded-3xl p-8 sm:p-10 shadow-xl text-center space-y-4">
        <div className="w-16 h-16 rounded-2xl bg-[#EAF8FA] border border-[#A9DDE7] text-[#27C5E5] flex items-center justify-center mx-auto text-3xl font-bold">
          404
        </div>
        <h1 className="text-xl sm:text-2xl font-extrabold text-[#16323B]">
          Halaman Tidak Ditemukan
        </h1>
        <p className="text-[#5A7C85] text-sm leading-relaxed">
          Halaman yang Anda tuju tidak tersedia atau telah dipindahkan ke tautan lain.
        </p>
        <div className="pt-2">
          <Link
            to={homePath}
            className="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-semibold text-xs sm:text-sm shadow-md shadow-[#27C5E5]/20 transition-all cursor-pointer"
          >
            ← Kembali ke Beranda FinToko
          </Link>
        </div>
      </div>
    </div>
  );
}

export default NotFoundPage;
