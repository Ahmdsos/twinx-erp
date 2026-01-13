import React, { Suspense, lazy } from 'react';
import { createBrowserRouter, RouterProvider, Navigate } from 'react-router-dom';
import { Spin } from 'antd';
import { MainLayout } from './components/layout';

// Lazy load pages for code splitting
const LoginPage = lazy(() => import('./features/auth/pages/LoginPage'));
const RegisterPage = lazy(() => import('./features/auth/pages/RegisterPage'));
const ForgotPasswordPage = lazy(() => import('./features/auth/pages/ForgotPasswordPage'));
const DashboardPage = lazy(() => import('./features/dashboard/pages/DashboardPage'));

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

      // Inventory
      { path: 'products', element: <ComingSoon /> },
      { path: 'categories', element: <ComingSoon /> },
      { path: 'brands', element: <ComingSoon /> },
      { path: 'units', element: <ComingSoon /> },
      { path: 'stock', element: <ComingSoon /> },
      { path: 'stock-adjustment', element: <ComingSoon /> },

      // Sales
      { path: 'invoices', element: <ComingSoon /> },
      { path: 'quotations', element: <ComingSoon /> },
      { path: 'sales-returns', element: <ComingSoon /> },
      { path: 'coupons', element: <ComingSoon /> },

      // Purchases
      { path: 'suppliers', element: <ComingSoon /> },
      { path: 'purchase-orders', element: <ComingSoon /> },
      { path: 'purchase-returns', element: <ComingSoon /> },

      // Customers
      { path: 'customers', element: <ComingSoon /> },
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

      // Settings
      { path: 'settings/general', element: <ComingSoon /> },
      { path: 'settings/company', element: <ComingSoon /> },
      { path: 'settings/currency', element: <ComingSoon /> },
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
