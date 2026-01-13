import React, { useEffect } from 'react';
import { 
  Card, Form, Input, Button, Upload, message, Tabs, 
  Row, Col, Spin, InputNumber, Select, Divider
} from 'antd';
import { UploadOutlined, SaveOutlined } from '@ant-design/icons';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import settingsApi from '../../../api/settings.api';
import { PageHeader } from '../../../components/common';

const { TextArea } = Input;
const { TabPane } = Tabs;
const { Option } = Select;

const GeneralSettingsPage: React.FC = () => {
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  // Fetch settings
  const { data: settingsData, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => settingsApi.getAll(),
  });

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: settingsApi.update,
    onSuccess: () => {
      message.success('تم حفظ الإعدادات بنجاح');
      queryClient.invalidateQueries({ queryKey: ['settings'] });
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  // Logo upload mutation
  const uploadLogoMutation = useMutation({
    mutationFn: settingsApi.uploadLogo,
    onSuccess: () => {
      message.success('تم رفع الشعار بنجاح');
      queryClient.invalidateQueries({ queryKey: ['settings'] });
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ في رفع الشعار');
    },
  });

  useEffect(() => {
    if (settingsData?.data) {
      const { company, tax, invoice, general } = settingsData.data;
      form.setFieldsValue({
        ...company,
        ...tax,
        ...invoice,
        ...general,
      });
    }
  }, [settingsData, form]);

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      updateMutation.mutate(values);
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  const handleLogoUpload = (file: File) => {
    uploadLogoMutation.mutate(file);
    return false; // Prevent auto upload
  };

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
        title="الإعدادات العامة"
        subtitle="إعدادات الشركة والنظام"
        breadcrumbs={[{ label: 'الإعدادات' }, { label: 'عام' }]}
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

      <Form form={form} layout="vertical">
        <Tabs defaultActiveKey="company">
          <TabPane tab="معلومات الشركة" key="company">
            <Card style={{ borderRadius: 12, marginBottom: 16 }}>
              <Row gutter={24}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="name"
                    label="اسم الشركة (إنجليزي)"
                    rules={[{ required: true, message: 'مطلوب' }]}
                  >
                    <Input placeholder="Company Name" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={12}>
                  <Form.Item name="name_ar" label="اسم الشركة (عربي)">
                    <Input placeholder="اسم الشركة" dir="rtl" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={12}>
                  <Form.Item name="email" label="البريد الإلكتروني">
                    <Input placeholder="info@company.com" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={12}>
                  <Form.Item name="phone" label="الهاتف">
                    <Input placeholder="+966 12 345 6789" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={12}>
                  <Form.Item name="mobile" label="الجوال">
                    <Input placeholder="+966 50 123 4567" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={12}>
                  <Form.Item name="website" label="الموقع الإلكتروني">
                    <Input placeholder="https://www.company.com" />
                  </Form.Item>
                </Col>
                <Col xs={24}>
                  <Form.Item name="address" label="العنوان">
                    <TextArea rows={2} placeholder="العنوان بالتفصيل..." />
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="city" label="المدينة">
                    <Input placeholder="الرياض" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="country" label="البلد">
                    <Input placeholder="المملكة العربية السعودية" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="postal_code" label="الرمز البريدي">
                    <Input placeholder="12345" />
                  </Form.Item>
                </Col>
              </Row>

              <Divider>المعرّفات الرسمية</Divider>

              <Row gutter={24}>
                <Col xs={24} md={12}>
                  <Form.Item name="vat_number" label="الرقم الضريبي (VAT)">
                    <Input placeholder="300000000000003" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={12}>
                  <Form.Item name="cr_number" label="السجل التجاري">
                    <Input placeholder="1234567890" />
                  </Form.Item>
                </Col>
              </Row>

              <Divider>الشعار</Divider>

              <Upload
                accept="image/*"
                showUploadList={false}
                beforeUpload={handleLogoUpload}
              >
                <Button icon={<UploadOutlined />} loading={uploadLogoMutation.isPending}>
                  رفع شعار جديد
                </Button>
              </Upload>
            </Card>
          </TabPane>

          <TabPane tab="الضريبة" key="tax">
            <Card style={{ borderRadius: 12 }}>
              <Row gutter={24}>
                <Col xs={24} md={8}>
                  <Form.Item name="tax_rate" label="نسبة الضريبة %">
                    <InputNumber min={0} max={100} style={{ width: '100%' }} />
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="tax_type" label="نوع الضريبة">
                    <Select>
                      <Option value="exclusive">غير شاملة</Option>
                      <Option value="inclusive">شاملة</Option>
                    </Select>
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="tax_name" label="اسم الضريبة">
                    <Input placeholder="ضريبة القيمة المضافة" />
                  </Form.Item>
                </Col>
              </Row>
            </Card>
          </TabPane>

          <TabPane tab="الفواتير" key="invoice">
            <Card style={{ borderRadius: 12 }}>
              <Row gutter={24}>
                <Col xs={24} md={8}>
                  <Form.Item name="invoice_prefix" label="بادئة الفاتورة">
                    <Input placeholder="INV-" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="quotation_prefix" label="بادئة عرض السعر">
                    <Input placeholder="QT-" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="credit_note_prefix" label="بادئة إشعار الدائن">
                    <Input placeholder="CN-" />
                  </Form.Item>
                </Col>
                <Col xs={24}>
                  <Form.Item name="invoice_footer" label="تذييل الفاتورة">
                    <TextArea rows={2} placeholder="نص يظهر أسفل كل فاتورة..." />
                  </Form.Item>
                </Col>
                <Col xs={24}>
                  <Form.Item name="invoice_terms" label="الشروط والأحكام">
                    <TextArea rows={3} placeholder="شروط وأحكام الفواتير..." />
                  </Form.Item>
                </Col>
              </Row>
            </Card>
          </TabPane>

          <TabPane tab="عام" key="general">
            <Card style={{ borderRadius: 12 }}>
              <Row gutter={24}>
                <Col xs={24} md={8}>
                  <Form.Item name="date_format" label="تنسيق التاريخ">
                    <Select>
                      <Option value="YYYY-MM-DD">2024-01-15</Option>
                      <Option value="DD/MM/YYYY">15/01/2024</Option>
                      <Option value="MM/DD/YYYY">01/15/2024</Option>
                    </Select>
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="time_format" label="تنسيق الوقت">
                    <Select>
                      <Option value="HH:mm">24 ساعة (14:30)</Option>
                      <Option value="hh:mm A">12 ساعة (02:30 PM)</Option>
                    </Select>
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="timezone" label="المنطقة الزمنية">
                    <Select>
                      <Option value="Asia/Riyadh">الرياض</Option>
                      <Option value="Asia/Dubai">دبي</Option>
                      <Option value="Africa/Cairo">القاهرة</Option>
                    </Select>
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name="language" label="اللغة الافتراضية">
                    <Select>
                      <Option value="ar">العربية</Option>
                      <Option value="en">English</Option>
                    </Select>
                  </Form.Item>
                </Col>
              </Row>
            </Card>
          </TabPane>
        </Tabs>
      </Form>
    </div>
  );
};

export default GeneralSettingsPage;
