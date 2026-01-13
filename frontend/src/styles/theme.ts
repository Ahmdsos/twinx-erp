import type { ThemeConfig } from 'antd';

// Light theme configuration
export const lightTheme: ThemeConfig = {
  token: {
    // Primary colors
    colorPrimary: '#1890ff',
    colorSuccess: '#52c41a',
    colorWarning: '#faad14',
    colorError: '#ff4d4f',
    colorInfo: '#1890ff',
    
    // Background
    colorBgContainer: '#ffffff',
    colorBgLayout: '#f5f5f5',
    
    // Border
    borderRadius: 8,
    
    // Typography
    fontFamily: "'Tajawal', 'Segoe UI', sans-serif",
    fontSize: 14,
    
    // Spacing
    padding: 16,
    margin: 16,
  },
  components: {
    Button: {
      borderRadius: 6,
      controlHeight: 40,
    },
    Input: {
      borderRadius: 6,
      controlHeight: 40,
    },
    Select: {
      borderRadius: 6,
      controlHeight: 40,
    },
    Card: {
      borderRadius: 12,
    },
    Table: {
      borderRadius: 8,
    },
    Modal: {
      borderRadius: 12,
    },
    Menu: {
      itemBorderRadius: 6,
    },
  },
};

// Dark theme configuration
export const darkTheme: ThemeConfig = {
  token: {
    // Primary colors
    colorPrimary: '#177ddc',
    colorSuccess: '#49aa19',
    colorWarning: '#d89614',
    colorError: '#d32029',
    colorInfo: '#177ddc',
    
    // Background
    colorBgContainer: '#1f1f1f',
    colorBgLayout: '#141414',
    
    // Border
    borderRadius: 8,
    
    // Typography
    fontFamily: "'Tajawal', 'Segoe UI', sans-serif",
    fontSize: 14,
  },
  components: {
    Button: {
      borderRadius: 6,
      controlHeight: 40,
    },
    Input: {
      borderRadius: 6,
      controlHeight: 40,
    },
    Select: {
      borderRadius: 6,
      controlHeight: 40,
    },
    Card: {
      borderRadius: 12,
    },
    Table: {
      borderRadius: 8,
    },
    Modal: {
      borderRadius: 12,
    },
    Menu: {
      itemBorderRadius: 6,
    },
  },
};

export const getTheme = (mode: 'light' | 'dark'): ThemeConfig => {
  return mode === 'dark' ? darkTheme : lightTheme;
};
