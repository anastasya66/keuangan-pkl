import api from './api';
import { downloadFromApi } from '../utils/downloadHelper';

export const inventoryValuationService = {
  async getValuation(params = {}) {
    const response = await api.get('/inventory/valuation', { params });
    return response.data;
  },

  /**
   * Unduh data Nilai Persediaan dalam format PDF via Axios Blob dengan autentikasi Sanctum.
   */
  async downloadValuationPdf(params = {}) {
    return await downloadFromApi('/inventory/valuation/export/pdf', params, 'nilai-persediaan.pdf');
  },

  /**
   * Unduh data Nilai Persediaan dalam format Excel (.xlsx) via Axios Blob dengan autentikasi Sanctum.
   */
  async downloadValuationExcel(params = {}) {
    return await downloadFromApi('/inventory/valuation/export/excel', params, 'nilai-persediaan.xlsx');
  },

  /**
   * Backward-compatibility URL helpers.
   */
  getExportExcelUrl(params = {}) {
    const query = new URLSearchParams(params).toString();
    return `/api/v1/inventory/valuation/export/excel${query ? `?${query}` : ''}`;
  },

  getExportPdfUrl(params = {}) {
    const query = new URLSearchParams(params).toString();
    return `/api/v1/inventory/valuation/export/pdf${query ? `?${query}` : ''}`;
  },
};

export default inventoryValuationService;
