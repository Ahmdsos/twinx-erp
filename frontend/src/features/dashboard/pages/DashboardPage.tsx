import React from 'react';
import { Row, Col, Card, Statistic, Typography, Table, Tag, Space } from 'antd';
import {
  ShoppingCartOutlined,
  DollarOutlined,
  UserOutlined,
  InboxOutlined,
  ArrowUpOutlined,
  ArrowDownOutlined,
} from '@ant-design/icons';
import { useSettingsStore, formatCurrency } from '../../../store/settings.store';

const { Title, Text } = Typography;

// Sample data
const recentOrders = [
  { key: '1', order: '#ORD-001', customer: 'أحمد محمد', total: 1500, status: 'completed', date: '2026-01-13' },
  { key: '2', order: '#ORD-002', customer: 'محمد علي', total: 2300, status: 'processing', date: '2026-01-13' },
  { key: '3', order: '#ORD-003', customer: 'سارة أحمد', total: 890, status: 'pending', date: '2026-01-12' },
  { key: '4', order: '#ORD-004', customer: 'فاطمة خالد', total: 3200, status: 'completed', date: '2026-01-12' },
  { key: '5', order: '#ORD-005', customer: 'خالد سعيد', total: 1750, status: 'cancelled', date: '2026-01-11' },
];

const topProducts = [
  { key: '1', name: 'iPhone 14 Pro', sold: 45, revenue: 202500 },
  { key: '2', name: 'Samsung Galaxy S23', sold: 38, revenue: 133000 },
  { key: '3', name: 'MacBook Pro 14"', sold: 22, revenue: 154000 },
  { key: '4', name: 'iPad Air', sold: 31, revenue: 77500 },
  { key: '5', name: 'AirPods Pro', sold: 58, revenue: 58000 },
];

const statusColors: Record<string, string> = {
  completed: 'green',
  processing: 'blue',
  pending: 'orange',
  cancelled: 'red',
};

const statusLabels: Record<string, string> = {
  completed: 'مكتمل',
  processing: 'قيد التنفيذ',
  pending: 'معلق',
  cancelled: 'ملغي',
};

const DashboardPage: React.FC = () => {
  const { currency } = useSettingsStore();

  const orderColumns = [
    { title: 'رقم الطلب', dataIndex: 'order', key: 'order' },
    { title: 'العميل', dataIndex: 'customer', key: 'customer' },
    { 
      title: 'المبلغ', 
      dataIndex: 'total', 
      key: 'total',
      render: (value: number) => formatCurrency(value, currency),
    },
    {
      title: 'الحالة',
      dataIndex: 'status',
      key: 'status',
      render: (status: string) => (
        <Tag color={statusColors[status]}>{statusLabels[status]}</Tag>
      ),
    },
    { title: 'التاريخ', dataIndex: 'date', key: 'date' },
  ];

  const productColumns = [
    { title: 'المنتج', dataIndex: 'name', key: 'name' },
    { title: 'المبيعات', dataIndex: 'sold', key: 'sold' },
    { 
      title: 'الإيرادات', 
      dataIndex: 'revenue', 
      key: 'revenue',
      render: (value: number) => formatCurrency(value, currency),
    },
  ];

  return (
    <div>
      <Title level={3} style={{ marginBottom: 24 }}>لوحة التحكم</Title>

      {/* Stats Cards */}
      <Row gutter={[24, 24]}>
        <Col xs={24} sm={12} lg={6}>
          <Card
            style={{
              background: 'linear-gradient(135deg, #1890ff 0%, #40a9ff 100%)',
              border: 'none',
              borderRadius: 12,
            }}
          >
            <Statistic
              title={<Text style={{ color: 'rgba(255,255,255,0.8)' }}>مبيعات اليوم</Text>}
              value={15420}
              prefix={<DollarOutlined />}
              suffix={currency.symbol}
              valueStyle={{ color: '#fff', fontSize: 28 }}
            />
            <div style={{ marginTop: 8 }}>
              <ArrowUpOutlined style={{ color: '#52c41a' }} />
              <Text style={{ color: 'rgba(255,255,255,0.8)', marginRight: 4 }}>
                12% من أمس
              </Text>
            </div>
          </Card>
        </Col>

        <Col xs={24} sm={12} lg={6}>
          <Card
            style={{
              background: 'linear-gradient(135deg, #52c41a 0%, #95de64 100%)',
              border: 'none',
              borderRadius: 12,
            }}
          >
            <Statistic
              title={<Text style={{ color: 'rgba(255,255,255,0.8)' }}>الطلبات</Text>}
              value={48}
              prefix={<ShoppingCartOutlined />}
              valueStyle={{ color: '#fff', fontSize: 28 }}
            />
            <div style={{ marginTop: 8 }}>
              <ArrowUpOutlined style={{ color: '#fff' }} />
              <Text style={{ color: 'rgba(255,255,255,0.8)', marginRight: 4 }}>
                8 طلبات جديدة
              </Text>
            </div>
          </Card>
        </Col>

        <Col xs={24} sm={12} lg={6}>
          <Card
            style={{
              background: 'linear-gradient(135deg, #faad14 0%, #ffc53d 100%)',
              border: 'none',
              borderRadius: 12,
            }}
          >
            <Statistic
              title={<Text style={{ color: 'rgba(255,255,255,0.8)' }}>العملاء</Text>}
              value={256}
              prefix={<UserOutlined />}
              valueStyle={{ color: '#fff', fontSize: 28 }}
            />
            <div style={{ marginTop: 8 }}>
              <ArrowUpOutlined style={{ color: '#fff' }} />
              <Text style={{ color: 'rgba(255,255,255,0.8)', marginRight: 4 }}>
                5 عملاء جدد
              </Text>
            </div>
          </Card>
        </Col>

        <Col xs={24} sm={12} lg={6}>
          <Card
            style={{
              background: 'linear-gradient(135deg, #ff4d4f 0%, #ff7875 100%)',
              border: 'none',
              borderRadius: 12,
            }}
          >
            <Statistic
              title={<Text style={{ color: 'rgba(255,255,255,0.8)' }}>تنبيهات المخزون</Text>}
              value={12}
              prefix={<InboxOutlined />}
              valueStyle={{ color: '#fff', fontSize: 28 }}
            />
            <div style={{ marginTop: 8 }}>
              <ArrowDownOutlined style={{ color: '#fff' }} />
              <Text style={{ color: 'rgba(255,255,255,0.8)', marginRight: 4 }}>
                منتجات منخفضة
              </Text>
            </div>
          </Card>
        </Col>
      </Row>

      {/* Tables */}
      <Row gutter={[24, 24]} style={{ marginTop: 24 }}>
        <Col xs={24} lg={14}>
          <Card title="أحدث الطلبات" style={{ borderRadius: 12 }}>
            <Table
              columns={orderColumns}
              dataSource={recentOrders}
              pagination={false}
              size="small"
            />
          </Card>
        </Col>

        <Col xs={24} lg={10}>
          <Card title="المنتجات الأكثر مبيعاً" style={{ borderRadius: 12 }}>
            <Table
              columns={productColumns}
              dataSource={topProducts}
              pagination={false}
              size="small"
            />
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default DashboardPage;
