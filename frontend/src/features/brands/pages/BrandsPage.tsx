import React, { useState } from 'react';
import { 
  Button, Modal, Form, Input, message, Space, Tag, Popconfirm, 
  Tooltip, Avatar, Switch
} from 'antd';
import { 
  PlusOutlined, EditOutlined, DeleteOutlined, 
  GlobalOutlined
} from '@ant-design/icons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ProTable, type ProColumns } from '@ant-design/pro-components';
import brandsApi from '../../../api/brands.api';
import { PageHeader } from '../../../components/common';
import type { Brand } from '../../../types';

const BrandsPage: React.FC = () => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingBrand, setEditingBrand] = useState<Brand | null>(null);
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  // Mutations
  const createMutation = useMutation({
    mutationFn: brandsApi.create,
    onSuccess: () => {
      message.success('تم إنشاء الماركة بنجاح');
      queryClient.invalidateQueries({ queryKey: ['brands'] });
      handleCloseModal();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: any }) => brandsApi.update(id, data),
    onSuccess: () => {
      message.success('تم تحديث الماركة بنجاح');
      queryClient.invalidateQueries({ queryKey: ['brands'] });
      handleCloseModal();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: brandsApi.delete,
    onSuccess: () => {
      message.success('تم حذف الماركة بنجاح');
      queryClient.invalidateQueries({ queryKey: ['brands'] });
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const handleOpenModal = (brand?: Brand) => {
    if (brand) {
      setEditingBrand(brand);
      form.setFieldsValue(brand);
    } else {
      setEditingBrand(null);
      form.resetFields();
    }
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setEditingBrand(null);
    form.resetFields();
  };

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      if (editingBrand) {
        updateMutation.mutate({ id: editingBrand.id, data: values });
      } else {
        createMutation.mutate(values);
      }
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  const columns: ProColumns<Brand>[] = [
    {
      title: 'الشعار',
      dataIndex: 'logo',
      key: 'logo',
      width: 80,
      render: (logo, record) => (
        <Avatar 
          src={logo} 
          size={40}
          style={{ backgroundColor: '#f5f5f5' }}
        >
          {record.name?.charAt(0)}
        </Avatar>
      ),
    },
    {
      title: 'الاسم',
      dataIndex: 'name',
      key: 'name',
      render: (text, record) => (
        <Space direction="vertical" size={0}>
          <span style={{ fontWeight: 500 }}>{text}</span>
          {record.name_ar && (
            <span style={{ fontSize: 12, color: '#888' }}>{record.name_ar}</span>
          )}
        </Space>
      ),
    },
    {
      title: 'الموقع',
      dataIndex: 'website',
      key: 'website',
      render: (_, record) => record.website ? (
        <a href={record.website} target="_blank" rel="noopener noreferrer">
          <GlobalOutlined /> {record.website}
        </a>
      ) : '-',
    },
    {
      title: 'الحالة',
      dataIndex: 'is_active',
      key: 'is_active',
      width: 80,
      render: (val) => (
        <Tag color={val ? 'success' : 'default'}>
          {val ? 'نشط' : 'معطل'}
        </Tag>
      ),
    },
    {
      title: 'الإجراءات',
      key: 'actions',
      width: 100,
      render: (_, record) => (
        <Space>
          <Tooltip title="تعديل">
            <Button 
              type="text" 
              icon={<EditOutlined />} 
              size="small"
              onClick={() => handleOpenModal(record)}
            />
          </Tooltip>
          <Popconfirm
            title="هل أنت متأكد من حذف هذه الماركة؟"
            onConfirm={() => deleteMutation.mutate(record.id)}
            okText="نعم"
            cancelText="لا"
          >
            <Tooltip title="حذف">
              <Button 
                type="text" 
                icon={<DeleteOutlined />} 
                size="small"
                danger
              />
            </Tooltip>
          </Popconfirm>
        </Space>
      ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <PageHeader
        title="الماركات"
        subtitle="إدارة ماركات المنتجات"
        breadcrumbs={[{ label: 'المخزون' }, { label: 'الماركات' }]}
        extra={
          <Button 
            type="primary" 
            icon={<PlusOutlined />}
            onClick={() => handleOpenModal()}
          >
            إضافة ماركة
          </Button>
        }
      />

      <ProTable<Brand>
        columns={columns}
        request={async (params) => {
          const response = await brandsApi.getAll({
            search: params.keyword,
            page: params.current,
            per_page: params.pageSize,
          });
          return {
            data: response.data,
            success: response.success,
            total: response.meta?.total,
          };
        }}
        rowKey="id"
        search={false}
        pagination={{
          pageSize: 20,
        }}
        options={{
          reload: true,
          density: true,
        }}
      />

      {/* Add/Edit Modal */}
      <Modal
        title={editingBrand ? 'تعديل الماركة' : 'إضافة ماركة جديدة'}
        open={isModalOpen}
        onCancel={handleCloseModal}
        footer={[
          <Button key="cancel" onClick={handleCloseModal}>
            إلغاء
          </Button>,
          <Button 
            key="submit" 
            type="primary" 
            onClick={handleSubmit}
            loading={createMutation.isPending || updateMutation.isPending}
          >
            {editingBrand ? 'تحديث' : 'إنشاء'}
          </Button>,
        ]}
      >
        <Form form={form} layout="vertical" initialValues={{ is_active: true }}>
          <Form.Item
            name="name"
            label="الاسم (إنجليزي)"
            rules={[{ required: true, message: 'الاسم مطلوب' }]}
          >
            <Input placeholder="Brand Name" />
          </Form.Item>

          <Form.Item name="name_ar" label="الاسم (عربي)">
            <Input placeholder="اسم الماركة" dir="rtl" />
          </Form.Item>

          <Form.Item name="website" label="الموقع الإلكتروني">
            <Input placeholder="https://example.com" />
          </Form.Item>

          <Form.Item name="description" label="الوصف">
            <Input.TextArea rows={2} placeholder="وصف الماركة..." />
          </Form.Item>

          <Form.Item name="is_active" label="الحالة" valuePropName="checked">
            <Switch checkedChildren="نشط" unCheckedChildren="معطل" />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
};

export default BrandsPage;
