import { useAuthStore } from '../store/auth.store';
import { useCallback, useEffect } from 'react';

/**
 * Custom hook for authentication
 * Provides auth state and actions with convenient methods
 */
export const useAuth = () => {
  const {
    user,
    isAuthenticated,
    isLoading,
    error,
    login,
    register,
    logout,
    fetchUser,
    clearError,
  } = useAuthStore();

  // Fetch user on mount if authenticated but no user data
  useEffect(() => {
    if (isAuthenticated && !user) {
      fetchUser();
    }
  }, [isAuthenticated, user, fetchUser]);

  // Check if user has a specific role
  const hasRole = useCallback(
    (role: string) => {
      return user?.role === role;
    },
    [user]
  );

  // Check if user is admin
  const isAdmin = useCallback(() => {
    return user?.role === 'admin' || user?.role === 'super_admin';
  }, [user]);

  return {
    user,
    isAuthenticated,
    isLoading,
    error,
    login,
    register,
    logout,
    fetchUser,
    clearError,
    hasRole,
    isAdmin,
  };
};

export default useAuth;
