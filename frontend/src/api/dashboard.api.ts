import api from './axios';
import type { PaginatedResponse, ApiResponse } from '../types';

// Dashboard Stats
export interface DashboardStats {
  todaySales: number;
  todayOrders: number;
  monthSales: number;
  monthOrders: number;
  totalCustomers: number;
  totalProducts: number;
  lowStockCount: number;
  pendingOrders: number;
}

export interface SalesKPIs {
  total_sales: number;
  total_orders: number;
  average_order_value: number;
  sales_change: number;
  orders_change: number;
}

export interface InventoryKPIs {
  total_products: number;
  in_stock: number;
  low_stock: number;
  out_of_stock: number;
  total_value: number;
}

export interface FinanceKPIs {
  revenue: number;
  expenses: number;
  profit: number;
  receivables: number;
  payables: number;
}

export interface TopProduct {
  id: string;
  name: string;
  sku: string;
  total_sold: number;
  total_revenue: number;
}

export interface TopCustomer {
  id: string;
  name: string;
  total_orders: number;
  total_spent: number;
}

export interface SalesChartData {
  date: string;
  sales: number;
  orders: number;
}

const dashboardApi = {
  getSalesKPIs: async (period: string = 'month') => {
    const response = await api.get<ApiResponse<SalesKPIs>>(`/v1/dashboard/sales-kpis?period=${period}`);
    return response.data;
  },

  getInventoryKPIs: async () => {
    const response = await api.get<ApiResponse<InventoryKPIs>>('/v1/dashboard/inventory-kpis');
    return response.data;
  },

  getFinanceKPIs: async (period: string = 'month') => {
    const response = await api.get<ApiResponse<FinanceKPIs>>(`/v1/dashboard/finance-kpis?period=${period}`);
    return response.data;
  },

  getTopProducts: async (limit: number = 5, period: string = 'month') => {
    const response = await api.get<ApiResponse<TopProduct[]>>(`/v1/dashboard/top-products?limit=${limit}&period=${period}`);
    return response.data;
  },

  getTopCustomers: async (limit: number = 5, period: string = 'month') => {
    const response = await api.get<ApiResponse<TopCustomer[]>>(`/v1/dashboard/top-customers?limit=${limit}&period=${period}`);
    return response.data;
  },

  getSalesChart: async (period: string = 'month') => {
    const response = await api.get<ApiResponse<SalesChartData[]>>(`/v1/dashboard/sales-chart?period=${period}`);
    return response.data;
  },
};

export default dashboardApi;
