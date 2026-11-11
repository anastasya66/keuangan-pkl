import api from './api';

/**
 * Product Management REST API Service
 */
export const productService = {
  /**
   * Fetch products with search, category_id, unit_id, is_active, min_price, max_price, and pagination.
   */
  async getProducts(params = {}) {
    const response = await api.get('/products', { params });
    return response.data;
  },

  /**
   * Fetch single product detail with category and unit.
   */
  async getProduct(id) {
    const response = await api.get(`/products/${id}`);
    return response.data;
  },

  /**
   * Dapatkan rekomendasi kode SKU berikutnya.
   */
  async getNextSku(categoryId = '') {
    const params = categoryId ? { category_id: categoryId } : {};
    const response = await api.get('/products/generate-sku', { params });
    return response.data;
  },

  /**
   * Create a new product (supports JSON or FormData with photo upload).
   */
  async createProduct(productData) {
    const isForm = productData instanceof FormData;
    const response = await api.post('/products', productData, {
      headers: isForm ? { 'Content-Type': 'multipart/form-data' } : {},
    });
    return response.data;
  },

  /**
   * Update product (supports JSON or FormData with photo upload).
   */
  async updateProduct(id, productData) {
    if (productData instanceof FormData) {
      productData.append('_method', 'PUT');
      const response = await api.post(`/products/${id}`, productData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return response.data;
    }
    const response = await api.put(`/products/${id}`, productData);
    return response.data;
  },

  /**
   * Toggle product active status.
   */
  async updateProductStatus(id, isActive) {
    const response = await api.patch(`/products/${id}/status`, { is_active: isActive });
    return response.data;
  },

  /**
   * Delete product (soft delete).
   */
  async deleteProduct(id) {
    const response = await api.delete(`/products/${id}`);
    return response.data;
  },
};

export default productService;

