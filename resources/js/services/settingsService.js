import api from './api';

export const settingsService = {
  // 1. System Settings
  async getSettings() {
    const response = await api.get('/admin/settings');
    return response.data;
  },

  async getPublicSettings() {
    const response = await api.get('/settings/public');
    return response.data;
  },

  async updateSettings(settings) {
    const response = await api.put('/admin/settings', { settings });
    return response.data;
  },

  // 2. Roles & Permissions
  async getRoles() {
    const response = await api.get('/admin/roles');
    return response.data;
  },

  async updateRolePermissions(roleId, permissions) {
    const response = await api.put(`/admin/roles/${roleId}/permissions`, { permissions });
    return response.data;
  },

  // 3. Document Sequences
  async getDocumentSequences() {
    const response = await api.get('/admin/document-sequences');
    return response.data;
  },

  async updateDocumentSequence(id, data) {
    const response = await api.put(`/admin/document-sequences/${id}`, data);
    return response.data;
  },

  // 4. Accounting Periods
  async getAccountingPeriods(fiscalYear) {
    const response = await api.get('/admin/accounting-periods', {
      params: fiscalYear ? { fiscal_year: fiscalYear } : {},
    });
    return response.data;
  },

  async togglePeriodLock(id) {
    const response = await api.patch(`/admin/accounting-periods/${id}/lock`);
    return response.data;
  },

  async closePeriod(id, notes) {
    const response = await api.post(`/admin/accounting-periods/${id}/close`, { notes });
    return response.data;
  },

  // 5. Payment Methods
  async getPaymentMethods(activeOnly = false) {
    const response = await api.get('/admin/payment-methods', {
      params: activeOnly ? { active_only: 1 } : {},
    });
    return response.data;
  },

  async createPaymentMethod(data) {
    const response = await api.post('/admin/payment-methods', data);
    return response.data;
  },

  async updatePaymentMethod(id, data) {
    const response = await api.put(`/admin/payment-methods/${id}`, data);
    return response.data;
  },

  async togglePaymentMethodStatus(id) {
    const response = await api.patch(`/admin/payment-methods/${id}/status`);
    return response.data;
  },

  // 6. Database Backup & Restore
  async getBackups() {
    const response = await api.get('/admin/backups');
    return response.data;
  },

  async createBackup(notes) {
    const response = await api.post('/admin/backups', { notes });
    return response.data;
  },

  getBackupDownloadUrl(id) {
    const base = import.meta.env.VITE_API_BASE_URL || '/api/v1';
    return `${base}/admin/backups/${id}/download`;
  },

  async restoreBackup(id, password) {
    const response = await api.post(`/admin/backups/${id}/restore`, { password });
    return response.data;
  },

  // 7. Activity Logs
  async getActivityLogs(params = {}) {
    const response = await api.get('/admin/activity-logs', { params });
    return response.data;
  },

  // 8. User Preferences & Login History
  async getUserPreferences() {
    const response = await api.get('/user/preferences');
    return response.data;
  },

  async updateUserPreferences(data) {
    const response = await api.put('/user/preferences', data);
    return response.data;
  },

  async getLoginHistories() {
    const response = await api.get('/user/login-histories');
    return response.data;
  },
};

export default settingsService;
