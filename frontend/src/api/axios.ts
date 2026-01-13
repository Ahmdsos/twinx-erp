import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

// Get CSRF cookie before making requests
export const getCsrfCookie = async () => {
  await axios.get(`${api.defaults.baseURL}/sanctum/csrf-cookie`, {
    withCredentials: true,
  });
};

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Get XSRF token from cookies
    const token = document.cookie
      .split('; ')
      .find((row) => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1];

    if (token) {
      config.headers['X-XSRF-TOKEN'] = decodeURIComponent(token);
    }

    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // If CSRF token mismatch, refresh and retry
    if (error.response?.status === 419 && !originalRequest._retry) {
      originalRequest._retry = true;
      await getCsrfCookie();
      return api.request(originalRequest);
    }

    // If unauthorized, redirect to login
    if (error.response?.status === 401) {
      window.location.href = '/login';
    }

    return Promise.reject(error);
  }
);

export default api;
