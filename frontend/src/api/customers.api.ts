import api from './axios';
import type { Customer, CustomerFormData, CustomerStatement } from '../types/customer.types';

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export const customersApi = {
  /**
   * Get paginated customers list
   */
  async list(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    is_active?: boolean;
    price_list_id?: string;
    has_balance?: boolean;
    sort_field?: string;
    sort_order?: 'asc' | 'desc';
  }): Promise<PaginatedResponse<Customer>> {
    const { data } = await api.get('/api/v1/customers', { params });
    return data;
  },

  /**
   * Get all customers (for dropdowns)
   */
  async all(): Promise<ApiResponse<Customer[]>> {
    const { data } = await api.get('/api/v1/customers/all');
    return data;
  },

  /**
   * Get single customer
   */
  async get(id: string): Promise<ApiResponse<Customer>> {
    const { data } = await api.get(`/api/v1/customers/${id}`);
    return data;
  },

  /**
   * Create new customer
   */
  async create(customer: CustomerFormData): Promise<ApiResponse<Customer>> {
    const { data } = await api.post('/api/v1/customers', customer);
    return data;
  },

  /**
   * Update customer
   */
  async update(id: string, customer: Partial<CustomerFormData>): Promise<ApiResponse<Customer>> {
    const { data } = await api.put(`/api/v1/customers/${id}`, customer);
    return data;
  },

  /**
   * Delete customer
   */
  async delete(id: string): Promise<ApiResponse<null>> {
    const { data } = await api.delete(`/api/v1/customers/${id}`);
    return data;
  },

  /**
   * Bulk delete customers
   */
  async bulkDelete(ids: string[]): Promise<ApiResponse<{ deleted: number; errors: string[] }>> {
    const { data } = await api.post('/api/v1/customers/bulk-delete', { ids });
    return data;
  },

  /**
   * Get customer statement (كشف حساب)
   */
  async getStatement(
    id: string,
    params?: {
      from?: string;
      to?: string;
    }
  ): Promise<ApiResponse<CustomerStatement>> {
    const { data } = await api.get(`/api/v1/customers/${id}/statement`, { params });
    return data;
  },
};
