import api from './axios';
import type { PaginatedResponse, ApiResponse, Product } from '../types';

export interface ProductFilters {
  search?: string;
  category_id?: string;
  brand_id?: string;
  is_active?: boolean;
  stock_status?: 'in_stock' | 'low_stock' | 'out_of_stock';
  per_page?: number;
  page?: number;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface CreateProductData {
  sku?: string;
  name: string;
  name_ar?: string;
  category_id?: string;
  brand_id?: string;
  unit_id?: string;
  cost_price: number;
  selling_price: number;
  retail_price?: number;
  semi_wholesale_price?: number;
  quarter_wholesale_price?: number;
  wholesale_price?: number;
  distributor_price?: number;
  minimum_price?: number;
  tax_rate?: number;
  barcode?: string;
  description?: string;
  is_active?: boolean;
  track_stock?: boolean;
  reorder_level?: number;
  stock_quantity?: number;
}

export interface BulkUpdateData {
  ids: string[];
  data: {
    category_id?: string;
    brand_id?: string;
    is_active?: boolean;
    price_adjustment?: number;
    price_adjustment_type?: 'fixed' | 'percentage';
  };
}

const productsApi = {
  getAll: async (filters: ProductFilters = {}) => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        params.append(key, String(value));
      }
    });
    const response = await api.get<PaginatedResponse<Product>>(`/v1/products?${params}`);
    return response.data;
  },

  getById: async (id: string) => {
    const response = await api.get<ApiResponse<Product>>(`/v1/products/${id}`);
    return response.data;
  },

  create: async (data: CreateProductData) => {
    const response = await api.post<ApiResponse<Product>>('/v1/products', data);
    return response.data;
  },

  update: async (id: string, data: Partial<CreateProductData>) => {
    const response = await api.put<ApiResponse<Product>>(`/v1/products/${id}`, data);
    return response.data;
  },

  delete: async (id: string) => {
    const response = await api.delete<ApiResponse<null>>(`/v1/products/${id}`);
    return response.data;
  },

  // Bulk operations
  bulkDelete: async (ids: string[]) => {
    const response = await api.post<ApiResponse<{ deleted: number }>>('/v1/products/bulk-delete', { ids });
    return response.data;
  },

  bulkUpdate: async (data: BulkUpdateData) => {
    const response = await api.put<ApiResponse<{ updated: number }>>('/v1/products/bulk-update', data);
    return response.data;
  },

  // Image upload
  uploadImage: async (id: string, file: File) => {
    const formData = new FormData();
    formData.append('image', file);
    const response = await api.post<ApiResponse<{ image_url: string }>>(`/v1/products/${id}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  // Import/Export
  import: async (file: File) => {
    const formData = new FormData();
    formData.append('file', file);
    const response = await api.post<ApiResponse<{ imported: number; errors: string[] }>>('/v1/products/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  export: async (format: 'xlsx' | 'csv' = 'csv') => {
    const response = await api.get(`/v1/products/export?format=${format}`, {
      responseType: 'blob',
    });
    return response.data;
  },
};

export default productsApi;

