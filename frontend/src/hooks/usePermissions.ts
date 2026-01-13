import { useCallback } from 'react';
import { useAuth } from './useAuth';

// Define all permissions in the system
export type Permission =
  // Dashboard
  | 'dashboard.view'
  // Products
  | 'products.view'
  | 'products.create'
  | 'products.edit'
  | 'products.delete'
  // Customers
  | 'customers.view'
  | 'customers.create'
  | 'customers.edit'
  | 'customers.delete'
  // Invoices
  | 'invoices.view'
  | 'invoices.create'
  | 'invoices.edit'
  | 'invoices.delete'
  // POS
  | 'pos.access'
  | 'pos.void'
  | 'pos.discount'
  // HR
  | 'employees.view'
  | 'employees.create'
  | 'employees.edit'
  | 'employees.delete'
  | 'payroll.view'
  | 'payroll.manage'
  // Reports
  | 'reports.view'
  | 'reports.export'
  // Settings
  | 'settings.view'
  | 'settings.edit'
  | 'users.manage'
  | 'roles.manage';

// Role-based permissions mapping
const rolePermissions: Record<string, Permission[]> = {
  super_admin: ['*'] as any, // All permissions
  admin: [
    'dashboard.view',
    'products.view', 'products.create', 'products.edit', 'products.delete',
    'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
    'invoices.view', 'invoices.create', 'invoices.edit',
    'pos.access', 'pos.void', 'pos.discount',
    'employees.view', 'employees.create', 'employees.edit',
    'reports.view', 'reports.export',
    'settings.view', 'settings.edit',
  ],
  manager: [
    'dashboard.view',
    'products.view', 'products.create', 'products.edit',
    'customers.view', 'customers.create', 'customers.edit',
    'invoices.view', 'invoices.create',
    'pos.access', 'pos.discount',
    'employees.view',
    'reports.view',
  ],
  cashier: [
    'dashboard.view',
    'products.view',
    'customers.view', 'customers.create',
    'invoices.view', 'invoices.create',
    'pos.access',
  ],
  accountant: [
    'dashboard.view',
    'invoices.view',
    'reports.view', 'reports.export',
  ],
};

/**
 * Custom hook for permission checking
 */
export const usePermissions = () => {
  const { user, isAdmin } = useAuth();

  // Check if user has a specific permission
  const hasPermission = useCallback(
    (permission: Permission): boolean => {
      if (!user?.role) return false;
      
      // Super admin has all permissions
      if (user.role === 'super_admin') return true;
      
      const permissions = rolePermissions[user.role] || [];
      return permissions.includes(permission);
    },
    [user]
  );

  // Check if user has any of the given permissions
  const hasAnyPermission = useCallback(
    (permissions: Permission[]): boolean => {
      return permissions.some((p) => hasPermission(p));
    },
    [hasPermission]
  );

  // Check if user has all of the given permissions
  const hasAllPermissions = useCallback(
    (permissions: Permission[]): boolean => {
      return permissions.every((p) => hasPermission(p));
    },
    [hasPermission]
  );

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    isAdmin,
  };
};

export default usePermissions;
