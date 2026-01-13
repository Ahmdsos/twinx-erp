import React, { Suspense, lazy } from 'react';
import { createBrowserRouter, RouterProvider, Navigate } from 'react-router-dom';
import { Spin } from 'antd';
import { MainLayout } from './components/layout';

// Lazy load pages for code splitting
const LoginPage = lazy(() => import('./features/auth/pages/LoginPage'));
const RegisterPage = lazy(() => import('./features/auth/pages/RegisterPage'));
const ForgotPasswordPage = lazy(() => import('./features/auth/pages/ForgotPasswordPage'));
const DashboardPage = lazy(() => import('./features/dashboard/pages/DashboardPage'));

// Phase 1 Pages
const ProductsPage = lazy(() => import('./features/products/pages/ProductsPage'));
const CategoriesPage = lazy(() => import('./features/categories/pages/CategoriesPage'));
const BrandsPage = lazy(() => import('./features/brands/pages/BrandsPage'));
const UnitsPage = lazy(() => import('./features/units/pages/UnitsPage'));
const GeneralSettingsPage = lazy(() => import('./features/settings/pages/GeneralSettingsPage'));
const CurrencySettingsPage = lazy(() => import('./features/settings/pages/CurrencySettingsPage'));

// Phase 2 Pages - Customers & Suppliers
const CustomersPage = lazy(() => import('./features/customers/pages/CustomersPage'));
const CustomerDetailsPage = lazy(() => import('./features/customers/pages/CustomerDetailsPage'));
const CustomerStatementPage = lazy(() => import('./features/customers/pages/CustomerStatementPage'));
const SuppliersPage = lazy(() => import('./features/suppliers/pages/SuppliersPage'));
const SupplierDetailsPage = lazy(() => import('./features/suppliers/pages/SupplierDetailsPage'));
const SupplierStatementPage = lazy(() => import('./features/suppliers/pages/SupplierStatementPage'));

// Loading component
const PageLoader = () => (
  <div style={{ 
    display: 'flex', 
    justifyContent: 'center', 
    alignItems: 'center', 
    height: '100vh' 
  }}>
    <Spin size="large" />
  </div>
);

// Wrap lazy component with Suspense
const withSuspense = (Component: React.LazyExoticComponent<React.FC>) => (
  <Suspense fallback={<PageLoader />}>
    <Component />
  </Suspense>
);

// Placeholder component for routes not yet implemented
const ComingSoon: React.FC = () => (
  <div style={{ textAlign: 'center', padding: 48 }}>
    <h2>🚧 قيد الإنشاء</h2>
    <p>هذه الصفحة ستكون متاحة قريباً</p>
  </div>
);

const router = createBrowserRouter([
  // Public routes (Auth)
  {
    path: '/login',
    element: withSuspense(LoginPage),
  },
  {
    path: '/register',
    element: withSuspense(RegisterPage),
  },
  {
    path: '/forgot-password',
    element: withSuspense(ForgotPasswordPage),
  },

  // Protected routes (Main Layout)
  {
    path: '/',
    element: <MainLayout />,
    children: [
      {
        index: true,
        element: withSuspense(DashboardPage),
      },

      // POS
      { path: 'pos', element: <ComingSoon /> },
      { path: 'pos/orders', element: <ComingSoon /> },

      // Inventory (Phase 1)
      { path: 'products', element: withSuspense(ProductsPage) },
      { path: 'categories', element: withSuspense(CategoriesPage) },
      { path: 'brands', element: withSuspense(BrandsPage) },
      { path: 'units', element: withSuspense(UnitsPage) },
      { path: 'stock', element: <ComingSoon /> },
      { path: 'stock-adjustment', element: <ComingSoon /> },

      // Sales
      { path: 'invoices', element: <ComingSoon /> },
      { path: 'quotations', element: <ComingSoon /> },
      { path: 'sales-returns', element: <ComingSoon /> },
      { path: 'coupons', element: <ComingSoon /> },

      // Purchases
      { path: 'suppliers', element: withSuspense(SuppliersPage) },
      { path: 'suppliers/:id', element: withSuspense(SupplierDetailsPage) },
      { path: 'suppliers/:id/statement', element: withSuspense(SupplierStatementPage) },
      { path: 'purchase-orders', element: <ComingSoon /> },
      { path: 'purchase-returns', element: <ComingSoon /> },

      // Customers (Phase 2)
      { path: 'customers', element: withSuspense(CustomersPage) },
      { path: 'customers/:id', element: withSuspense(CustomerDetailsPage) },
      { path: 'customers/:id/statement', element: withSuspense(CustomerStatementPage) },
      { path: 'customer-groups', element: <ComingSoon /> },

      // Delivery
      { path: 'delivery/orders', element: <ComingSoon /> },
      { path: 'delivery/zones', element: <ComingSoon /> },
      { path: 'delivery/drivers', element: <ComingSoon /> },

      // Finance
      { path: 'expenses', element: <ComingSoon /> },
      { path: 'income', element: <ComingSoon /> },
      { path: 'accounts', element: <ComingSoon /> },
      { path: 'bank-accounts', element: <ComingSoon /> },

      // HR
      { path: 'employees', element: <ComingSoon /> },
      { path: 'departments', element: <ComingSoon /> },
      { path: 'attendance', element: <ComingSoon /> },
      { path: 'leaves', element: <ComingSoon /> },
      { path: 'payroll', element: <ComingSoon /> },
      { path: 'holidays', element: <ComingSoon /> },

      // Reports
      { path: 'reports/sales', element: <ComingSoon /> },
      { path: 'reports/purchases', element: <ComingSoon /> },
      { path: 'reports/inventory', element: <ComingSoon /> },
      { path: 'reports/financial', element: <ComingSoon /> },

      // Settings (Phase 1)
      { path: 'settings/general', element: withSuspense(GeneralSettingsPage) },
      { path: 'settings/company', element: withSuspense(GeneralSettingsPage) },
      { path: 'settings/currency', element: withSuspense(CurrencySettingsPage) },
      { path: 'settings/taxes', element: <ComingSoon /> },
      { path: 'settings/users', element: <ComingSoon /> },
      { path: 'settings/roles', element: <ComingSoon /> },
      { path: 'settings/profile', element: <ComingSoon /> },

      // Notifications
      { path: 'notifications', element: <ComingSoon /> },

      // Catch all - 404
      { path: '*', element: <Navigate to="/" replace /> },
    ],
  },
]);

const Routes: React.FC = () => {
  return <RouterProvider router={router} />;
};

export default Routes;

