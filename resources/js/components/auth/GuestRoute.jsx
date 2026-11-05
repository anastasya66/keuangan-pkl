import { Navigate } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';

export function GuestRoute({ children }) {
  const { isAuthenticated, user, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="min-h-screen bg-slate-900 flex flex-col items-center justify-center text-slate-300">
        <div className="w-10 h-10 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin"></div>
        <p className="mt-4 text-sm font-medium">Memuat FinToko...</p>
      </div>
    );
  }

  if (isAuthenticated) {
    const target = user?.role === 'admin' ? '/admin/dashboard' : '/staff/dashboard';
    return <Navigate to={target} replace />;
  }

  return children;
}

export default GuestRoute;

