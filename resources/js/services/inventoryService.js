import api from './api';

export const inventoryService = {
  /**
   * Catat penyesuaian stok fisik (Stock Opname / Input Stok Masuk atau Keluar).
   */
  async recordAdjustment(data) {
    const response = await api.post('/inventory/adjustments', data);
    return response.data;
  },

  /**
   * Ambil daftar inventaris/stok barang.
   */
  async getInventory(params = {}) {
    const response = await api.get('/inventory', { params });
    return response.data;
  },

  /**
   * Ambil riwayat pergerakan stok (mutasi).
   */
  async getMovements(params = {}) {
    const response = await api.get('/inventory/movements', { params });
    return response.data;
  },
};

export default inventoryService;

