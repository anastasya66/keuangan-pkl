import api from './api';
import { downloadBlobFile, downloadBlob, downloadFromApi } from '../utils/downloadHelper';

export { downloadBlobFile, downloadBlob, downloadFromApi };

export const reportService = {
  // === DASHBOARD SERVICES ===
  getDashboardOverview: async (params = {}) => {
    const response = await api.get('/dashboard', { params });
    return response.data;
  },

  getFinancialSummary: async (params = {}) => {
    const response = await api.get('/dashboard/financial-summary', { params });
    return response.data;
  },

  getFinancialChart: async (params = {}) => {
    const response = await api.get('/dashboard/financial-chart', { params });
    return response.data;
  },

  getExpenseDistribution: async (params = {}) => {
    const response = await api.get('/dashboard/expense-distribution', { params });
    return response.data;
  },

  getRecentActivities: async (limit = 10) => {
    const response = await api.get('/dashboard/recent-activities', { params: { limit } });
    return response.data;
  },

  getInventorySummary: async () => {
    const response = await api.get('/dashboard/inventory-summary');
    return response.data;
  },

  getLowStock: async (limit = 6) => {
    const response = await api.get('/dashboard/low-stock', { params: { limit } });
    return response.data;
  },

  // === LAPORAN PENJUALAN (SALES & PROFIT REPORT) ===
  getSalesReport: async (params = {}) => {
    const response = await api.get('/reports/sales', { params });
    return response.data;
  },

  downloadSalesReportPdf: async (params = {}) => {
    const mode = params.mode || 'daily';
    return await downloadFromApi('/reports/sales/export/pdf', params, `laporan-penjualan-${mode}.pdf`);
  },

  downloadSalesReportExcel: async (params = {}) => {
    const mode = params.mode || 'daily';
    return await downloadFromApi('/reports/sales/export/excel', params, `laporan-penjualan-${mode}.xlsx`);
  },

  // === LAPORAN LABA RUGI (INCOME STATEMENT) ===
  getIncomeStatement: async (params = {}) => {
    const response = await api.get('/reports/income-statement', { params });
    return response.data;
  },

  downloadIncomeStatementPdf: async (params = {}) => {
    return await downloadFromApi('/reports/income-statement/export/pdf', params, 'laporan-laba-rugi.pdf');
  },

  downloadIncomeStatementExcel: async (params = {}) => {
    return await downloadFromApi('/reports/income-statement/export/excel', params, 'laporan-laba-rugi.xlsx');
  },

  // === LAPORAN NERACA (BALANCE SHEET) ===
  getBalanceSheet: async (params = {}) => {
    const response = await api.get('/reports/balance-sheet', { params });
    return response.data;
  },

  downloadBalanceSheetPdf: async (params = {}) => {
    return await downloadFromApi('/reports/balance-sheet/export/pdf', params, 'laporan-neraca.pdf');
  },

  downloadBalanceSheetExcel: async (params = {}) => {
    return await downloadFromApi('/reports/balance-sheet/export/excel', params, 'laporan-neraca.xlsx');
  },

  // === LAPORAN STOK & VALUASI (INVENTORY REPORT) ===
  getInventoryReport: async (params = {}) => {
    const response = await api.get('/reports/inventory', { params });
    return response.data;
  },

  downloadInventoryReportPdf: async (params = {}) => {
    return await downloadFromApi('/reports/inventory/export/pdf', params, 'laporan-stok-persediaan.pdf');
  },

  downloadInventoryReportExcel: async (params = {}) => {
    return await downloadFromApi('/reports/inventory/export/excel', params, 'laporan-stok-persediaan.xlsx');
  },

  // === LAPORAN MUTASI STOK (STOCK MOVEMENTS) ===
  getStockMovements: async (params = {}) => {
    const response = await api.get('/reports/stock-movements', { params });
    return response.data;
  },

  downloadStockMovementsPdf: async (params = {}) => {
    return await downloadFromApi('/reports/stock-movements/export/pdf', params, 'laporan-mutasi-persediaan.pdf');
  },

  downloadStockMovementsExcel: async (params = {}) => {
    return await downloadFromApi('/reports/stock-movements/export/excel', params, 'laporan-mutasi-persediaan.xlsx');
  },
};

export default reportService;
