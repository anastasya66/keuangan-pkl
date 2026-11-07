import api from './api';

export const inventoryValuationService = {
  async getValuation(params = {}) {
    const response = await api.get('/inventory/valuation', { params });
    return response.data;
  },

  getExportExcelUrl(params = {}) {
    const query = new URLSearchParams(params).toString();
    const base = import.meta.env.VITE_API_BASE_URL || '/api/v1';
    return `${base}/inventory/valuation/export/excel${query ? `?${query}` : ''}`;
  },

  getExportPdfUrl(params = {}) {
    const query = new URLSearchParams(params).toString();
    const base = import.meta.env.VITE_API_BASE_URL || '/api/v1';
    return `${base}/inventory/valuation/export/pdf${query ? `?${query}` : ''}`;
  },
};

export default inventoryValuationService;
