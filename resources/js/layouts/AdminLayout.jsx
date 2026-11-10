import React, { useState } from 'react';
import { Link, NavLink, Outlet, useNavigate, useLocation } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import {
  IconDashboard,
  IconBox,
  IconTag,
  IconRuler,
  IconUsers,
  IconJournal,
  IconLedger,
  IconAccounts,
  IconBalance,
  IconIncomeStatement,
  IconBalanceSheet,
  IconStock,
  IconMovement,
  IconUser,
  IconLogout,
  IconPos,
  IconPurchase,
  IconSupplier,
  IconStockCard,
  IconValuation,
  IconSettings,
} from '../components/common/Icons';

export function AdminLayout({ children }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isUserDropdownOpen, setIsUserDropdownOpen] = useState(false);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  // Format today's date in Indonesian
  const todayFormatted = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date());

  const navClass = ({ isActive }) =>
    `flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 active:scale-[0.97] group ${
      isActive
        ? 'bg-[#E3F6FA] text-[#167A93] border border-[#38BFE0]/30 shadow-xs'
        : 'text-[#577782] hover:text-[#16323B] hover:bg-[#F3FBFC] hover:translate-x-1'
    }`;

  const navItems = [
    {
      group: 'MENU UTAMA',
      items: [
        { to: '/admin/dashboard', label: 'Dashboard Toko', icon: IconDashboard },
        { to: '/admin/pos', label: 'Penjualan', icon: IconPos },
      ],
    },
    {
      group: 'PEMBELIAN & SUPPLIER',
      items: [
        { to: '/admin/purchases', label: 'Pembelian', icon: IconPurchase },
        { to: '/admin/suppliers', label: 'Data Supplier', icon: IconSupplier },
      ],
    },
    {
      group: 'PRODUK & STOK BARANG',
      items: [
        { to: '/admin/products', label: 'Katalog Barang', icon: IconBox },
        { to: '/admin/inventory/valuation', label: 'Nilai Persediaan', icon: IconValuation },
        { to: '/admin/categories', label: 'Kategori Barang', icon: IconTag },
        { to: '/admin/units', label: 'Satuan Ukuran', icon: IconRuler },
        { to: '/admin/inventory/stock-card', label: 'Kartu Stok', icon: IconStockCard },
        { to: '/admin/reports/inventory', label: 'Sisa Stok Toko', icon: IconStock },
        { to: '/admin/reports/stock-movements', label: 'Riwayat Keluar Masuk', icon: IconMovement },
      ],
    },
    {
      group: 'CATATAN KEUANGAN',
      items: [
        { to: '/admin/accounting/journals', label: 'Catatan Transaksi (Jurnal)', icon: IconJournal },
        { to: '/admin/accounting/ledger', label: 'Buku Kas & Rekening', icon: IconLedger },
        { to: '/admin/accounting/accounts', label: 'Daftar Rekening Akun', icon: IconAccounts },
        { to: '/admin/accounting/trial-balance', label: 'Cek Keseimbangan Saldo', icon: IconBalance },
      ],
    },
    {
      group: 'LAPORAN RESMI',
      items: [
        { to: '/admin/reports/income-statement', label: 'Laporan Untung & Rugi', icon: IconIncomeStatement },
        { to: '/admin/reports/balance-sheet', label: 'Posisi Keuangan (Neraca)', icon: IconBalanceSheet },
      ],
    },
    {
      group: 'TIM & PENGATURAN',
      items: [
        { to: '/admin/users', label: 'Kelola Tim Toko', icon: IconUsers },
        { to: '/admin/settings', label: 'Pengaturan Sistem', icon: IconSettings },
        { to: '/profile', label: 'Profil Saya', icon: IconUser },
      ],
    },
  ];

  return (
    <div className="min-h-screen bg-[#EAF8FA] text-[#16323B] flex font-sans antialiased">
      {/* Mobile Drawer Backdrop with smooth blur */}
      {isMobileMenuOpen && (
        <div
          onClick={() => setIsMobileMenuOpen(false)}
          className="fixed inset-0 bg-[#10262E]/35 backdrop-blur-xs z-40 lg:hidden animate-backdrop"
        />
      )}

      {/* Sidebar Navigation */}
      <aside
        className={`fixed lg:sticky top-0 h-screen z-50 lg:z-30 bg-white border-r border-[#D7EBEF] flex flex-col justify-between transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] shadow-2xl lg:shadow-xs ${
          isMobileMenuOpen ? 'translate-x-0 w-72 max-w-[85vw]' : '-translate-x-full lg:translate-x-0'
        } ${isSidebarOpen ? 'lg:w-64' : 'lg:w-20'}`}
      >
        {/* Sidebar Brand Header */}
        <div>
          <div className="h-16 px-4 flex items-center justify-between border-b border-[#D7EBEF]">
            <Link to="/admin/dashboard" className="flex items-center gap-3 overflow-hidden group">
              <img
                src="/images/fintoko-mark.png"
                alt="FinToko"
                className="w-9 h-9 object-contain shrink-0 group-hover:scale-105 transition-transform duration-200"
              />
              {(isSidebarOpen || isMobileMenuOpen) && (
                <div className="flex flex-col">
                  <span className="text-base font-black tracking-tight text-[#10262E] leading-tight">
                    FinToko
                  </span>
                  <span className="text-[10px] font-bold text-[#167A93] uppercase tracking-wider">
                    Portal Admin
                  </span>
                </div>
              )}
            </Link>

            {/* Close button for mobile */}
            <button
              onClick={() => setIsMobileMenuOpen(false)}
              className="lg:hidden p-1.5 rounded-lg text-[#577782] hover:bg-[#F3FBFC] btn-press"
            >
              ✕
            </button>
          </div>

          {/* Navigation Links Scrollable */}
          <div className="p-3 space-y-6 overflow-y-auto max-h-[calc(100vh-140px)] no-scrollbar">
            {navItems.map((sec, sIdx) => (
              <div key={sIdx} className="space-y-1">
                {(isSidebarOpen || isMobileMenuOpen) && (
                  <div className="px-3 text-[10px] font-bold uppercase tracking-wider text-[#8EA6AF] mb-1.5">
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
                      <span className="shrink-0 text-current transition-colors">
                        <Icon className="w-4 h-4" />
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

        {/* Sidebar Bottom Profile Card */}
        <div className="p-3 border-t border-[#D7EBEF] bg-[#F8FDFF]">
          {(isSidebarOpen || isMobileMenuOpen) ? (
            <div className="p-2.5 rounded-2xl bg-white border border-[#D7EBEF] shadow-2xs flex items-center justify-between">
              <div className="flex items-center gap-2.5 overflow-hidden">
                <div className="w-8 h-8 rounded-full bg-[#E3F6FA] text-[#167A93] border border-[#38BFE0]/30 flex items-center justify-center font-bold text-xs shrink-0">
                  {user?.name?.charAt(0) || 'A'}
                </div>
                <div className="overflow-hidden">
                  <div className="text-xs font-bold text-[#10262E] truncate">{user?.name}</div>
                  <div className="text-[10px] text-[#577782] truncate">Admin Toko</div>
                </div>
              </div>
              <button
                onClick={handleLogout}
                className="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-600 transition-colors cursor-pointer btn-press"
                title="Keluar"
              >
                <IconLogout className="w-4 h-4" />
              </button>
            </div>
          ) : (
            <button
              onClick={handleLogout}
              className="w-full py-2 flex items-center justify-center text-rose-500 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer btn-press"
              title="Keluar"
            >
              <IconLogout className="w-4 h-4" />
            </button>
          )}
        </div>
      </aside>

      {/* Main Content Viewport */}
      <div className="flex-1 flex flex-col min-w-0 min-h-screen">
        {/* Topbar */}
        <header className="bg-white/85 backdrop-blur-md border-b border-[#D7EBEF] sticky top-0 z-30 h-16">
          <div className="max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
            {/* Left Controls: Hamburger Mobile & Desktop Sidebar Toggle */}
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => setIsMobileMenuOpen(true)}
                className="lg:hidden p-2 rounded-xl text-[#577782] hover:bg-[#F3FBFC] border border-[#D7EBEF] cursor-pointer btn-press"
                title="Menu"
              >
                ☰
              </button>

              <button
                type="button"
                onClick={() => setIsSidebarOpen(!isSidebarOpen)}
                className="hidden lg:flex p-2 rounded-xl text-[#577782] hover:bg-[#F3FBFC] border border-[#D7EBEF] cursor-pointer btn-press"
                title={isSidebarOpen ? 'Ciutkan Sidebar' : 'Perlebar Sidebar'}
              >
                {isSidebarOpen ? '◀' : '▶'}
              </button>

              <div className="flex items-center gap-1.5 sm:gap-2 text-xs truncate max-w-[150px] xs:max-w-[200px] sm:max-w-none">
                <img src="/images/fintoko-mark.png" alt="FinToko" className="w-5 h-5 object-contain shrink-0" />
                <span className="font-black text-[#10262E] tracking-tight">FinToko</span>
                <span className="hidden sm:inline text-[#8EA6AF]">&bull;</span>
                <span className="hidden sm:inline font-semibold text-[#577782]">Sistem Keuangan &amp; Stok Toko</span>
              </div>
            </div>

            {/* Right Controls: Today's Date & User Profile Dropdown */}
            <div className="flex items-center gap-2 sm:gap-3">
              {/* Date Chip */}
              <div className="hidden sm:flex items-center px-3.5 py-1.5 rounded-full bg-[#F3FBFC] border border-[#D7EBEF] text-xs font-semibold text-[#577782]">
                <span>{todayFormatted}</span>
              </div>

              {/* User Dropdown */}
              <div className="relative">
                <button
                  type="button"
                  onClick={() => setIsUserDropdownOpen(!isUserDropdownOpen)}
                  className="flex items-center gap-2.5 p-1 sm:px-3 sm:py-1.5 rounded-full hover:bg-[#F3FBFC] border border-transparent hover:border-[#D7EBEF] transition-all cursor-pointer btn-press"
                >
                  <div className="w-8 h-8 rounded-full bg-gradient-to-tr from-[#27C5E5] to-[#38BFE0] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                    {user?.name?.charAt(0) || 'A'}
                  </div>
                  <div className="hidden md:flex flex-col text-left">
                    <span className="text-xs font-bold text-[#10262E] leading-tight">
                      {user?.name}
                    </span>
                    <span className="text-[10px] text-[#167A93] font-semibold">Admin Toko</span>
                  </div>
                  <span className="text-xs text-[#8EA6AF]">▾</span>
                </button>

                {/* Dropdown Menu with Pop Animation */}
                {isUserDropdownOpen && (
                  <div
                    onClick={() => setIsUserDropdownOpen(false)}
                    className="absolute right-0 mt-2 w-52 bg-white border border-[#D7EBEF] rounded-2xl shadow-xl p-2 z-50 animate-dropdown-pop"
                  >
                    <div className="px-3 py-2 border-b border-[#EBF5F8] mb-1">
                      <div className="text-xs font-bold text-[#10262E] truncate">{user?.name}</div>
                      <div className="text-[11px] text-[#577782] truncate">{user?.email}</div>
                    </div>

                    <Link
                      to="/profile"
                      className="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-[#577782] hover:text-[#10262E] hover:bg-[#F3FBFC] rounded-xl transition-colors btn-press"
                    >
                      <IconUser className="w-4 h-4 text-[#27C5E5]" />
                      <span>Profil Saya</span>
                    </Link>

                    <button
                      type="button"
                      onClick={handleLogout}
                      className="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-rose-500 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer mt-1 btn-press"
                    >
                      <IconLogout className="w-4 h-4 text-rose-500" />
                      <span>Keluar Sistem</span>
                    </button>
                  </div>
                )}
              </div>
            </div>
          </div>
        </header>

        {/* Content Area with Smooth Page Transition Motion */}
        <main className="flex-1 max-w-7xl w-full mx-auto px-3.5 sm:px-6 lg:px-8 py-4 sm:py-6 overflow-x-hidden">
          <div key={location.pathname} className="animate-page-enter">
            {children || <Outlet />}
          </div>
        </main>

        {/* Footer Minimalist */}
        <footer className="border-t border-[#D7EBEF] bg-white/50 text-center py-4 text-xs text-[#8EA6AF]">
          FinToko &copy; 2026 &bull; Aplikasi Pengelolaan Keuangan &amp; Stok Toko
        </footer>
      </div>
    </div>
  );
}

export default AdminLayout;
