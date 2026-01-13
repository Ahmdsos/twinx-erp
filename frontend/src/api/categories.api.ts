import api from './axios';
import type { ApiResponse, Category } from '../types';

export interface CategoryFilters {
  search?: string;
  parent_id?: string;
  is_active?: boolean;
  per_page?: number;
  page?: number;
}

export interface CreateCategoryData {
  name: string;
  name_ar?: string;
  slug?: string;
  parent_id?: string;
  description?: string;
  image?: string;
  sort_order?: number;
  is_active?: boolean;
}

export interface CategoryTreeItem extends Category {
  children?: CategoryTreeItem[];
}

export interface ReorderItem {
  id: string;
  sort_order: number;
  parent_id?: string | null;
}

const categoriesApi = {
  getAll: async (filters: CategoryFilters = {}) => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        params.append(key, String(value));
      }
    });
    const response = await api.get(`/v1/categories?${params}`);
    return response.data;
  },

  getTree: async () => {
    const response = await api.get<ApiResponse<CategoryTreeItem[]>>('/v1/categories/tree');
    return response.data;
  },

  getById: async (id: string) => {
    const response = await api.get<ApiResponse<Category>>(`/v1/categories/${id}`);
    return response.data;
  },

  create: async (data: CreateCategoryData) => {
    const response = await api.post<ApiResponse<Category>>('/v1/categories', data);
    return response.data;
  },

  update: async (id: string, data: Partial<CreateCategoryData>) => {
    const response = await api.put<ApiResponse<Category>>(`/v1/categories/${id}`, data);
    return response.data;
  },

  delete: async (id: string) => {
    const response = await api.delete<ApiResponse<null>>(`/v1/categories/${id}`);
    return response.data;
  },

  reorder: async (categories: ReorderItem[]) => {
    const response = await api.post<ApiResponse<null>>('/v1/categories/reorder', { categories });
    return response.data;
  },
};

export default categoriesApi;
