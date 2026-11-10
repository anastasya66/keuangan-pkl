import React, { useState } from 'react';
import { Link, NavLink, Outlet, useNavigate, useLocation } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import {
  LayoutDashboard,
  ShoppingCart,
  Package,
  Tag,
  Ruler,
  Layers,
  ClipboardList,
  Coins,
  ArrowLeftRight,
  User,
  LogOut,
  Menu,
  X,
  Bell,
  Mail,
  ChevronDown,
  PanelLeftClose,
  PanelLeft,
} from 'lucide-react';
import SearchPill from '../components/ui/SearchPill';
import ErrorBoundary from '../components/common/ErrorBoundary';

export function StaffLayout({ children }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isUserDropdownOpen, setIsUserDropdownOpen] = useState(false);
  const [globalSearch, setGlobalSearch] = useState('');

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const navClass = ({ isActive }) =>
    `flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 select-none group ${
      isActive
        ? 'bg-[#20282D] text-white shadow-xs font-bold'
        : 'text-[#879197] hover:text-[#20282D] hover:bg-[#EAEFF1]'
    }`;

  const navSections = [
    {
      group: 'MENU UTAMA',
      items: [
        { to: '/staff/dashboard', label: 'Dashboard Staf', icon: LayoutDashboard },
        { to: '/staff/pos', label: 'Penjualan (Kasir POS)', icon: ShoppingCart },
      ],
    },
    {
      group: 'PRODUK & INVENTARIS',
      items: [
        { to: '/staff/products', label: 'Katalog Produk', icon: Package },
        { to: '/staff/categories', label: 'Kategori Produk', icon: Tag },
        { to: '/staff/units', label: 'Satuan Ukuran', icon: Ruler },
        { to: '/staff/reports/inventory', label: 'Stok Barang', icon: Layers },
        { to: '/staff/inventory/stock-card', label: 'Kartu Stok', icon: ClipboardList },
        { to: '/staff/inventory/valuation', label: 'Nilai Persediaan', icon: Coins },
      ],
    },
  ];

  return (
    <div className="min-h-screen bg-[#F1F4F5] text-[#20282D] flex font-sans antialiased">
      {/* Mobile Drawer Backdrop */}
      {isMobileMenuOpen && (
        <div
          onClick={() => setIsMobileMenuOpen(false)}
          className="fixed inset-0 bg-[#20282D]/40 backdrop-blur-xs z-40 lg:hidden animate-backdrop"
        />
      )}

      {/* Sidebar Navigation */}
      <aside
        className={`fixed lg:sticky top-0 h-screen z-50 lg:z-30 bg-[#F8FAFA] border-r border-[#E7ECEE] flex flex-col justify-between transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] shadow-xl lg:shadow-none ${
          isMobileMenuOpen ? 'translate-x-0 w-72 max-w-[85vw]' : '-translate-x-full lg:translate-x-0'
        } ${isSidebarOpen ? 'lg:w-64' : 'lg:w-20'}`}
      >
        {/* Brand Header */}
        <div>
          <div className="h-16 px-4 flex items-center justify-between border-b border-[#E7ECEE] bg-[#F8FAFA]">
            <Link to="/staff/dashboard" className="flex items-center gap-2.5 overflow-hidden group">
              <img
                src="/images/fintoko-mark.png"
                alt="FinToko"
                className="w-8 h-8 object-contain shrink-0 group-hover:scale-105 transition-transform duration-200"
              />
              {(isSidebarOpen || isMobileMenuOpen) && (
                <div className="flex flex-col">
                  <span className="text-base font-black tracking-tight text-[#20282D] leading-tight">
                    FinToko
                  </span>
                  <span className="text-[10px] font-bold text-[#55BFD8] uppercase tracking-wider">
                    Staff Kasir
                  </span>
                </div>
              )}
            </Link>

            <button
              type="button"
              onClick={() => setIsSidebarOpen(!isSidebarOpen)}
              className="hidden lg:flex p-1.5 rounded-lg text-[#879197] hover:text-[#20282D] hover:bg-[#EAEFF1] transition-colors"
            >
              {isSidebarOpen ? (
                <PanelLeftClose className="w-4 h-4" />
              ) : (
                <PanelLeft className="w-4 h-4" />
              )}
            </button>

            <button
              onClick={() => setIsMobileMenuOpen(false)}
              className="lg:hidden p-1.5 rounded-lg text-[#879197] hover:text-[#20282D] hover:bg-[#EAEFF1] transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Navigation Links Scrollable */}
          <div className="p-3 space-y-5 overflow-y-auto max-h-[calc(100vh-145px)] no-scrollbar">
            {navSections.map((sec, sIdx) => (
              <div key={sIdx} className="space-y-1">
                {(isSidebarOpen || isMobileMenuOpen) && (
                  <div className="px-3 text-[10px] font-bold uppercase tracking-wider text-[#A6AFB3] mb-1">
                    {sec.group}
                  </div>
                )}
                {sec.items.map((item, iIdx) => {
                  const Icon = item.icon;
                  return (
                    <NavLink
                      key={iIdx}
                      to={item.to}
                      onClick={() => setIsMobileMenuOpen(false)}
                      className={navClass}
                      title={!isSidebarOpen ? item.label : undefined}
                    >
                      <span className="shrink-0 transition-colors">
                        <Icon className="w-4 h-4 stroke-[2]" />
                      </span>
                      {(isSidebarOpen || isMobileMenuOpen) && (
                        <span className="truncate">{item.label}</span>
                      )}
                    </NavLink>
                  );
                })}
              </div>
            ))}
          </div>
        </div>

        {/* Sidebar Footer */}
        <div className="p-3 border-t border-[#E7ECEE] bg-[#F8FAFA]">
          {(isSidebarOpen || isMobileMenuOpen) ? (
            <div className="flex items-center justify-between gap-2 p-2 rounded-xl bg-white border border-[#E7ECEE] shadow-2xs">
              <Link to="/profile" className="flex items-center gap-2.5 min-w-0 flex-1 hover:opacity-85 transition-opacity">
                <div className="w-8 h-8 rounded-full bg-[#E5F6FA] text-[#55BFD8] font-bold text-xs flex items-center justify-center shrink-0 border border-[#D5E3E7]">
                  {user?.name ? user.name.charAt(0).toUpperCase() : 'S'}
                </div>
                <div className="min-w-0 flex-1">
                  <div className="text-xs font-bold text-[#20282D] truncate">
                    {user?.name || 'Staff Toko'}
                  </div>
                  <div className="text-[10px] font-semibold text-[#879197] truncate">
                    {user?.email || 'staff@fintoko.com'}
                  </div>
                </div>
              </Link>
              <button
                type="button"
                onClick={handleLogout}
                className="p-1.5 rounded-lg text-[#879197] hover:text-[#E47777] hover:bg-[#FDF3F3] transition-colors"
                title="Keluar"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          ) : (
            <div className="flex flex-col items-center gap-2">
              <Link to="/profile" className="w-8 h-8 rounded-full bg-[#E5F6FA] text-[#55BFD8] font-bold text-xs flex items-center justify-center border border-[#D5E3E7]">
                {user?.name ? user.name.charAt(0).toUpperCase() : 'S'}
              </Link>
              <button
                type="button"
                onClick={handleLogout}
                className="p-2 rounded-xl text-[#879197] hover:text-[#E47777] hover:bg-[#FDF3F3] transition-colors"
                title="Keluar"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          )}
        </div>
      </aside>

      {/* Main Container Area */}
      <div className="flex-1 flex flex-col min-w-0 min-h-screen">
        {/* Topbar Minimalist */}
        <header className="h-16 bg-white border-b border-[#E7ECEE] sticky top-0 z-20 flex items-center px-4 sm:px-6 justify-between gap-4">
          <div className="flex items-center gap-3 flex-1 max-w-md">
            <button
              type="button"
              onClick={() => setIsMobileMenuOpen(true)}
              className="lg:hidden p-2 rounded-xl text-[#879197] hover:text-[#20282D] hover:bg-[#F1F4F5] border border-[#E7ECEE]"
            >
              <Menu className="w-5 h-5" />
            </button>

            <SearchPill
              value={globalSearch}
              onChange={(e) => setGlobalSearch(e.target.value)}
              placeholder="Search here..."
              className="w-full max-w-xs sm:max-w-sm"
            />
          </div>

          <div className="flex items-center gap-2 sm:gap-3">
            <button
              type="button"
              className="w-9 h-9 rounded-full bg-[#F1F4F5] hover:bg-[#EAEFF1] text-[#879197] hover:text-[#20282D] flex items-center justify-center transition-colors relative"
            >
              <Mail className="w-4 h-4" />
            </button>

            <button
              type="button"
              className="w-9 h-9 rounded-full bg-[#F1F4F5] hover:bg-[#EAEFF1] text-[#879197] hover:text-[#20282D] flex items-center justify-center transition-colors relative"
            >
              <Bell className="w-4 h-4" />
            </button>

            <div className="relative">
              <button
                type="button"
                onClick={() => setIsUserDropdownOpen(!isUserDropdownOpen)}
                className="flex items-center gap-2 p-1 pl-1.5 pr-2 rounded-full hover:bg-[#F1F4F5] transition-colors border border-transparent hover:border-[#E7ECEE]"
              >
                <div className="w-8 h-8 rounded-full bg-[#E5F6FA] text-[#55BFD8] font-bold text-xs flex items-center justify-center border border-[#D5E3E7] shadow-2xs">
                  {user?.name ? user.name.charAt(0).toUpperCase() : 'S'}
                </div>
                <span className="hidden sm:inline text-xs font-bold text-[#20282D]">
                  {user?.name?.split(' ')[0] || 'Staff'}
                </span>
                <ChevronDown className="w-3.5 h-3.5 text-[#879197]" />
              </button>

              {isUserDropdownOpen && (
                <div className="absolute right-0 mt-2 w-52 bg-white rounded-2xl border border-[#E7ECEE] shadow-lg p-2 space-y-1 animate-dropdown-pop z-50">
                  <div className="px-3 py-2 border-b border-[#F1F4F5]">
                    <div className="text-xs font-bold text-[#20282D] truncate">
                      {user?.name}
                    </div>
                    <div className="text-[11px] text-[#879197] truncate">
                      {user?.email}
                    </div>
                  </div>

                  <Link
                    to="/profile"
                    onClick={() => setIsUserDropdownOpen(false)}
                    className="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-[#879197] hover:text-[#20282D] hover:bg-[#F1F4F5] transition-colors"
                  >
                    <User className="w-4 h-4" />
                    <span>Profil Pengguna</span>
                  </Link>

                  <button
                    type="button"
                    onClick={handleLogout}
                    className="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-[#E47777] hover:bg-[#FDF3F3] transition-colors text-left"
                  >
                    <LogOut className="w-4 h-4" />
                    <span>Keluar Sistem</span>
                  </button>
                </div>
              )}
            </div>
          </div>
        </header>

        <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-6">
          <div key={location.pathname} className="animate-page-enter">
            <ErrorBoundary>
              {children || <Outlet />}
            </ErrorBoundary>
          </div>
        </main>

        <footer className="border-t border-[#E7ECEE] bg-white/60 text-center py-4 text-xs font-medium text-[#A6AFB3]">
          FinToko &copy; 2026 &bull; Modern Inventory &amp; Financial Management System
        </footer>
      </div>
    </div>
  );
}

export default StaffLayout;
