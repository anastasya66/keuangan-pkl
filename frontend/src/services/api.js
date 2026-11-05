import axios from 'axios';

/**
 * Axios instance terkonfigurasi untuk FinToko REST API.
 * Menggunakan Base URL dinamis dari Environment Variable (Vite).
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 10000,
});

/**
 * Request Interceptor:
 * Secara otomatis menyematkan Bearer Token jika tersimpan di localStorage.
 */
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('fintoko_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

/**
 * Response Interceptor:
 * Menangani response global dan error status (seperti 401 Unauthorized).
 */
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response) {
      // Token tidak valid atau kedaluwarsa
      if (error.response.status === 401) {
        localStorage.removeItem('fintoko_token');
        localStorage.removeItem('fintoko_user');
      }
    }
    return Promise.reject(error);
  }
);

export default api;
