import api from './api';

/**
 * Admin User Management API Service
 */
export const userService = {
  /**
   * Fetch paginated list of users with search and filter.
   */
  async getUsers(params = {}) {
    const response = await api.get('/admin/users', { params });
    return response.data;
  },

  /**
   * Fetch single user details.
   */
  async getUser(id) {
    const response = await api.get(`/admin/users/${id}`);
    return response.data;
  },

  /**
   * Create a new staff or admin user.
   */
  async createUser(userData) {
    const response = await api.post('/admin/users', userData);
    return response.data;
  },

  /**
   * Update user details (name, email, optional password).
   */
  async updateUser(id, userData) {
    const response = await api.put(`/admin/users/${id}`, userData);
    return response.data;
  },

  /**
   * Update user role (admin / staff).
   */
  async updateUserRole(id, role) {
    const response = await api.patch(`/admin/users/${id}/role`, { role });
    return response.data;
  },

  /**
   * Update user active status (boolean).
   */
  async updateUserStatus(id, isActive) {
    const response = await api.patch(`/admin/users/${id}/status`, { is_active: isActive });
    return response.data;
  },

  /**
   * Soft-delete a user.
   */
  async deleteUser(id) {
    const response = await api.delete(`/admin/users/${id}`);
    return response.data;
  },
};

export default userService;

