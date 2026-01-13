import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Layout, Menu, Typography } from 'antd';
import type { MenuProps } from 'antd';
import {
  DashboardOutlined,
  ShoppingCartOutlined,
  ShopOutlined,
  UserOutlined,
  TeamOutlined,
  FileTextOutlined,
  SettingOutlined,
  DollarOutlined,
  InboxOutlined,
  CarOutlined,
  BarChartOutlined,
  AppstoreOutlined,
  TagsOutlined,
  BankOutlined,
  CalendarOutlined,
  IdcardOutlined,
} from '@ant-design/icons';
import { useSettingsStore } from '../../store/settings.store';

const { Sider } = Layout;
const { Text } = Typography;

type MenuItem = Required<MenuProps>['items'][number];

function getItem(
  label: React.ReactNode,
  key: React.Key,
  icon?: React.ReactNode,
  children?: MenuItem[],
): MenuItem {
  return {
    key,
    icon,
    children,
    label,
  } as MenuItem;
}

const menuItems: MenuItem[] = [
  getItem(<Link to="/">لوحة التحكم</Link>, '/', <DashboardOutlined />),
  
  getItem('نقطة البيع', 'pos-group', <ShoppingCartOutlined />, [
    getItem(<Link to="/pos">POS</Link>, '/pos'),
    getItem(<Link to="/pos/orders">الطلبات المعلقة</Link>, '/pos/orders'),
  ]),
  
  getItem('المخزون', 'inventory-group', <InboxOutlined />, [
    getItem(<Link to="/products">المنتجات</Link>, '/products'),
    getItem(<Link to="/categories">التصنيفات</Link>, '/categories'),
    getItem(<Link to="/brands">الماركات</Link>, '/brands'),
    getItem(<Link to="/units">الوحدات</Link>, '/units'),
    getItem(<Link to="/stock">إدارة المخزون</Link>, '/stock'),
    getItem(<Link to="/stock-adjustment">تسوية المخزون</Link>, '/stock-adjustment'),
  ]),
  
  getItem('المبيعات', 'sales-group', <ShopOutlined />, [
    getItem(<Link to="/invoices">الفواتير</Link>, '/invoices'),
    getItem(<Link to="/quotations">عروض الأسعار</Link>, '/quotations'),
    getItem(<Link to="/sales-returns">المرتجعات</Link>, '/sales-returns'),
    getItem(<Link to="/coupons">الكوبونات</Link>, '/coupons'),
  ]),
  
  getItem('المشتريات', 'purchases-group', <TagsOutlined />, [
    getItem(<Link to="/suppliers">الموردين</Link>, '/suppliers'),
    getItem(<Link to="/purchase-orders">أوامر الشراء</Link>, '/purchase-orders'),
    getItem(<Link to="/purchase-returns">مرتجعات المشتريات</Link>, '/purchase-returns'),
  ]),
  
  getItem('العملاء', 'customers-group', <TeamOutlined />, [
    getItem(<Link to="/customers">قائمة العملاء</Link>, '/customers'),
    getItem(<Link to="/customer-groups">مجموعات العملاء</Link>, '/customer-groups'),
  ]),
  
  getItem('التوصيل', 'delivery-group', <CarOutlined />, [
    getItem(<Link to="/delivery/orders">طلبات التوصيل</Link>, '/delivery/orders'),
    getItem(<Link to="/delivery/zones">مناطق التوصيل</Link>, '/delivery/zones'),
    getItem(<Link to="/delivery/drivers">السائقين</Link>, '/delivery/drivers'),
  ]),
  
  getItem('المالية', 'finance-group', <DollarOutlined />, [
    getItem(<Link to="/expenses">المصروفات</Link>, '/expenses'),
    getItem(<Link to="/income">الإيرادات</Link>, '/income'),
    getItem(<Link to="/accounts">الحسابات</Link>, '/accounts'),
    getItem(<Link to="/bank-accounts">الحسابات البنكية</Link>, '/bank-accounts'),
  ]),
  
  getItem('الموارد البشرية', 'hr-group', <IdcardOutlined />, [
    getItem(<Link to="/employees">الموظفين</Link>, '/employees'),
    getItem(<Link to="/departments">الأقسام</Link>, '/departments'),
    getItem(<Link to="/attendance">الحضور والانصراف</Link>, '/attendance'),
    getItem(<Link to="/leaves">الإجازات</Link>, '/leaves'),
    getItem(<Link to="/payroll">الرواتب</Link>, '/payroll'),
    getItem(<Link to="/holidays">العطل الرسمية</Link>, '/holidays'),
  ]),
  
  getItem('التقارير', 'reports-group', <BarChartOutlined />, [
    getItem(<Link to="/reports/sales">تقارير المبيعات</Link>, '/reports/sales'),
    getItem(<Link to="/reports/purchases">تقارير المشتريات</Link>, '/reports/purchases'),
    getItem(<Link to="/reports/inventory">تقارير المخزون</Link>, '/reports/inventory'),
    getItem(<Link to="/reports/financial">التقارير المالية</Link>, '/reports/financial'),
  ]),
  
  getItem('الإعدادات', 'settings-group', <SettingOutlined />, [
    getItem(<Link to="/settings/general">الإعدادات العامة</Link>, '/settings/general'),
    getItem(<Link to="/settings/company">بيانات الشركة</Link>, '/settings/company'),
    getItem(<Link to="/settings/currency">العملات</Link>, '/settings/currency'),
    getItem(<Link to="/settings/taxes">الضرائب</Link>, '/settings/taxes'),
    getItem(<Link to="/settings/users">المستخدمين</Link>, '/settings/users'),
    getItem(<Link to="/settings/roles">الأدوار والصلاحيات</Link>, '/settings/roles'),
  ]),
];

const Sidebar: React.FC = () => {
  const location = useLocation();
  const { sidebarCollapsed } = useSettingsStore();
  const [openKeys, setOpenKeys] = useState<string[]>([]);

  const onOpenChange: MenuProps['onOpenChange'] = (keys) => {
    setOpenKeys(keys);
  };

  return (
    <Sider
      trigger={null}
      collapsible
      collapsed={sidebarCollapsed}
      width={260}
      collapsedWidth={80}
      style={{
        overflow: 'auto',
        height: '100vh',
        position: 'fixed',
        right: 0,
        top: 0,
        bottom: 0,
        zIndex: 100,
        boxShadow: '-2px 0 8px rgba(0,0,0,0.05)',
      }}
      theme="light"
    >
      {/* Logo */}
      <div
        style={{
          height: 64,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          borderBottom: '1px solid #f0f0f0',
          padding: '0 16px',
        }}
      >
        <Link to="/" style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <AppstoreOutlined style={{ fontSize: 28, color: '#1890ff' }} />
          {!sidebarCollapsed && (
            <Text strong style={{ fontSize: 20, color: '#1890ff' }}>
              TWINX ERP
            </Text>
          )}
        </Link>
      </div>

      {/* Menu */}
      <Menu
        mode="inline"
        selectedKeys={[location.pathname]}
        openKeys={openKeys}
        onOpenChange={onOpenChange}
        items={menuItems}
        style={{
          borderRight: 0,
          padding: '8px 0',
        }}
      />
    </Sider>
  );
};

export default Sidebar;
