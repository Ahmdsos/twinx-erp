import api from './axios';
import type { Supplier, SupplierFormData, SupplierStatement } from '../types/customer.types';
import type { ApiResponse, PaginatedResponse } from './customers.api';

export const suppliersApi = {
  /**
   * Get paginated suppliers list
   */
  async list(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    is_active?: boolean;
    has_balance?: boolean;
    sort_field?: string;
    sort_order?: 'asc' | 'desc';
  }): Promise<PaginatedResponse<Supplier>> {
    const { data } = await api.get('/api/v1/suppliers', { params });
    return data;
  },

  /**
   * Get all suppliers (for dropdowns)
   */
  async all(): Promise<ApiResponse<Supplier[]>> {
    const { data } = await api.get('/api/v1/suppliers/all');
    return data;
  },

  /**
   * Get single supplier
   */
  async get(id: string): Promise<ApiResponse<Supplier>> {
    const { data } = await api.get(`/api/v1/suppliers/${id}`);
    return data;
  },

  /**
   * Create new supplier
   */
  async create(supplier: SupplierFormData): Promise<ApiResponse<Supplier>> {
    const { data } = await api.post('/api/v1/suppliers', supplier);
    return data;
  },

  /**
   * Update supplier
   */
  async update(id: string, supplier: Partial<SupplierFormData>): Promise<ApiResponse<Supplier>> {
    const { data } = await api.put(`/api/v1/suppliers/${id}`, supplier);
    return data;
  },

  /**
   * Delete supplier
   */
  async delete(id: string): Promise<ApiResponse<null>> {
    const { data} = await api.delete(`/api/v1/suppliers/${id}`);
    return data;
  },

  /**
   * Bulk delete suppliers
   */
  async bulkDelete(ids: string[]): Promise<ApiResponse<{ deleted: number; errors: string[] }>> {
    const { data } = await api.post('/api/v1/suppliers/bulk-delete', { ids });
    return data;
  },

  /**
   * Get supplier statement (كشف حساب)
   */
  async getStatement(
    id: string,
    params?: {
      from?: string;
      to?: string;
    }
  ): Promise<ApiResponse<SupplierStatement>> {
    const { data } = await api.get(`/api/v1/suppliers/${id}/statement`, { params });
    return data;
  },
};
