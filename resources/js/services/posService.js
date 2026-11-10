import api from './api';

export const posService = {
  async getPosProducts(params = {}) {
    const response = await api.get('/pos/products', { params });
    return response.data;
  },

  async findByBarcode(barcode) {
    const response = await api.get(`/pos/products/barcode/${encodeURIComponent(barcode)}`);
    return response.data;
  },

  async checkout(data) {
    const response = await api.post('/pos/checkout', data);
    return response.data;
  },

  async getSales(params = {}) {
    const response = await api.get('/pos/sales', { params });
    return response.data;
  },

  async getSale(id) {
    const response = await api.get(`/pos/sales/${id}`);
    return response.data;
  },

  async getReceipt(id) {
    const response = await api.get(`/pos/sales/${id}/receipt`);
    return response.data;
  },

  async cancelSale(id, data = {}) {
    const response = await api.post(`/pos/sales/${id}/cancel`, data);
    return response.data;
  },

  async getPaymentMethods() {
    const response = await api.get('/payment-methods', { params: { active_only: 1 } });
    return response.data;
  },
};

export default posService;

