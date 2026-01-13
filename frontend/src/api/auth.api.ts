import api, { getCsrfCookie } from './axios';

export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface User {
  id: string;
  name: string;
  email: string;
  role?: string;
  company_id?: string;
  avatar?: string;
}

export const authApi = {
  // Get CSRF cookie
  getCsrfCookie,

  // Login
  login: async (credentials: LoginCredentials) => {
    await getCsrfCookie();
    const response = await api.post('/api/login', credentials);
    return response.data;
  },

  // Register
  register: async (data: RegisterData) => {
    await getCsrfCookie();
    const response = await api.post('/api/register', data);
    return response.data;
  },

  // Logout
  logout: async () => {
    const response = await api.post('/api/logout');
    return response.data;
  },

  // Get current user
  getUser: async (): Promise<User> => {
    const response = await api.get('/api/user');
    return response.data;
  },

  // Forgot password
  forgotPassword: async (email: string) => {
    await getCsrfCookie();
    const response = await api.post('/api/forgot-password', { email });
    return response.data;
  },

  // Reset password
  resetPassword: async (data: {
    email: string;
    password: string;
    password_confirmation: string;
    token: string;
  }) => {
    const response = await api.post('/api/reset-password', data);
    return response.data;
  },
};

export default authApi;
