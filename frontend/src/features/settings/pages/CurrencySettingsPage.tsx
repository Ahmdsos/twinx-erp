import React, { useEffect } from 'react';
import { 
  Card, Form, Button, message, Table, InputNumber,
  Select, Row, Col, Spin, Typography
} from 'antd';
import { SaveOutlined, DollarOutlined } from '@ant-design/icons';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import settingsApi from '../../../api/settings.api';
import { PageHeader } from '../../../components/common';
import { useSettingsStore, CURRENCIES } from '../../../store/settings.store';
import type { Currency } from '../../../types';

const { Text } = Typography;
const { Option } = Select;

const CurrencySettingsPage: React.FC = () => {
  const [form] = Form.useForm();
  const queryClient = useQueryClient();
  const { setCurrency } = useSettingsStore();

  // Fetch settings
  const { data: settingsData, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => settingsApi.getAll(),
  });

  // Fetch currencies
  const { data: currenciesData, isLoading: currenciesLoading } = useQuery({
    queryKey: ['settings', 'currencies'],
    queryFn: () => settingsApi.getCurrencies(),
  });

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: settingsApi.update,
    onSuccess: () => {
      message.success('تم حفظ إعدادات العملة بنجاح');
      queryClient.invalidateQueries({ queryKey: ['settings'] });
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  useEffect(() => {
    if (settingsData?.data?.currency) {
      form.setFieldsValue(settingsData.data.currency);
    }
  }, [settingsData, form]);

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      updateMutation.mutate(values);
      
      // Update store
      const selectedCurrency = CURRENCIES.find(c => c.code === values.default_currency);
      if (selectedCurrency) {
        setCurrency(selectedCurrency.code);
      }
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  // Currencies table columns
  const columns = [
    {
      title: 'العملة',
      dataIndex: 'code',
      key: 'code',
      render: (code: string, record: Currency) => (
        <span>
          <Text strong>{record.symbol}</Text> {record.name_ar || record.name} ({code})
        </span>
      ),
    },
    {
      title: 'سعر الصرف',
      dataIndex: 'exchange_rate',
      key: 'exchange_rate',
      render: (rate: number) => rate.toFixed(4),
    },
    {
      title: 'خانات عشرية',
      dataIndex: 'decimal_places',
      key: 'decimal_places',
    },
    {
      title: 'افتراضية',
      dataIndex: 'is_default',
      key: 'is_default',
      render: (val: boolean) => val ? <Text type="success">✓</Text> : '-',
    },
    {
      title: 'نشطة',
      dataIndex: 'is_active',
      key: 'is_active',
      render: (val: boolean) => val ? <Text type="success">✓</Text> : <Text type="secondary">✗</Text>,
    },
  ];

  if (isLoading) {
    return (
      <div style={{ padding: 24, textAlign: 'center' }}>
        <Spin size="large" />
      </div>
    );
  }

  return (
    <div style={{ padding: 24 }}>
      <PageHeader
        title="إعدادات العملة"
        subtitle="إدارة العملات وأسعار الصرف"
        breadcrumbs={[{ label: 'الإعدادات' }, { label: 'العملة' }]}
        extra={
          <Button 
            type="primary" 
            icon={<SaveOutlined />}
            onClick={handleSubmit}
            loading={updateMutation.isPending}
          >
            حفظ التغييرات
          </Button>
        }
      />

      <Row gutter={24}>
        <Col xs={24} lg={12}>
          <Card title="إعدادات العملة الافتراضية" style={{ borderRadius: 12, marginBottom: 16 }}>
            <Form form={form} layout="vertical">
              <Form.Item
                name="default_currency"
                label="العملة الافتراضية"
                rules={[{ required: true, message: 'مطلوب' }]}
              >
                <Select>
                  {CURRENCIES.map(currency => (
                    <Option key={currency.code} value={currency.code}>
                      {currency.symbol} {currency.name_ar} ({currency.code})
                    </Option>
                  ))}
                </Select>
              </Form.Item>

              <Form.Item name="decimal_places" label="عدد الخانات العشرية">
                <InputNumber min={0} max={4} style={{ width: '100%' }} />
              </Form.Item>

              <Form.Item name="currency_position" label="موقع رمز العملة">
                <Select>
                  <Option value="before">قبل المبلغ ($ 100)</Option>
                  <Option value="after">بعد المبلغ (100 ﷼)</Option>
                </Select>
              </Form.Item>

              <Form.Item name="thousand_separator" label="فاصل الآلاف">
                <Select>
                  <Option value=",">فاصلة (1,000)</Option>
                  <Option value=".">نقطة (1.000)</Option>
                  <Option value=" ">مسافة (1 000)</Option>
                </Select>
              </Form.Item>

              <Form.Item name="decimal_separator" label="الفاصل العشري">
                <Select>
                  <Option value=".">نقطة (100.50)</Option>
                  <Option value=",">فاصلة (100,50)</Option>
                </Select>
              </Form.Item>
            </Form>
          </Card>
        </Col>

        <Col xs={24} lg={12}>
          <Card 
            title="العملات المتاحة"
            style={{ borderRadius: 12 }}
            extra={<DollarOutlined />}
          >
            <Table
              columns={columns}
              dataSource={currenciesData?.data || CURRENCIES.map(c => ({
                ...c,
                id: c.code,
                is_default: c.code === 'SAR',
                is_active: true,
              }))}
              loading={currenciesLoading}
              pagination={false}
              rowKey="code"
              size="small"
            />
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default CurrencySettingsPage;
