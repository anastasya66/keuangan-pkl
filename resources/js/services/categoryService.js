import api from './api';

/**
 * Category Management REST API Service
 */
export const categoryService = {
  /**
   * Fetch categories with search, active filter, or pagination.
   * If params.all = true, returns all active categories.
   */
  async getCategories(params = {}) {
    const response = await api.get('/categories', { params });
    return response.data;
  },

  /**
   * Fetch single category detail.
   */
  async getCategory(id) {
    const response = await api.get(`/categories/${id}`);
    return response.data;
  },

  /**
   * Create a new category.
   */
  async createCategory(categoryData) {
    const response = await api.post('/categories', categoryData);
    return response.data;
  },

  /**
   * Update category.
   */
  async updateCategory(id, categoryData) {
    const response = await api.put(`/categories/${id}`, categoryData);
    return response.data;
  },

  /**
   * Toggle category active status.
   */
  async updateCategoryStatus(id, isActive) {
    const response = await api.patch(`/categories/${id}/status`, { is_active: isActive });
    return response.data;
  },

  /**
   * Delete category (soft delete, rejected if has products).
   */
  async deleteCategory(id) {
    const response = await api.delete(`/categories/${id}`);
    return response.data;
  },
};

export default categoryService;
