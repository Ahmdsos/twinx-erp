import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Card,
  Descriptions,
  Button,
  Space,
  Tag,
  Spin,
  Row,
  Col,
  Statistic,
  Typography,
} from 'antd';
import {
  ArrowLeftOutlined,
  EditOutlined,
  FileTextOutlined,
} from '@ant-design/icons';
import { useQuery } from '@tanstack/react-query';
import { customersApi } from '../../../api/customers.api';
import dayjs from 'dayjs';

const { Title } = Typography;

const CustomerDetailsPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const { data, isLoading } = useQuery({
    queryKey: ['customer', id],
    queryFn: () => customersApi.get(id!),
    enabled: !!id,
  });

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '100px' }}>
        <Spin size="large" />
      </div>
    );
  }

  if (!data?.data) {
    return <div>لم يتم العثور على العميل</div>;
  }

  const customer = data.data;

  return (
    <div style={{ padding: '24px' }}>
      <Space style={{ marginBottom: 16 }}>
        <Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/customers')}>
          رجوع
        </Button>
        <Button
          type="primary"
          icon={<EditOutlined />}
          onClick={() => navigate(`/customers/${id}/edit`)}
        >
          تعديل
        </Button>
        <Button
          icon={<FileTextOutlined />}
          onClick={() => navigate(`/customers/${id}/statement`)}
        >
          كشف حساب
        </Button>
      </Space>

      <Row gutter={[16, 16]}>
        <Col span={24}>
          <Card title={<Title level={4}>معلومات العميل</Title>}>
            <Descriptions bordered column={2}>
              <Descriptions.Item label="الكود">{customer.code}</Descriptions.Item>
              <Descriptions.Item label="الحالة">
                <Tag color={customer.is_active ? 'success' : 'default'}>
                  {customer.is_active ? 'نشط' : 'معطل'}
                </Tag>
              </Descriptions.Item>
              <Descriptions.Item label="الاسم (إنجليزي)">{customer.name}</Descriptions.Item>
              <Descriptions.Item label="الاسم (عربي)">
                {customer.name_ar || '-'}
              </Descriptions.Item>
              <Descriptions.Item label="الهاتف">{customer.phone}</Descriptions.Item>
              <Descriptions.Item label="الجوال">{customer.mobile || '-'}</Descriptions.Item>
              <Descriptions.Item label="البريد الإلكتروني">
                {customer.email || '-'}
              </Descriptions.Item>
              <Descriptions.Item label="الرقم الضريبي">
                {customer.vat_number || '-'}
              </Descriptions.Item>
              <Descriptions.Item label="رقم السجل التجاري">
                {customer.cr_number || '-'}
              </Descriptions.Item>
              <Descriptions.Item label="المدينة">{customer.city || '-'}</Descriptions.Item>
              <Descriptions.Item label="العنوان" span={2}>
                {customer.address || '-'}
              </Descriptions.Item>
            </Descriptions>
          </Card>
        </Col>

        <Col span={24}>
          <Row gutter={16}>
            <Col span={8}>
              <Card>
                <Statistic
                  title="الرصيد المستحق"
                  value={customer.total_balance || 0}
                  precision={2}
                  suffix="ر.س"
                  valueStyle={{
                    color: (customer.total_balance || 0) > 0 ? '#cf1322' : '#389e0d',
                  }}
                />
              </Card>
            </Col>
            <Col span={8}>
              <Card>
                <Statistic
                  title="حد الائتمان"
                  value={customer.credit_limit}
                  precision={2}
                  suffix="ر.س"
                />
                <div style={{ marginTop: 8 }}>
                  <Tag color={
                    customer.credit_status === 'good' ? 'success' :
                    customer.credit_status === 'warning' ? 'warning' : 'error'
                  }>
                    {customer.credit_status === 'no_credit' ? 'لا يوجد' :
                     customer.credit_status === 'good' ? 'جيد' :
                     customer.credit_status === 'warning' ? 'تحذير' : 'تجاوز الحد'}
                  </Tag>
                </div>
              </Card>
            </Col>
            <Col span={8}>
              <Card>
                <Statistic
                  title="المتاح من الائتمان"
                  value={customer.available_credit}
                  precision={2}
                  suffix="ر.س"
                />
              </Card>
            </Col>
          </Row>
        </Col>

        <Col span={24}>
          <Card title={<Title level={4}>معلومات مالية</Title>}>
            <Descriptions bordered column={2}>
              <Descriptions.Item label="قائمة الأسعار">
                {customer.price_list?.name || '-'}
              </Descriptions.Item>
              <Descriptions.Item label="شروط الدفع">
                {customer.payment_terms_label}
              </Descriptions.Item>
              <Descriptions.Item label="إجمالي الفواتير">
                {customer.total_invoices || 0}
              </Descriptions.Item>
              <Descriptions.Item label="إجمالي الدفعات">
                {customer.total_payments || 0}
              </Descriptions.Item>
              <Descriptions.Item label="تاريخ الإنشاء">
                {dayjs(customer.created_at).format('DD/MM/YYYY')}
              </Descriptions.Item>
              <Descriptions.Item label="آخر تحديث">
                {dayjs(customer.updated_at).format('DD/MM/YYYY')}
              </Descriptions.Item>
            </Descriptions>
          </Card>
        </Col>

        {customer.metadata?.notes && (
          <Col span={24}>
            <Card title={<Title level={4}>ملاحظات</Title>}>
              <p>{customer.metadata.notes}</p>
            </Card>
          </Col>
        )}
      </Row>
    </div>
  );
};

export default CustomerDetailsPage;
