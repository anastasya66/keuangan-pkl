import { createContext, useCallback, useEffect, useState } from 'react';
import authService from '../services/authService';

export const AuthContext = createContext(null);

/**
 * Catatan Keamanan Penyimpanan Token:
 * Pada tahap development, Sanctum Bearer Token disimpan pada `localStorage` browser.
 * PERHATIAN KEAMANAN: Penyimpanan di localStorage rentan terhadap serangan Cross-Site Scripting (XSS).
 * Untuk lingkungan production berisiko tinggi, disarankan menggunakan Sanctum SPA Cookie-based
 * authentication dengan HttpOnly & SameSite=Lax/Strict flags untuk proteksi maksimal.
 */
const TOKEN_KEY = 'fintoko_token';

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => localStorage.getItem(TOKEN_KEY));
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  // Clear auth state helper
  const clearAuth = useCallback(() => {
    localStorage.removeItem(TOKEN_KEY);
    setToken(null);
    setUser(null);
  }, []);

  // Fetch current user details from /api/v1/auth/me
  const fetchUser = useCallback(async () => {
    const currentToken = localStorage.getItem(TOKEN_KEY);
    if (!currentToken) {
      setUser(null);
      setIsLoading(false);
      return null;
    }

    try {
      const response = await authService.getMe();
      if (response && response.success && response.data) {
        setUser(response.data);
        return response.data;
      }
      throw new Error('Respons autentikasi tidak valid');
    } catch (error) {
      console.warn('Sesi autentikasi kedaluwarsa atau tidak valid:', error.message);
      clearAuth();
      return null;
    } finally {
      setIsLoading(false);
    }
  }, [clearAuth]);

  // Handle Login
  const login = async (email, password) => {
    setIsLoading(true);
    try {
      const response = await authService.login({ email, password });
      if (response && response.success && response.data?.token) {
        const receivedToken = response.data.token;
        const receivedUser = response.data.user;

        localStorage.setItem(TOKEN_KEY, receivedToken);
        setToken(receivedToken);
        setUser(receivedUser);

        return { success: true, user: receivedUser };
      }
      throw new Error(response.message || 'Login gagal.');
    } catch (error) {
      clearAuth();
      const message =
        error.response?.data?.message || error.message || 'Terjadi kesalahan saat login.';
      const errors = error.response?.data?.errors || null;
      return { success: false, message, errors };
    } finally {
      setIsLoading(false);
    }
  };

  // Handle Logout
  const logout = async () => {
    setIsLoading(true);
    try {
      await authService.logout();
    } catch (error) {
      // Even if network fails, always clear local state
      console.warn('Panggilan logout server gagal, membersihkan sesi lokal:', error.message);
    } finally {
      clearAuth();
      setIsLoading(false);
    }
  };

  // Initial load check on mount
  useEffect(() => {
    fetchUser();
  }, [fetchUser]);

  const value = {
    user,
    token,
    isAuthenticated: Boolean(token && user),
    isLoading,
    login,
    logout,
    fetchUser,
    setUser,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export default AuthContext;
