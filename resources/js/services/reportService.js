import api from './api';

/**
 * Helper untuk mengunduh file blob (PDF / Excel) dari API response.
 */
function downloadBlob(response, defaultFilename = 'download') {
  const blob = new Blob([response.data], {
    type: response.headers['content-type'] || 'application/octet-stream',
  });
  
  // Ekstrak nama file dari header Content-Disposition jika tersedia
  let filename = defaultFilename;
  const disposition = response.headers['content-disposition'];
  if (disposition && disposition.indexOf('filename=') !== -1) {
    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
    if (matches != null && matches[1]) {
      filename = matches[1].replace(/['"]/g, '');
    }
  }

  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}

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

  // === LAPORAN LABA RUGI (INCOME STATEMENT) ===
  getIncomeStatement: async (params = {}) => {
    const response = await api.get('/reports/income-statement', { params });
    return response.data;
  },

  downloadIncomeStatementPdf: async (params = {}) => {
    const response = await api.get('/reports/income-statement/export/pdf', {
      params,
      responseType: 'blob',
    });
    downloadBlob(response, 'laporan-laba-rugi.pdf');
  },

  downloadIncomeStatementExcel: async (params = {}) => {
    const response = await api.get('/reports/income-statement/export/excel', {
      params,
      responseType: 'blob',
    });
    downloadBlob(response, 'laporan-laba-rugi.xls');
  },

  // === LAPORAN NERACA (BALANCE SHEET) ===
  getBalanceSheet: async (params = {}) => {
    const response = await api.get('/reports/balance-sheet', { params });
    return response.data;
  },

  downloadBalanceSheetPdf: async (params = {}) => {
    const response = await api.get('/reports/balance-sheet/export/pdf', {
      params,
      responseType: 'blob',
    });
    downloadBlob(response, 'laporan-neraca.pdf');
  },

  downloadBalanceSheetExcel: async (params = {}) => {
    const response = await api.get('/reports/balance-sheet/export/excel', {
      params,
      responseType: 'blob',
    });
    downloadBlob(response, 'laporan-neraca.xls');
  },

  // === LAPORAN STOK & VALUASI (INVENTORY REPORT) ===
  getInventoryReport: async (params = {}) => {
    const response = await api.get('/reports/inventory', { params });
    return response.data;
  },

  downloadInventoryReportPdf: async (params = {}) => {
    const response = await api.get('/reports/inventory/export/pdf', {
      params,
      responseType: 'blob',
    });
    downloadBlob(response, 'laporan-stok-persediaan.pdf');
  },

  downloadInventoryReportExcel: async (params = {}) => {
    const response = await api.get('/reports/inventory/export/excel', {
      params,
      responseType: 'blob',
    });
    downloadBlob(response, 'laporan-stok-persediaan.xls');
  },

  // === LAPORAN MUTASI STOK (STOCK MOVEMENTS) ===
  getStockMovements: async (params = {}) => {
    const response = await api.get('/reports/stock-movements', { params });
    return response.data;
  },

  downloadStockMovementsPdf: async (params = {}) => {
    const response = await api.get('/reports/stock-movements/export/pdf', {
      params,
      responseType: 'blob',
    });
    downloadBlob(response, 'laporan-mutasi-persediaan.pdf');
  },

  downloadStockMovementsExcel: async (params = {}) => {
    const response = await api.get('/reports/stock-movements/export/excel', {
      params,
      responseType: 'blob',
    });
    downloadBlob(response, 'laporan-mutasi-persediaan.xls');
  },
};

export default reportService;

