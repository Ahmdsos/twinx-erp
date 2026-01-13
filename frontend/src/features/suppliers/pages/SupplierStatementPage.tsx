import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Card,
  Table,
  DatePicker,
  Button,
  Space,
  Typography,
  Statistic,
  Row,
  Col,
  Tag,
  Descriptions,
  Spin,
} from 'antd';
import { PrinterOutlined, DownloadOutlined, ArrowLeftOutlined } from '@ant-design/icons';
import { useQuery } from '@tanstack/react-query';
import { suppliersApi } from '../../../api/suppliers.api';
import type { StatementTransaction } from '../../../types/customer.types';
import dayjs, { Dayjs } from 'dayjs';

const { RangePicker } = DatePicker;
const { Title, Text } = Typography;

const SupplierStatementPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  
  const [dateRange, setDateRange] = useState<[Dayjs, Dayjs]>([
    dayjs().startOf('month'),
    dayjs(),
  ]);

  const { data, isLoading } = useQuery({
    queryKey: ['supplier-statement', id, dateRange],
    queryFn: () =>
      suppliersApi.getStatement(id!, {
        from: dateRange[0].format('YYYY-MM-DD'),
        to: dateRange[1].format('YYYY-MM-DD'),
      }),
    enabled: !!id,
  });

  const columns = [
    {
      title: 'التاريخ',
      dataIndex: 'date',
      key: 'date',
      width: 120,
      render: (date: string) => dayjs(date).format('DD/MM/YYYY'),
    },
    {
      title: 'النوع',
      dataIndex: 'type',
      key: 'type',
      width: 100,
      render: (type: string) => {
        const typeMap = {
          bill: { text: 'فاتورة شراء', color: 'blue' },
          payment: { text: 'دفعة', color: 'green' },
          debit_note: { text: 'إشعار مدين', color: 'orange' },
        };
        const config = typeMap[type as keyof typeof typeMap] || { text: type, color: 'default' };
        return <Tag color={config.color}>{config.text}</Tag>;
      },
    },
    {
      title: 'المرجع',
      dataIndex: 'reference',
      key: 'reference',
      width: 120,
    },
    {
      title: 'البيان',
      dataIndex: 'description',
      key: 'description',
    },
    {
      title: 'مدين',
      dataIndex: 'debit',
      key: 'debit',
      width: 120,
      align: 'right' as const,
      render: (value: number) =>
        value > 0 ? (
          <Text strong style={{ color: '#cf1322' }}>
            {value.toFixed(2)} ر.س
          </Text>
        ) : (
          '-'
        ),
    },
    {
      title: 'دائن',
      dataIndex: 'credit',
      key: 'credit',
      width: 120,
      align: 'right' as const,
      render: (value: number) =>
        value > 0 ? (
          <Text strong style={{ color: '#389e0d' }}>
            {value.toFixed(2)} ر.س
          </Text>
        ) : (
          '-'
        ),
    },
    {
      title: 'الرصيد',
      dataIndex: 'balance',
      key: 'balance',
      width: 120,
      align: 'right' as const,
      render: (value: number) => (
        <Text strong>{value.toFixed(2)} ر.س</Text>
      ),
    },
  ];

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '100px' }}>
        <Spin size="large" />
      </div>
    );
  }

  if (!data?.data) {
    return <div>لم يتم العثور على البيانات</div>;
  }

  const { supplier, period, opening_balance, transactions, closing_balance } = data.data;

  return (
    <div style={{ padding: '24px', maxWidth: '1200px', margin: '0 auto' }}>
      <Space style={{ marginBottom: 16 }}>
        <Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/suppliers')}>
          رجوع
        </Button>
      </Space>

      <Card>
        <div style={{ marginBottom: 24 }}>
          <Row justify="space-between" align="middle">
            <Col>
              <Title level={3}>كشف حساب مورد</Title>
            </Col>
            <Col>
              <Space>
                <RangePicker
                  value={dateRange}
                  onChange={(dates) => {
                    if (dates && dates[0] && dates[1]) {
                      setDateRange([dates[0], dates[1]]);
                    }
                  }}
                  format="DD/MM/YYYY"
                />
                <Button icon={<PrinterOutlined />} onClick={() => window.print()}>
                  طباعة
                </Button>
                <Button icon={<DownloadOutlined />}>تصدير</Button>
              </Space>
            </Col>
          </Row>
        </div>

        <Descriptions bordered column={2} style={{ marginBottom: 24 }}>
          <Descriptions.Item label="رقم المورد">{supplier.code}</Descriptions.Item>
          <Descriptions.Item label="الاسم">{supplier.display_name}</Descriptions.Item>
          <Descriptions.Item label="الهاتف">{supplier.phone}</Descriptions.Item>
          <Descriptions.Item label="البريد الإلكتروني">{supplier.email || '-'}</Descriptions.Item>
          <Descriptions.Item label="من تاريخ">
            {dayjs(period.from).format('DD/MM/YYYY')}
          </Descriptions.Item>
          <Descriptions.Item label="إلى تاريخ">
            {dayjs(period.to).format('DD/MM/YYYY')}
          </Descriptions.Item>
        </Descriptions>

        <Row gutter={16} style={{ marginBottom: 24 }}>
          <Col span={8}>
            <Card>
              <Statistic
                title="الرصيد الافتتاحي"
                value={opening_balance}
                precision={2}
                suffix="ر.س"
                valueStyle={{ color: opening_balance > 0 ? '#cf1322' : '#389e0d' }}
              />
            </Card>
          </Col>
          <Col span={8}>
            <Card>
              <Statistic
                title="إجمالي المدين"
                value={transactions.reduce((sum: number, t: StatementTransaction) => sum + t.debit, 0)}
                precision={2}
                suffix="ر.س"
                valueStyle={{ color: '#cf1322' }}
              />
            </Card>
          </Col>
          <Col span={8}>
            <Card>
              <Statistic
                title="إجمالي الدائن"
                value={transactions.reduce((sum: number, t: StatementTransaction) => sum + t.credit, 0)}
                precision={2}
                suffix="ر.س"
                valueStyle={{ color: '#389e0d' }}
              />
            </Card>
          </Col>
        </Row>

        <Table<StatementTransaction>
          columns={columns}
          dataSource={transactions}
          rowKey="id"
          pagination={false}
          summary={() => (
            <Table.Summary fixed>
              <Table.Summary.Row style={{ backgroundColor: '#fafafa', fontWeight: 'bold' }}>
                <Table.Summary.Cell index={0} colSpan={6} align="right">
                  <Text strong>الرصيد الختامي:</Text>
                </Table.Summary.Cell>
                <Table.Summary.Cell index={1} align="right">
                  <Text strong style={{ fontSize: 16, color: closing_balance > 0 ? '#cf1322' : '#389e0d' }}>
                    {closing_balance.toFixed(2)} ر.س
                  </Text>
                </Table.Summary.Cell>
              </Table.Summary.Row>
            </Table.Summary>
          )}
        />

        {closing_balance > 0 && (
          <div style={{ marginTop: 16, padding: 12, backgroundColor: '#fff1f0', borderRadius: 4 }}>
            <Text type="danger" strong>
              ملاحظة: المبلغ المستحق علينا للمورد: {closing_balance.toFixed(2)} ر.س
            </Text>
          </div>
        )}
      </Card>
    </div>
  );
};

export default SupplierStatementPage;
