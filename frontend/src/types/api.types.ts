/**
 * API Response Types
 */

// Base API response
export interface ApiResponse<T = any> {
  data: T;
  message?: string;
  success: boolean;
}

// Paginated response
export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
  };
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

// Error response
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
}

// Login response
export interface LoginResponse {
  user: User;
  token?: string;
}

// Stats response for dashboards
export interface StatsResponse {
  total: number;
  change?: number;
  changePercent?: number;
  trend?: 'up' | 'down' | 'neutral';
}

// Import User type
import type { User } from './models.types';
