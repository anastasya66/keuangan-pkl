import React, { useState } from 'react';
import { Link, NavLink, Outlet, useNavigate, useLocation } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import {
  IconDashboard,
  IconBox,
  IconTag,
  IconRuler,
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
  IconStockCard,
  IconValuation,
} from '../components/common/Icons';

export function StaffLayout({ children }) {
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
        { to: '/staff/dashboard', label: 'Dashboard Toko', icon: IconDashboard },
        { to: '/staff/pos', label: 'Penjualan', icon: IconPos },
      ],
    },
    {
      group: 'PRODUK & STOK BARANG',
      items: [
        { to: '/staff/products', label: 'Katalog Barang', icon: IconBox },
        { to: '/staff/inventory/valuation', label: 'Nilai Persediaan', icon: IconValuation },
        { to: '/staff/categories', label: 'Kategori Barang', icon: IconTag },
        { to: '/staff/units', label: 'Satuan Ukuran', icon: IconRuler },
        { to: '/staff/inventory/stock-card', label: 'Kartu Stok', icon: IconStockCard },
        { to: '/staff/reports/inventory', label: 'Sisa Stok Toko', icon: IconStock },
        { to: '/staff/reports/stock-movements', label: 'Riwayat Keluar Masuk', icon: IconMovement },
      ],
    },
    {
      group: 'CATATAN KEUANGAN',
      items: [
        { to: '/staff/accounting/journals', label: 'Catatan Transaksi (Jurnal)', icon: IconJournal },
        { to: '/staff/accounting/ledger', label: 'Buku Kas & Rekening', icon: IconLedger },
        { to: '/staff/accounting/accounts', label: 'Daftar Rekening Akun', icon: IconAccounts },
        { to: '/staff/accounting/trial-balance', label: 'Cek Keseimbangan Saldo', icon: IconBalance },
      ],
    },
    {
      group: 'LAPORAN TOKO',
      items: [
        { to: '/staff/reports/income-statement', label: 'Laporan Untung & Rugi', icon: IconIncomeStatement },
        { to: '/staff/reports/balance-sheet', label: 'Posisi Keuangan (Neraca)', icon: IconBalanceSheet },
      ],
    },
    {
      group: 'PENGATURAN',
      items: [
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
        <div>
          <div className="h-16 px-4 flex items-center justify-between border-b border-[#D7EBEF]">
            <Link to="/staff/dashboard" className="flex items-center gap-3 overflow-hidden group">
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
                    Staff Toko
                  </span>
                </div>
              )}
            </Link>

            <button
              onClick={() => setIsMobileMenuOpen(false)}
              className="lg:hidden p-1.5 rounded-lg text-[#577782] hover:bg-[#F3FBFC] btn-press"
            >
              ✕
            </button>
          </div>

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

        <div className="p-3 border-t border-[#D7EBEF] bg-[#F8FDFF]">
          {(isSidebarOpen || isMobileMenuOpen) ? (
            <div className="p-2.5 rounded-2xl bg-white border border-[#D7EBEF] shadow-2xs flex items-center justify-between">
              <div className="flex items-center gap-2.5 overflow-hidden">
                <div className="w-8 h-8 rounded-full bg-[#E3F6FA] text-[#167A93] border border-[#38BFE0]/30 flex items-center justify-center font-bold text-xs shrink-0">
                  {user?.name?.charAt(0) || 'S'}
                </div>
                <div className="overflow-hidden">
                  <div className="text-xs font-bold text-[#10262E] truncate">{user?.name}</div>
                  <div className="text-[10px] text-[#577782] truncate">Staff Toko</div>
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
        <header className="bg-white/85 backdrop-blur-md border-b border-[#D7EBEF] sticky top-0 z-30 h-16">
          <div className="max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => setIsMobileMenuOpen(true)}
                className="lg:hidden p-2 rounded-xl text-[#577782] hover:bg-[#F3FBFC] border border-[#D7EBEF] cursor-pointer btn-press"
              >
                ☰
              </button>

              <button
                type="button"
                onClick={() => setIsSidebarOpen(!isSidebarOpen)}
                className="hidden lg:flex p-2 rounded-xl text-[#577782] hover:bg-[#F3FBFC] border border-[#D7EBEF] cursor-pointer btn-press"
              >
                {isSidebarOpen ? '◀' : '▶'}
              </button>

              <div className="flex items-center gap-1.5 sm:gap-2 text-xs truncate max-w-[150px] xs:max-w-[200px] sm:max-w-none">
                <img src="/images/fintoko-mark.png" alt="FinToko" className="w-5 h-5 object-contain shrink-0" />
                <span className="font-black text-[#10262E] tracking-tight">FinToko</span>
                <span className="hidden sm:inline text-[#8EA6AF]">&bull;</span>
                <span className="hidden sm:inline font-semibold text-[#577782]">Portal Staf Toko</span>
              </div>
            </div>

            <div className="flex items-center gap-2 sm:gap-3">
              <div className="hidden sm:flex items-center px-3.5 py-1.5 rounded-full bg-[#F3FBFC] border border-[#D7EBEF] text-xs font-semibold text-[#577782]">
                <span>{todayFormatted}</span>
              </div>

              <div className="relative">
                <button
                  type="button"
                  onClick={() => setIsUserDropdownOpen(!isUserDropdownOpen)}
                  className="flex items-center gap-2.5 p-1 sm:px-3 sm:py-1.5 rounded-full hover:bg-[#F3FBFC] border border-transparent hover:border-[#D7EBEF] transition-all cursor-pointer btn-press"
                >
                  <div className="w-8 h-8 rounded-full bg-gradient-to-tr from-[#27C5E5] to-[#38BFE0] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                    {user?.name?.charAt(0) || 'S'}
                  </div>
                  <div className="hidden md:flex flex-col text-left">
                    <span className="text-xs font-bold text-[#10262E] leading-tight">
                      {user?.name}
                    </span>
                    <span className="text-[10px] text-[#167A93] font-semibold">Staff Toko</span>
                  </div>
                  <span className="text-xs text-[#8EA6AF]">▾</span>
                </button>

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

        <footer className="border-t border-[#D7EBEF] bg-white/50 text-center py-4 text-xs text-[#8EA6AF]">
          FinToko &copy; 2026 &bull; Aplikasi Pengelolaan Keuangan &amp; Stok Toko
        </footer>
      </div>
    </div>
  );
}

export default StaffLayout;
