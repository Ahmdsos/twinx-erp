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
import { suppliersApi } from '../../../api/suppliers.api';
import type { Supplier, SupplierFormData } from '../../../types/customer.types';

interface SupplierFormProps {
  open: boolean;
  supplier: Supplier | null;
  onClose: () => void;
  onSuccess: () => void;
}

const SupplierForm: React.FC<SupplierFormProps> = ({
  open,
  supplier,
  onClose,
  onSuccess,
}) => {
  const [form] = Form.useForm();
  const isEdit = !!supplier;

  useEffect(() => {
    if (open) {
      if (supplier) {
        form.setFieldsValue(supplier);
      } else {
        form.resetFields();
      }
    }
  }, [open, supplier, form]);

  const createMutation = useMutation({
    mutationFn: suppliersApi.create,
    onSuccess: () => {
      message.success('تم إنشاء المورد بنجاح');
      form.resetFields();
      onSuccess();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<SupplierFormData> }) =>
      suppliersApi.update(id, data),
    onSuccess:() => {
      message.success('تم تحديث المورد بنجاح');
      onSuccess();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      
      if (isEdit && supplier) {
        updateMutation.mutate({ id: supplier.id, data: values });
      } else {
        createMutation.mutate(values);
      }
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  return (
    <Modal
      title={isEdit ? 'تعديل المورد' : 'إضافة مورد جديد'}
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
                        <Input placeholder="اسم المورد" />
                      </Form.Item>
                    </Col>
                    <Col span={12}>
                      <Form.Item name="name_ar" label="الاسم (عربي)">
                        <Input placeholder="اسم المورد بالعربي" />
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
                        rules={[{ type: 'email' }]}
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
                <Form.Item
                  name="payment_terms"
                  label="شروط الدفع (أيام)"
                >
                  <InputNumber
                    style={{ width: '100%' }}
                    min={0}
                    max={365}
                    placeholder="30"
                  />
                </Form.Item>
              ),
            },
            {
              key: 'bank',
              label: 'معلومات بنكية',
              children: (
                <>
                  <Form.Item name={['metadata', 'bank_details', 'bank_name']} label="اسم البنك">
                    <Input placeholder="البنك الأهلي" />
                  </Form.Item>
                  <Form.Item name={['metadata', 'bank_details', 'account_number']} label="رقم الحساب">
                    <Input placeholder="SA0000000000000000000000" />
                  </Form.Item>
                  <Form.Item name={['metadata', 'bank_details', 'iban']} label="IBAN">
                    <Input placeholder="SA0000000000000000000000" maxLength={34} />
                  </Form.Item>
                </>
              ),
            },
          ]}
        />
      </Form>
    </Modal>
  );
};

export default SupplierForm;
