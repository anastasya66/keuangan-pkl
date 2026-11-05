import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import useAuth from '../hooks/useAuth';

export function AdminLayout({ children }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const navClass = ({ isActive }) =>
    `px-3 py-1.5 rounded-xl text-xs sm:text-sm font-medium transition-all whitespace-nowrap ${
      isActive
        ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-semibold'
        : 'text-slate-400 hover:text-white hover:bg-slate-800'
    }`;

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
      {/* Top Navigation Bar */}
      <header className="bg-slate-900/90 backdrop-blur border-b border-slate-800 sticky top-0 z-30">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center gap-6">
            <Link to="/admin/dashboard" className="flex items-center gap-2">
              <span className="text-xl font-extrabold tracking-tight bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">
                FinToko
              </span>
              <span className="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                Admin
              </span>
            </Link>

            <nav className="hidden lg:flex items-center gap-1.5">
              <NavLink to="/admin/dashboard" className={navClass}>
                Dashboard
              </NavLink>
              <NavLink to="/admin/products" className={navClass}>
                Produk
              </NavLink>
              <NavLink to="/admin/categories" className={navClass}>
                Kategori
              </NavLink>
              <NavLink to="/admin/units" className={navClass}>
                Satuan
              </NavLink>
              <NavLink to="/admin/users" className={navClass}>
                Pengguna
              </NavLink>
              <NavLink to="/profile" className={navClass}>
                Profil
              </NavLink>
            </nav>
          </div>

          <div className="flex items-center gap-3">
            <div className="hidden sm:flex flex-col text-right">
              <span className="text-sm font-semibold text-white leading-tight">{user?.name}</span>
              <span className="text-[11px] text-emerald-400 font-mono">administrator</span>
            </div>

            <button
              onClick={handleLogout}
              className="px-3 py-1.5 rounded-xl text-xs font-semibold bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 border border-rose-500/30 transition-all cursor-pointer"
            >
              Keluar
            </button>
          </div>
        </div>

        {/* Sub Navigation Bar for tablet/mobile */}
        <div className="lg:hidden border-t border-slate-800/80 px-4 py-2 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
          <NavLink to="/admin/dashboard" className={navClass}>
            Dashboard
          </NavLink>
          <NavLink to="/admin/products" className={navClass}>
            Produk
          </NavLink>
          <NavLink to="/admin/categories" className={navClass}>
            Kategori
          </NavLink>
          <NavLink to="/admin/units" className={navClass}>
            Satuan
          </NavLink>
          <NavLink to="/admin/users" className={navClass}>
            Pengguna
          </NavLink>
          <NavLink to="/profile" className={navClass}>
            Profil
          </NavLink>
        </div>
      </header>

      {/* Main Content Area */}
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children || <Outlet />}
      </main>

      {/* Footer */}
      <footer className="border-t border-slate-900 text-center py-4 text-xs text-slate-500">
        FinToko &copy; 2026 — Master Data Management • Mode: Administrator
      </footer>
    </div>
  );
}

export default AdminLayout;
