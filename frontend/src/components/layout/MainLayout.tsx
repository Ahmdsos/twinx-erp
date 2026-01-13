import React from 'react';
import { Outlet, Navigate } from 'react-router-dom';
import { Layout, ConfigProvider, theme as antTheme } from 'antd';
import arEG from 'antd/locale/ar_EG';
import enUS from 'antd/locale/en_US';
import Sidebar from './Sidebar';
import Header from './Header';
import { useAuthStore } from '../../store/auth.store';
import { useSettingsStore } from '../../store/settings.store';
import { getTheme } from '../../styles/theme';

const { Content } = Layout;

const MainLayout: React.FC = () => {
  const { isAuthenticated, isLoading } = useAuthStore();
  const { sidebarCollapsed, theme, language } = useSettingsStore();

  // Show loading while checking auth
  if (isLoading) {
    return (
      <div style={{ 
        display: 'flex', 
        justifyContent: 'center', 
        alignItems: 'center', 
        height: '100vh' 
      }}>
        جاري التحميل...
      </div>
    );
  }

  // Redirect to login if not authenticated
  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return (
    <ConfigProvider
      locale={language === 'ar' ? arEG : enUS}
      direction={language === 'ar' ? 'rtl' : 'ltr'}
      theme={{
        ...getTheme(theme),
        algorithm: theme === 'dark' ? antTheme.darkAlgorithm : antTheme.defaultAlgorithm,
      }}
    >
      <Layout style={{ minHeight: '100vh' }}>
        <Sidebar />
        <Layout
          style={{
            marginRight: sidebarCollapsed ? 80 : 260,
            transition: 'margin-right 0.2s',
            background: theme === 'dark' ? '#141414' : '#f5f5f5',
          }}
        >
          <Header />
          <Content
            style={{
              margin: 24,
              padding: 24,
              minHeight: 280,
              background: theme === 'dark' ? '#1f1f1f' : '#fff',
              borderRadius: 12,
            }}
          >
            <Outlet />
          </Content>
        </Layout>
      </Layout>
    </ConfigProvider>
  );
};

export default MainLayout;
