import { Navigate, useLocation } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';

export function ProtectedRoute({ children, allowedRoles }) {
  const { isAuthenticated, user, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return (
      <div className="min-h-screen bg-slate-900 flex flex-col items-center justify-center text-slate-300">
        <div className="w-10 h-10 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin"></div>
        <p className="mt-4 text-sm font-medium">Memverifikasi sesi...</p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Check role authorization
  if (allowedRoles && !allowedRoles.includes(user?.role)) {
    return (
      <div className="min-h-screen bg-slate-900 flex flex-col items-center justify-center p-6 text-center">
        <div className="w-16 h-16 rounded-full bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400 text-2xl font-bold mb-4">
          403
        </div>
        <h1 className="text-2xl font-bold text-white mb-2">Akses Ditolak</h1>
        <p className="text-slate-400 max-w-md mb-6">
          Anda tidak memiliki izin yang cukup untuk mengakses halaman ini. Halaman ini memerlukan hak akses{' '}
          <span className="font-semibold text-emerald-400">{allowedRoles.join(' / ')}</span>.
        </p>
        <Navigate
          to={user?.role === 'admin' ? '/admin/dashboard' : '/staff/dashboard'}
          replace
        />
      </div>
    );
  }

  return children;
}

export default ProtectedRoute;
