import api from './api';

/**
 * Authentication and Profile API Service
 */
export const authService = {
  /**
   * Log in user with email & password.
   */
  async login(credentials) {
    const response = await api.post('/auth/login', credentials);
    return response.data;
  },

  /**
   * Log out currently authenticated user.
   */
  async logout() {
    const response = await api.post('/auth/logout');
    return response.data;
  },

  /**
   * Fetch currently authenticated user profile.
   */
  async getMe() {
    const response = await api.get('/auth/me');
    return response.data;
  },

  /**
   * Rotate active authentication token.
   */
  async refreshToken() {
    const response = await api.post('/auth/refresh');
    return response.data;
  },

  /**
   * Get user profile.
   */
  async getProfile() {
    const response = await api.get('/user/profile');
    return response.data;
  },

  /**
   * Update user profile (name and email).
   */
  async updateProfile(profileData) {
    const response = await api.put('/user/profile', profileData);
    return response.data;
  },

  /**
   * Change user password.
   */
  async changePassword(passwordData) {
    const response = await api.put('/user/change-password', passwordData);
    return response.data;
  },
};

export default authService;

