import api from './api';

export const purchaseService = {
  async getPurchases(params = {}) {
    const response = await api.get('/purchases', { params });
    return response.data;
  },

  async getPurchase(id) {
    const response = await api.get(`/purchases/${id}`);
    return response.data;
  },

  async createPurchase(data) {
    const response = await api.post('/purchases', data);
    return response.data;
  },

  async receivePurchase(id) {
    const response = await api.post(`/purchases/${id}/receive`);
    return response.data;
  },

  async cancelPurchase(id, data = {}) {
    const response = await api.post(`/purchases/${id}/cancel`, data);
    return response.data;
  },

  async payDebt(id, data) {
    const response = await api.post(`/purchases/${id}/pay-debt`, data);
    return response.data;
  },
};

export default purchaseService;

