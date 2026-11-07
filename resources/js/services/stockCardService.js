import api from './api';

export const stockCardService = {
  async getStockCard(productId, params = {}) {
    const response = await api.get(`/inventory/stock-card/${productId}`, { params });
    return response.data;
  },
};

export default stockCardService;

