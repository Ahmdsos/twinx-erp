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
import { suppliersApi } from '../../../api/suppliers.api';
import dayjs from 'dayjs';

const { Title } = Typography;

const SupplierDetailsPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const { data, isLoading } = useQuery({
    queryKey: ['supplier', id],
    queryFn: () => suppliersApi.get(id!),
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
    return <div>لم يتم العثور على المورد</div>;
  }

  const supplier = data.data;

  return (
    <div style={{ padding: '24px' }}>
      <Space style={{ marginBottom: 16 }}>
        <Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/suppliers')}>
          رجوع
        </Button>
        <Button
          type="primary"
          icon={<EditOutlined />}
          onClick={() => navigate(`/suppliers/${id}/edit`)}
        >
          تعديل
        </Button>
        <Button
          icon={<FileTextOutlined />}
          onClick={() => navigate(`/suppliers/${id}/statement`)}
        >
          كشف حساب
        </Button>
      </Space>

      <Row gutter={[16, 16]}>
        <Col span={24}>
          <Card title={<Title level={4}>معلومات المورد</Title>}>
            <Descriptions bordered column={2}>
              <Descriptions.Item label="الكود">{supplier.code}</Descriptions.Item>
              <Descriptions.Item label="الحالة">
                <Tag color={supplier.is_active ? 'success' : 'default'}>
                  {supplier.is_active ? 'نشط' : 'معطل'}
                </Tag>
              </Descriptions.Item>
              <Descriptions.Item label="الاسم (إنجليزي)">{supplier.name}</Descriptions.Item>
              <Descriptions.Item label="الاسم (عربي)">
                {supplier.name_ar || '-'}
              </Descriptions.Item>
              <Descriptions.Item label="الهاتف">{supplier.phone}</Descriptions.Item>
              <Descriptions.Item label="الجوال">{supplier.mobile || '-'}</Descriptions.Item>
              <Descriptions.Item label="البريد الإلكتروني">
                {supplier.email || '-'}
              </Descriptions.Item>
              <Descriptions.Item label="الرقم الضريبي">
                {supplier.vat_number || '-'}
              </Descriptions.Item>
              <Descriptions.Item label="المدينة">{supplier.city || '-'}</Descriptions.Item>
              <Descriptions.Item label="العنوان" span={2}>
                {supplier.address || '-'}
              </Descriptions.Item>
            </Descriptions>
          </Card>
        </Col>

        <Col span={24}>
          <Row gutter={16}>
            <Col span={12}>
              <Card>
                <Statistic
                  title="الرصيد المستحق"
                  value={supplier.total_balance || 0}
                  precision={2}
                  suffix="ر.س"
                  valueStyle={{
                    color: (supplier.total_balance || 0) > 0 ? '#cf1322' : '#389e0d',
                  }}
                />
              </Card>
            </Col>
            <Col span={12}>
              <Card>
                <Statistic
                  title="شروط الدفع"
                  value={supplier.payment_terms_label}
                />
              </Card>
            </Col>
          </Row>
        </Col>

        <Col span={24}>
          <Card title={<Title level={4}>معلومات إضافية</Title>}>
            <Descriptions bordered column={2}>
              <Descriptions.Item label="إجمالي أوامر الشراء">
                {supplier.total_purchase_orders || 0}
              </Descriptions.Item>
              <Descriptions.Item label="إجمالي الفواتير">
                {supplier.total_bills || 0}
              </Descriptions.Item>
              <Descriptions.Item label="إجمالي الدفعات">
                {supplier.total_payments || 0}
              </Descriptions.Item>
              <Descriptions.Item label="تاريخ الإنشاء">
                {dayjs(supplier.created_at).format('DD/MM/YYYY')}
              </Descriptions.Item>
            </Descriptions>
          </Card>
        </Col>

        {supplier.metadata?.bank_details && (
          <Col span={24}>
            <Card title={<Title level={4}>معلومات بنكية</Title>}>
              <Descriptions bordered column={2}>
                <Descriptions.Item label="اسم البنك">
                  {supplier.metadata.bank_details.bank_name || '-'}
                </Descriptions.Item>
                <Descriptions.Item label="رقم الحساب">
                  {supplier.metadata.bank_details.account_number || '-'}
                </Descriptions.Item>
                <Descriptions.Item label="IBAN">
                  {supplier.metadata.bank_details.iban || '-'}
                </Descriptions.Item>
                <Descriptions.Item label="SWIFT Code">
                  {supplier.metadata.bank_details.swift_code || '-'}
                </Descriptions.Item>
              </Descriptions>
            </Card>
          </Col>
        )}

        {supplier.metadata?.notes && (
          <Col span={24}>
            <Card title={<Title level={4}>ملاحظات</Title>}>
              <p>{supplier.metadata.notes}</p>
            </Card>
          </Col>
        )}
      </Row>
    </div>
  );
};

export default SupplierDetailsPage;
