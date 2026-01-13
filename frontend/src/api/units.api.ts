import api from './axios';
import type { ApiResponse, Unit } from '../types';

export interface UnitFilters {
  search?: string;
  is_active?: boolean;
  base_only?: boolean;
  per_page?: number;
  page?: number;
}

export interface CreateUnitData {
  name: string;
  short_name: string;
  base_unit_id?: string;
  conversion_factor?: number;
  description?: string;
  is_active?: boolean;
}

export interface ConvertResult {
  from_quantity: number;
  from_unit: string;
  to_quantity: number;
  to_unit: string;
}

const unitsApi = {
  getAll: async (filters: UnitFilters = {}) => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        params.append(key, String(value));
      }
    });
    const response = await api.get(`/v1/units?${params}`);
    return response.data;
  },

  getAllForDropdown: async () => {
    const response = await api.get<ApiResponse<Unit[]>>('/v1/units/all');
    return response.data;
  },

  getById: async (id: string) => {
    const response = await api.get<ApiResponse<Unit>>(`/v1/units/${id}`);
    return response.data;
  },

  create: async (data: CreateUnitData) => {
    const response = await api.post<ApiResponse<Unit>>('/v1/units', data);
    return response.data;
  },

  update: async (id: string, data: Partial<CreateUnitData>) => {
    const response = await api.put<ApiResponse<Unit>>(`/v1/units/${id}`, data);
    return response.data;
  },

  delete: async (id: string) => {
    const response = await api.delete<ApiResponse<null>>(`/v1/units/${id}`);
    return response.data;
  },

  convert: async (fromUnitId: string, toUnitId: string, quantity: number) => {
    const response = await api.post<ApiResponse<ConvertResult>>('/v1/units/convert', {
      from_unit_id: fromUnitId,
      to_unit_id: toUnitId,
      quantity,
    });
    return response.data;
  },
};

export default unitsApi;
