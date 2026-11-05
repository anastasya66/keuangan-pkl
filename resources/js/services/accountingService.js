import api from './api';

export const accountingService = {
  // Chart of Accounts
  getAccounts: async (params = {}) => {
    const response = await api.get('/chart-of-accounts', { params });
    return response.data;
  },

  getAccount: async (id) => {
    const response = await api.get(`/chart-of-accounts/${id}`);
    return response.data;
  },

  createAccount: async (data) => {
    const response = await api.post('/chart-of-accounts', data);
    return response.data;
  },

  updateAccount: async (id, data) => {
    const response = await api.put(`/chart-of-accounts/${id}`, data);
    return response.data;
  },

  deleteAccount: async (id) => {
    const response = await api.delete(`/chart-of-accounts/${id}`);
    return response.data;
  },

  updateAccountStatus: async (id, isActive) => {
    const response = await api.patch(`/chart-of-accounts/${id}/status`, { is_active: isActive });
    return response.data;
  },

  // General Journal
  getJournals: async (params = {}) => {
    const response = await api.get('/journals', { params });
    return response.data;
  },

  getJournal: async (id) => {
    const response = await api.get(`/journals/${id}`);
    return response.data;
  },

  createJournal: async (data) => {
    const response = await api.post('/journals', data);
    return response.data;
  },

  updateJournal: async (id, data) => {
    const response = await api.put(`/journals/${id}`, data);
    return response.data;
  },

  deleteJournal: async (id) => {
    const response = await api.delete(`/journals/${id}`);
    return response.data;
  },

  postJournal: async (id) => {
    const response = await api.post(`/journals/${id}/post`);
    return response.data;
  },

  reverseJournal: async (id, reason = '') => {
    const response = await api.post(`/journals/${id}/reverse`, { reason });
    return response.data;
  },

  // General Ledger
  getGeneralLedger: async (params = {}) => {
    const response = await api.get('/general-ledger', { params });
    return response.data;
  },

  getAccountLedger: async (accountId, params = {}) => {
    const response = await api.get(`/general-ledger/${accountId}`, { params });
    return response.data;
  },

  // Trial Balance
  getTrialBalance: async (params = {}) => {
    const response = await api.get('/trial-balance', { params });
    return response.data;
  },

  // Integrations & Mappings
  getMappings: async () => {
    const response = await api.get('/accounting/mappings');
    return response.data;
  },

  syncTransaction: async (transactionId) => {
    const response = await api.post(`/accounting/sync-transaction/${transactionId}`);
    return response.data;
  },

  syncInventory: async (movementId) => {
    const response = await api.post(`/accounting/sync-inventory/${movementId}`);
    return response.data;
  },
};

export default accountingService;
