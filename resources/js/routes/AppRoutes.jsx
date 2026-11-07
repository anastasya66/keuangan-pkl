import { Navigate, Route, Routes } from 'react-router-dom';
import GuestRoute from '../components/auth/GuestRoute';
import ProtectedRoute from '../components/auth/ProtectedRoute';
import useAuth from '../hooks/useAuth';
import AdminLayout from '../layouts/AdminLayout';
import StaffLayout from '../layouts/StaffLayout';
import ChartOfAccountsPage from '../pages/accounting/ChartOfAccountsPage';
import GeneralJournalPage from '../pages/accounting/GeneralJournalPage';
import GeneralLedgerPage from '../pages/accounting/GeneralLedgerPage';
import JournalDetailPage from '../pages/accounting/JournalDetailPage';
import TrialBalancePage from '../pages/accounting/TrialBalancePage';
import AdminDashboardPage from '../pages/admin/AdminDashboardPage';
import UserManagementPage from '../pages/admin/UserManagementPage';
import LoginPage from '../pages/auth/LoginPage';
import CategoryPage from '../pages/categories/CategoryPage';
import NotFoundPage from '../pages/NotFoundPage';
import ProductPage from '../pages/products/ProductPage';
import ProfilePage from '../pages/profile/ProfilePage';
import BalanceSheetPage from '../pages/reports/BalanceSheetPage';
import IncomeStatementPage from '../pages/reports/IncomeStatementPage';
import InventoryReportPage from '../pages/reports/InventoryReportPage';
import StockMovementReportPage from '../pages/reports/StockMovementReportPage';
import StaffDashboardPage from '../pages/staff/StaffDashboardPage';
import UnitPage from '../pages/units/UnitPage';
import PosPage from '../pages/pos/PosPage';
import SalesHistoryPage from '../pages/pos/SalesHistoryPage';
import SupplierPage from '../pages/suppliers/SupplierPage';
import PurchasePage from '../pages/purchases/PurchasePage';
import PurchaseDetailPage from '../pages/purchases/PurchaseDetailPage';
import StockCardPage from '../pages/inventory/StockCardPage';
import InventoryValuationPage from '../pages/inventory/InventoryValuationPage';
import SettingsPage from '../pages/settings/SettingsPage';

// Wrapper for Profile page to render with the user's role layout
function ProfileWrapper() {
  const { user } = useAuth();
  if (user?.role === 'admin') {
    return (
      <AdminLayout>
        <ProfilePage />
      </AdminLayout>
    );
  }
  return (
    <StaffLayout>
      <ProfilePage />
    </StaffLayout>
  );
}

export function AppRoutes() {
  const { isAuthenticated, user } = useAuth();

  return (
    <Routes>
      {/* Root redirect based on role */}
      <Route
        path="/"
        element={
          !isAuthenticated ? (
            <Navigate to="/login" replace />
          ) : user?.role === 'admin' ? (
            <Navigate to="/admin/dashboard" replace />
          ) : (
            <Navigate to="/staff/dashboard" replace />
          )
        }
      />

      {/* Guest Route: Login */}
      <Route
        path="/login"
        element={
          <GuestRoute>
            <LoginPage />
          </GuestRoute>
        }
      />

      {/* Admin Protected Routes */}
      <Route
        path="/admin"
        element={
          <ProtectedRoute allowedRoles={['admin']}>
            <AdminLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/admin/dashboard" replace />} />
        <Route path="dashboard" element={<AdminDashboardPage />} />
        <Route path="products" element={<ProductPage />} />
        <Route path="categories" element={<CategoryPage />} />
        <Route path="units" element={<UnitPage />} />
        <Route path="users" element={<UserManagementPage />} />
        <Route path="settings" element={<SettingsPage />} />
        {/* POS & Kasir Routes (Admin) */}
        <Route path="pos" element={<PosPage />} />
        <Route path="pos/sales" element={<SalesHistoryPage />} />
        {/* Purchases & Suppliers (Admin) */}
        <Route path="suppliers" element={<SupplierPage />} />
        <Route path="purchases" element={<PurchasePage />} />
        <Route path="purchases/:id" element={<PurchaseDetailPage />} />
        {/* Inventory Stock Card & Valuation (Admin) */}
        <Route path="inventory/stock-card" element={<StockCardPage />} />
        <Route path="inventory/valuation" element={<InventoryValuationPage />} />
        {/* Accounting Routes (Admin) */}
        <Route path="accounting/accounts" element={<ChartOfAccountsPage />} />
        <Route path="accounting/journals" element={<GeneralJournalPage />} />
        <Route path="accounting/journals/:id" element={<JournalDetailPage />} />
        <Route path="accounting/ledger" element={<GeneralLedgerPage />} />
        <Route path="accounting/trial-balance" element={<TrialBalancePage />} />
        {/* Reports Routes (Admin) */}
        <Route path="reports/income-statement" element={<IncomeStatementPage />} />
        <Route path="reports/balance-sheet" element={<BalanceSheetPage />} />
        <Route path="reports/inventory" element={<InventoryReportPage />} />
        <Route path="reports/stock-movements" element={<StockMovementReportPage />} />
      </Route>

      {/* Staff Protected Routes */}
      <Route
        path="/staff"
        element={
          <ProtectedRoute allowedRoles={['staff']}>
            <StaffLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/staff/dashboard" replace />} />
        <Route path="dashboard" element={<StaffDashboardPage />} />
        <Route path="products" element={<ProductPage />} />
        <Route path="categories" element={<CategoryPage />} />
        <Route path="units" element={<UnitPage />} />
        {/* POS & Kasir Routes (Staff) */}
        <Route path="pos" element={<PosPage />} />
        <Route path="pos/sales" element={<SalesHistoryPage />} />
        {/* Inventory Stock Card (Staff) */}
        <Route path="inventory/stock-card" element={<StockCardPage />} />
        {/* Accounting Routes (Staff) */}
        <Route path="accounting/accounts" element={<ChartOfAccountsPage />} />
        <Route path="accounting/journals" element={<GeneralJournalPage />} />
        <Route path="accounting/journals/:id" element={<JournalDetailPage />} />
        <Route path="accounting/ledger" element={<GeneralLedgerPage />} />
        <Route path="accounting/trial-balance" element={<TrialBalancePage />} />
        {/* Reports Routes (Staff) */}
        <Route path="reports/income-statement" element={<IncomeStatementPage />} />
        <Route path="reports/balance-sheet" element={<BalanceSheetPage />} />
        <Route path="reports/inventory" element={<InventoryReportPage />} />
        <Route path="reports/stock-movements" element={<StockMovementReportPage />} />
      </Route>

      {/* Profile Route (Shared by both roles) */}
      <Route
        path="/profile"
        element={
          <ProtectedRoute allowedRoles={['admin', 'staff']}>
            <ProfileWrapper />
          </ProtectedRoute>
        }
      />

      {/* 404 Catch All */}
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}

export default AppRoutes;
