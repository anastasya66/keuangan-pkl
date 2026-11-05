import api from './api';

/**
 * Unit Management REST API Service
 */
export const unitService = {
  /**
   * Fetch units with search, active filter, or pagination.
   * If params.all = true, returns all units without pagination.
   */
  async getUnits(params = {}) {
    const response = await api.get('/units', { params });
    return response.data;
  },

  /**
   * Fetch single unit detail.
   */
  async getUnit(id) {
    const response = await api.get(`/units/${id}`);
    return response.data;
  },

  /**
   * Create a new unit.
   */
  async createUnit(unitData) {
    const response = await api.post('/units', unitData);
    return response.data;
  },

  /**
   * Update unit.
   */
  async updateUnit(id, unitData) {
    const response = await api.put(`/units/${id}`, unitData);
    return response.data;
  },

  /**
   * Toggle unit active status.
   */
  async updateUnitStatus(id, isActive) {
    const response = await api.patch(`/units/${id}/status`, { is_active: isActive });
    return response.data;
  },

  /**
   * Delete unit (soft delete, rejected if has products).
   */
  async deleteUnit(id) {
    const response = await api.delete(`/units/${id}`);
    return response.data;
  },
};

export default unitService;
