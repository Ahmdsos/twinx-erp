import React, { useEffect } from 'react';
import { ConfigProvider, App as AntApp, theme as antTheme } from 'antd';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import arEG from 'antd/locale/ar_EG';
import enUS from 'antd/locale/en_US';
import Routes from './routes';
import { useSettingsStore } from './store/settings.store';
import { useAuthStore } from './store/auth.store';
import { getTheme } from './styles/theme';
import './styles/global.css';

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
      staleTime: 1000 * 60 * 5, // 5 minutes
    },
  },
});

const App: React.FC = () => {
  const { theme, language } = useSettingsStore();
  const { fetchUser, isAuthenticated } = useAuthStore();

  // Fetch user on app load if authenticated
  useEffect(() => {
    if (isAuthenticated) {
      fetchUser();
    }
  }, []);

  // Set document direction based on language
  useEffect(() => {
    document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = language;
  }, [language]);

  // Set dark mode class on body
  useEffect(() => {
    if (theme === 'dark') {
      document.body.classList.add('dark');
      document.body.style.background = '#141414';
    } else {
      document.body.classList.remove('dark');
      document.body.style.background = '#f5f5f5';
    }
  }, [theme]);

  return (
    <QueryClientProvider client={queryClient}>
      <ConfigProvider
        locale={language === 'ar' ? arEG : enUS}
        direction={language === 'ar' ? 'rtl' : 'ltr'}
        theme={{
          ...getTheme(theme),
          algorithm: theme === 'dark' ? antTheme.darkAlgorithm : antTheme.defaultAlgorithm,
        }}
      >
        <AntApp>
          <Routes />
        </AntApp>
      </ConfigProvider>
    </QueryClientProvider>
  );
};

export default App;
