import api from './axios';
import type { ApiResponse, Brand } from '../types';

export interface BrandFilters {
  search?: string;
  is_active?: boolean;
  per_page?: number;
  page?: number;
}

export interface CreateBrandData {
  name: string;
  name_ar?: string;
  slug?: string;
  logo?: string;
  website?: string;
  description?: string;
  is_active?: boolean;
}

const brandsApi = {
  getAll: async (filters: BrandFilters = {}) => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        params.append(key, String(value));
      }
    });
    const response = await api.get(`/v1/brands?${params}`);
    return response.data;
  },

  getAllForDropdown: async () => {
    const response = await api.get<ApiResponse<Brand[]>>('/v1/brands/all');
    return response.data;
  },

  getById: async (id: string) => {
    const response = await api.get<ApiResponse<Brand>>(`/v1/brands/${id}`);
    return response.data;
  },

  create: async (data: CreateBrandData) => {
    const response = await api.post<ApiResponse<Brand>>('/v1/brands', data);
    return response.data;
  },

  update: async (id: string, data: Partial<CreateBrandData>) => {
    const response = await api.put<ApiResponse<Brand>>(`/v1/brands/${id}`, data);
    return response.data;
  },

  delete: async (id: string) => {
    const response = await api.delete<ApiResponse<null>>(`/v1/brands/${id}`);
    return response.data;
  },
};

export default brandsApi;
