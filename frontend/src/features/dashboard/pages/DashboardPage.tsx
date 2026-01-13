import React from 'react';
import { Row, Col, Card, Table, Typography, Statistic, Alert, Select } from 'antd';
import {
  ShoppingCartOutlined,
  DollarOutlined,
  InboxOutlined,
  ArrowUpOutlined,
  ArrowDownOutlined,
  WarningOutlined,
} from '@ant-design/icons';
import { useQuery } from '@tanstack/react-query';
import { Line } from '@ant-design/charts';
import dashboardApi from '../../../api/dashboard.api';
import type { SalesChartData } from '../../../api/dashboard.api';
import { formatCurrency } from '../../../store/settings.store';
import { PageHeader } from '../../../components/common';

const { Text } = Typography;

// Stats Card Component
interface StatCardProps {
  title: string;
  value: number | string;
  prefix?: React.ReactNode;
  suffix?: string;
  change?: number;
  loading?: boolean;
  color?: string;
}

const StatCard: React.FC<StatCardProps> = ({ title, value, prefix, suffix, change, loading, color }) => (
  <Card 
    style={{ 
      borderRadius: 12,
      background: color ? `linear-gradient(135deg, ${color} 0%, ${color}dd 100%)` : undefined,
    }}
    loading={loading}
  >
    <Statistic
      title={<Text style={{ color: color ? 'rgba(255,255,255,0.85)' : undefined }}>{title}</Text>}
      value={value}
      prefix={prefix}
      suffix={suffix}
      valueStyle={{ color: color ? '#fff' : undefined, fontSize: 28 }}
    />
    {change !== undefined && (
      <div style={{ marginTop: 8 }}>
        {change >= 0 ? (
          <Text style={{ color: color ? 'rgba(255,255,255,0.9)' : '#52c41a', fontSize: 12 }}>
            <ArrowUpOutlined /> {Math.abs(change)}% من الشهر الماضي
          </Text>
        ) : (
          <Text style={{ color: color ? 'rgba(255,255,255,0.9)' : '#ff4d4f', fontSize: 12 }}>
            <ArrowDownOutlined /> {Math.abs(change)}% من الشهر الماضي
          </Text>
        )}
      </div>
    )}
  </Card>
);

