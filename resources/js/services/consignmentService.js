import api from './api';
import { downloadFromApi } from '../utils/downloadHelper';

export const consignmentService = {
  // Ringkasan 4 Kartu Statistik
  getSummary: async (params = {}) => {
    const response = await api.get('/consignments/summary', { params });
    return response.data;
  },

  // Tab 1: Penjualan & Bagi Hasil
  getSales: async (params = {}) => {
    const response = await api.get('/consignments/sales', { params });
    return response.data;
  },

  // Tab 2: Stok Barang Konsinyasi
  getProducts: async (params = {}) => {
    const response = await api.get('/consignments/products', { params });
    return response.data;
  },

  // Tab 3: Data Konsinyor & Rekap Tagihan
  getConsignors: async (params = {}) => {
    const response = await api.get('/consignments/consignors', { params });
    return response.data;
  },

  // Dropdown list mitra aktif
  getActiveConsignors: async () => {
    const response = await api.get('/consignments/consignors/active');
    return response.data;
  },

  // CRUD Mitra Konsinyor
  createConsignor: async (data) => {
    const response = await api.post('/consignments/consignors', data);
    return response.data;
  },

  updateConsignor: async (id, data) => {
    const response = await api.put(`/consignments/consignors/${id}`, data);
    return response.data;
  },

  deleteConsignor: async (id) => {
    const response = await api.delete(`/consignments/consignors/${id}`);
    return response.data;
  },

  // CRUD Produk Konsinyasi
  createProduct: async (data) => {
    const response = await api.post('/consignments/products', data);
    return response.data;
  },

  updateProduct: async (id, data) => {
    const response = await api.put(`/consignments/products/${id}`, data);
    return response.data;
  },

  // Penyesuaian Stok (Tambah stok titipan atau retur sisa barang)
  adjustStock: async (productId, data) => {
    const response = await api.post(`/consignments/products/${productId}/adjust-stock`, data);
    return response.data;
  },

  // Settle / Penyetoran Tagihan ke Mitra
  settle: async (data) => {
    const response = await api.post('/consignments/settle', data);
    return response.data;
  },

  // Export PDF & Excel
  downloadPdf: async (params = {}) => {
    return await downloadFromApi('/consignments/export/pdf', params, `laporan-konsinyasi-${new Date().toISOString().slice(0, 10)}.pdf`);
  },

  downloadExcel: async (params = {}) => {
    return await downloadFromApi('/consignments/export/excel', params, `laporan-konsinyasi-${new Date().toISOString().slice(0, 10)}.xlsx`);
  },
};

