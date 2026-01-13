import React, { useEffect } from 'react';
import {
  Modal,
  Form,
  Input,
  InputNumber,
  Switch,
  Select,
  Tabs,
  Row,
  Col,
  message,
} from 'antd';
import { useMutation } from '@tanstack/react-query';
import { customersApi } from '../../../api/customers.api';
import type { Customer, CustomerFormData } from '../../../types/customer.types';

interface CustomerFormProps {
  open: boolean;
  customer: Customer | null;
  onClose: () => void;
  onSuccess: () => void;
}

const CustomerForm: React.FC<CustomerFormProps> = ({
  open,
  customer,
  onClose,
  onSuccess,
}) => {
  const [form] = Form.useForm();
  const isEdit = !!customer;

  // Reset form when customer changes
  useEffect(() => {
    if (open) {
      if (customer) {
        form.setFieldsValue({
          ...customer,
          price_list_id: customer.price_list?.id,
        });
      } else {
        form.resetFields();
      }
    }
  }, [open, customer, form]);

  const createMutation = useMutation({
    mutationFn: customersApi.create,
    onSuccess: () => {
      message.success('تم إنشاء العميل بنجاح');
      form.resetFields();
      onSuccess();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ أثناء الإنشاء');
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<CustomerFormData> }) =>
      customersApi.update(id, data),
    onSuccess: () => {
      message.success('تم تحديث العميل بنجاح');
      onSuccess();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ أثناء التحديث');
    },
  });

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      
      if (isEdit && customer) {
        updateMutation.mutate({ id: customer.id, data: values });
      } else {
        createMutation.mutate(values);
      }
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  return (
    <Modal
      title={isEdit ? 'تعديل العميل' : 'إضافة عميل جديد'}
      open={open}
      onCancel={onClose}
      onOk={handleSubmit}
      confirmLoading={createMutation.isPending || updateMutation.isPending}
      width={800}
      okText={isEdit ? 'تحديث' : 'إنشاء'}
      cancelText="إلغاء"
    >
      <Form
        form={form}
        layout="vertical"
        initialValues={{
          is_active: true,
          payment_terms: 30,
          credit_limit: 0,
          country: 'SA',
        }}
      >
        <Tabs
          items={[
            {
              key: 'basic',
              label: 'معلومات أساسية',
              children: (
                <>
                  <Row gutter={16}>
                    <Col span={12}>
                      <Form.Item
                        name="name"
                        label="الاسم (إنجليزي)"
                        rules={[{ required: true, message: 'الاسم مطلوب' }]}
                      >
                        <Input placeholder="اسم العميل" />
                      </Form.Item>
                    </Col>
                    <Col span={12}>
                      <Form.Item name="name_ar" label="الاسم (عربي)">
                        <Input placeholder="اسم العميل بالعربي" />
                      </Form.Item>
                    </Col>
                  </Row>

                  <Row gutter={16}>
                    <Col span={12}>
                      <Form.Item
                        name="phone"
                        label="رقم الهاتف"
                        rules={[{ required: true, message: 'رقم الهاتف مطلوب' }]}
                      >
                        <Input placeholder="+966xxxxxxxxx" />
                      </Form.Item>
                    </Col>
                    <Col span={12}>
                      <Form.Item name="mobile" label="رقم الجوال">
                        <Input placeholder="+966xxxxxxxxx" />
                      </Form.Item>
                    </Col>
                  </Row>

                  <Row gutter={16}>
                    <Col span={12}>
                      <Form.Item
                        name="email"
                        label="البريد الإلكتروني"
                        rules={[{ type: 'email', message: 'البريد الإلكتروني غير صحيح' }]}
                      >
                        <Input placeholder="email@example.com" />
                      </Form.Item>
                    </Col>
                    <Col span={12}>
                      <Form.Item
                        name="is_active"
                        label="الحالة"
                        valuePropName="checked"
                      >
                        <Switch checkedChildren="نشط" unCheckedChildren="معطل" />
                      </Form.Item>
                    </Col>
                  </Row>
                </>
              ),
            },
            {
              key: 'legal',
              label: 'معلومات قانونية',
              children: (
                <>
                  <Row gutter={16}>
                    <Col span={12}>
                      <Form.Item name="vat_number" label="الرقم الضريبي">
                        <Input placeholder="300000000000003" maxLength={15} />
                      </Form.Item>
                    </Col>
                    <Col span={12}>
                      <Form.Item name="cr_number" label="رقم السجل التجاري">
                        <Input placeholder="1234567890" />
                      </Form.Item>
                    </Col>
                  </Row>

                  <Form.Item name="address" label="العنوان">
                    <Input.TextArea rows={2} placeholder="العنوان الكامل" />
                  </Form.Item>

                  <Row gutter={16}>
                    <Col span={12}>
                      <Form.Item name="city" label="المدينة">
                        <Input placeholder="الرياض" />
                      </Form.Item>
                    </Col>
                    <Col span={12}>
                      <Form.Item name="country" label="الدولة">
                        <Select>
                          <Select.Option value="SA">السعودية</Select.Option>
                          <Select.Option value="AE">الإمارات</Select.Option>
                          <Select.Option value="KW">الكويت</Select.Option>
                          <Select.Option value="BH">البحرين</Select.Option>
                          <Select.Option value="QA">قطر</Select.Option>
                          <Select.Option value="OM">عمان</Select.Option>
                        </Select>
                      </Form.Item>
                    </Col>
                  </Row>
                </>
              ),
            },
            {
              key: 'financial',
              label: 'معلومات مالية',
              children: (
                <>
                  <Row gutter={16}>
                    <Col span={12}>
                      <Form.Item
                        name="credit_limit"
                        label="حد الائتمان (ر.س)"
                        tooltip="الحد الأقصى للمبلغ المستحق على العميل"
                      >
                        <InputNumber
                          style={{ width: '100%' }}
                          min={0}
                          precision={2}
                          placeholder="0.00"
                        />
                      </Form.Item>
                    </Col>
                    <Col span={12}>
                      <Form.Item
                        name="payment_terms"
                        label="شروط الدفع (أيام)"
                        tooltip="عدد الأيام المسموح بها للدفع الآجل"
                      >
                        <InputNumber
                          style={{ width: '100%' }}
                          min={0}
                          max={365}
                          placeholder="30"
                        />
                      </Form.Item>
                    </Col>
                  </Row>

                  <Form.Item
                    name="price_list_id"
                    label="قائمة الأسعار"
                    tooltip="قائمة الأسعار المخصصة لهذا العميل"
                  >
                    <Select
                      placeholder="اختر قائمة الأسعار"
                      allowClear
                      showSearch
                      filterOption={(input, option) =>
                        String(option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                      }
                    >
                      {/* سيتم تحميل قوائم الأسعار من API */}
                    </Select>
                  </Form.Item>
                </>
              ),
            },
            {
              key: 'notes',
              label: 'ملاحظات',
              children: (
                <Form.Item name={['metadata', 'notes']} label="ملاحظات">
                  <Input.TextArea
                    rows={6}
                    placeholder="أي ملاحظات إضافية عن العميل..."
                  />
                </Form.Item>
              ),
            },
          ]}
        />
      </Form>
    </Modal>
  );
};

export default CustomerForm;
