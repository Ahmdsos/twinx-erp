import React from 'react';
import { Layout, Button, Dropdown, Badge, Avatar, Space, Typography } from 'antd';
import type { MenuProps } from 'antd';
import {
  MenuFoldOutlined,
  MenuUnfoldOutlined,
  BellOutlined,
  UserOutlined,
  SettingOutlined,
  LogoutOutlined,
  SunOutlined,
  MoonOutlined,
  GlobalOutlined,
} from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/auth.store';
import { useSettingsStore, CURRENCIES } from '../../store/settings.store';

const { Header: AntHeader } = Layout;
const { Text, Link } = Typography;

const Header: React.FC = () => {
  const navigate = useNavigate();
  const { user, logout } = useAuthStore();
  const { 
    sidebarCollapsed, 
    toggleSidebar, 
    theme, 
    toggleTheme, 
    language, 
    setLanguage,
    currency,
    setCurrency 
  } = useSettingsStore();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const userMenuItems: MenuProps['items'] = [
    {
      key: 'profile',
      icon: <UserOutlined />,
      label: 'الملف الشخصي',
      onClick: () => navigate('/settings/profile'),
    },
    {
      key: 'settings',
      icon: <SettingOutlined />,
      label: 'الإعدادات',
      onClick: () => navigate('/settings/general'),
    },
    {
      type: 'divider',
    },
    {
      key: 'logout',
      icon: <LogoutOutlined />,
      label: 'تسجيل الخروج',
      onClick: handleLogout,
      danger: true,
    },
  ];

  const languageMenuItems: MenuProps['items'] = [
    {
      key: 'ar',
      label: '🇸🇦 العربية',
      onClick: () => setLanguage('ar'),
    },
    {
      key: 'en',
      label: '🇺🇸 English',
      onClick: () => setLanguage('en'),
    },
  ];

  const currencyMenuItems: MenuProps['items'] = CURRENCIES.map((c) => ({
    key: c.code,
    label: `${c.symbol} ${c.name_ar}`,
    onClick: () => setCurrency(c.code),
  }));

  const notificationItems: MenuProps['items'] = [
    {
      key: '1',
      label: (
        <div style={{ padding: '8px 0' }}>
          <Text strong>طلب جديد #1234</Text>
          <br />
          <Text type="secondary" style={{ fontSize: 12 }}>منذ 5 دقائق</Text>
        </div>
      ),
    },
    {
      key: '2',
      label: (
        <div style={{ padding: '8px 0' }}>
          <Text strong>مخزون منخفض: iPhone 14</Text>
          <br />
          <Text type="secondary" style={{ fontSize: 12 }}>منذ 10 دقائق</Text>
        </div>
      ),
    },
    {
      type: 'divider',
    },
    {
      key: 'all',
      label: <Link>عرض كل الإشعارات</Link>,
      onClick: () => navigate('/notifications'),
    },
  ];

  return (
    <AntHeader
      style={{
        padding: '0 24px',
        background: '#fff',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        boxShadow: '0 1px 4px rgba(0,0,0,0.08)',
        position: 'sticky',
        top: 0,
        zIndex: 99,
        marginRight: sidebarCollapsed ? 80 : 260,
        transition: 'margin-right 0.2s',
      }}
    >
      {/* Left side */}
      <Space>
        <Button
          type="text"
          icon={sidebarCollapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
          onClick={toggleSidebar}
          style={{ fontSize: 18 }}
        />
      </Space>

      {/* Right side */}
      <Space size="middle">
        {/* Theme toggle */}
        <Button
          type="text"
          icon={theme === 'dark' ? <SunOutlined /> : <MoonOutlined />}
          onClick={toggleTheme}
          title={theme === 'dark' ? 'الوضع الفاتح' : 'الوضع الداكن'}
        />

        {/* Currency selector */}
        <Dropdown menu={{ items: currencyMenuItems }} placement="bottomRight">
          <Button type="text">
            {currency.symbol} {currency.code}
          </Button>
        </Dropdown>

        {/* Language selector */}
        <Dropdown menu={{ items: languageMenuItems }} placement="bottomRight">
          <Button type="text" icon={<GlobalOutlined />}>
            {language === 'ar' ? 'العربية' : 'English'}
          </Button>
        </Dropdown>

        {/* Notifications */}
        <Dropdown 
          menu={{ items: notificationItems }} 
          placement="bottomRight"
          trigger={['click']}
        >
          <Badge count={5} size="small">
            <Button type="text" icon={<BellOutlined style={{ fontSize: 18 }} />} />
          </Badge>
        </Dropdown>

        {/* User menu */}
        <Dropdown menu={{ items: userMenuItems }} placement="bottomRight">
          <Space style={{ cursor: 'pointer' }}>
            <Avatar 
              icon={<UserOutlined />} 
              src={user?.avatar}
              style={{ backgroundColor: '#1890ff' }}
            />
            <Text strong>{user?.name || 'المستخدم'}</Text>
          </Space>
        </Dropdown>
      </Space>
    </AntHeader>
  );
};

export default Header;