const DashboardPage: React.FC = () => {
  const [period, setPeriod] = React.useState<string>('month');

  // Fetch Sales KPIs
  const { data: salesKPIs, isLoading: salesLoading, error: salesError } = useQuery({
    queryKey: ['dashboard', 'sales-kpis', period],
    queryFn: () => dashboardApi.getSalesKPIs(period),
  });

  // Fetch Inventory KPIs
  const { data: inventoryKPIs, isLoading: inventoryLoading } = useQuery({
    queryKey: ['dashboard', 'inventory-kpis'],
    queryFn: () => dashboardApi.getInventoryKPIs(),
  });

  // Fetch Top Products
  const { data: topProducts, isLoading: productsLoading } = useQuery({
    queryKey: ['dashboard', 'top-products', period],
    queryFn: () => dashboardApi.getTopProducts(5, period),
  });

  // Fetch Top Customers
  const { data: topCustomers, isLoading: customersLoading } = useQuery({
    queryKey: ['dashboard', 'top-customers', period],
    queryFn: () => dashboardApi.getTopCustomers(5, period),
  });

  // Fetch Sales Chart
  const { data: salesChart, isLoading: chartLoading } = useQuery({
    queryKey: ['dashboard', 'sales-chart', period],
    queryFn: () => dashboardApi.getSalesChart(period),
  });

  // Chart config
  const chartConfig = {
    data: salesChart?.data || [],
    xField: 'date',
    yField: 'sales',
    smooth: true,
    point: { size: 4, shape: 'circle' },
    color: '#1890ff',
    xAxis: { title: { text: 'التاريخ' } },
    yAxis: { title: { text: 'المبيعات' } },
    tooltip: {
      formatter: (data: SalesChartData) => ({
        name: 'المبيعات',
        value: formatCurrency(data.sales),
      }),
    },
  };

  // Top Products columns
  const productsColumns = [
    { 
      title: '#', 
      key: 'index',
      width: 50,
      render: (_: any, __: any, index: number) => index + 1,
    },
    { title: 'المنتج', dataIndex: 'name', key: 'name' },
    { title: 'الكود', dataIndex: 'sku', key: 'sku' },
    { 
      title: 'الكمية المباعة', 
      dataIndex: 'total_sold', 
      key: 'total_sold',
      render: (val: number) => val.toLocaleString('ar-SA'),
    },
    { 
      title: 'الإيرادات', 
      dataIndex: 'total_revenue', 
      key: 'total_revenue',
      render: (val: number) => formatCurrency(val),
    },
  ];

  // Top Customers columns
  const customersColumns = [
    { 
      title: '#', 
      key: 'index',
      width: 50,
      render: (_: any, __: any, index: number) => index + 1,
    },
    { title: 'العميل', dataIndex: 'name', key: 'name' },
    { 
      title: 'عدد الطلبات', 
      dataIndex: 'total_orders', 
      key: 'total_orders',
      render: (val: number) => val.toLocaleString('ar-SA'),
    },
    { 
      title: 'إجمالي المشتريات', 
      dataIndex: 'total_spent', 
      key: 'total_spent',
      render: (val: number) => formatCurrency(val),
    },
  ];

  // Error handling
  if (salesError) {
    return (
      <div style={{ padding: 24 }}>
        <Alert
          message="خطأ في تحميل البيانات"
          description="تعذر تحميل بيانات لوحة التحكم. تأكد من تشغيل الـ API."
          type="error"
          showIcon
        />
      </div>
    );
  }

  return (
    <div style={{ padding: 24 }}>
      <PageHeader
        title="لوحة التحكم"
        subtitle="نظرة شاملة على أداء النظام"
        extra={
          <Select
            value={period}
            onChange={setPeriod}
            style={{ width: 140 }}
            options={[
              { value: 'today', label: 'اليوم' },
              { value: 'week', label: 'هذا الأسبوع' },
              { value: 'month', label: 'هذا الشهر' },
              { value: 'year', label: 'هذا العام' },
            ]}
          />
        }
      />

      {/* Stats Cards */}
      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        <Col xs={24} sm={12} lg={6}>
          <StatCard
            title="إجمالي المبيعات"
            value={formatCurrency(salesKPIs?.data?.total_sales || 0)}
            prefix={<DollarOutlined />}
            change={salesKPIs?.data?.sales_change}
            loading={salesLoading}
            color="#1890ff"
          />
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <StatCard
            title="عدد الطلبات"
            value={salesKPIs?.data?.total_orders || 0}
            prefix={<ShoppingCartOutlined />}
            change={salesKPIs?.data?.orders_change}
            loading={salesLoading}
            color="#52c41a"
          />
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <StatCard
            title="إجمالي المنتجات"
            value={inventoryKPIs?.data?.total_products || 0}
            prefix={<InboxOutlined />}
            loading={inventoryLoading}
            color="#722ed1"
          />
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <StatCard
            title="مخزون منخفض"
            value={inventoryKPIs?.data?.low_stock || 0}
            prefix={<WarningOutlined />}
            loading={inventoryLoading}
            color="#fa8c16"
          />
        </Col>
      </Row>

      {/* Inventory Status Row */}
      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        <Col xs={24} sm={8}>
          <Card>
            <Statistic
              title="متوفر في المخزون"
              value={inventoryKPIs?.data?.in_stock || 0}
              valueStyle={{ color: '#52c41a' }}
              loading={inventoryLoading}
            />
          </Card>
        </Col>
        <Col xs={24} sm={8}>
          <Card>
            <Statistic
              title="مخزون منخفض"
              value={inventoryKPIs?.data?.low_stock || 0}
              valueStyle={{ color: '#faad14' }}
              loading={inventoryLoading}
            />
          </Card>
        </Col>
        <Col xs={24} sm={8}>
          <Card>
            <Statistic
              title="نفد من المخزون"
              value={inventoryKPIs?.data?.out_of_stock || 0}
              valueStyle={{ color: '#ff4d4f' }}
              loading={inventoryLoading}
            />
          </Card>
        </Col>
      </Row>

      {/* Sales Chart */}
      <Card
        title="مخطط المبيعات"
        style={{ marginBottom: 24, borderRadius: 12 }}
        loading={chartLoading}
      >
        {salesChart?.data && salesChart.data.length > 0 ? (
          <Line {...chartConfig} height={300} />
        ) : (
          <div style={{ textAlign: 'center', padding: 48 }}>
            <Text type="secondary">لا توجد بيانات لعرضها</Text>
          </div>
        )}
      </Card>

      {/* Top Products & Top Customers */}
      <Row gutter={[16, 16]}>
        <Col xs={24} lg={12}>
          <Card
            title="أفضل المنتجات مبيعاً"
            style={{ borderRadius: 12 }}
          >
            <Table
              columns={productsColumns}
              dataSource={topProducts?.data || []}
              loading={productsLoading}
              pagination={false}
              rowKey="id"
              size="small"
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card
            title="أفضل العملاء"
            style={{ borderRadius: 12 }}
          >
            <Table
              columns={customersColumns}
              dataSource={topCustomers?.data || []}
              loading={customersLoading}
              pagination={false}
              rowKey="id"
              size="small"
            />
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default DashboardPage;
