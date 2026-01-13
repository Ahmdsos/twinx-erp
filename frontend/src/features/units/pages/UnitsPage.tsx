import React, { useState } from 'react';
import { 
  Button, Modal, Form, Input, InputNumber, Select, message, 
  Space, Tag, Popconfirm, Tooltip, Switch
} from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined, SwapOutlined } from '@ant-design/icons';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ProTable, type ProColumns } from '@ant-design/pro-components';
import unitsApi from '../../../api/units.api';
import { PageHeader } from '../../../components/common';
import type { Unit } from '../../../types';

const { Option } = Select;

const UnitsPage: React.FC = () => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUnit, setEditingUnit] = useState<Unit | null>(null);
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  // Fetch base units for dropdown
  const { data: baseUnitsData } = useQuery({
    queryKey: ['units', 'base'],
    queryFn: () => unitsApi.getAll({ base_only: true }),
  });

  // Mutations
  const createMutation = useMutation({
    mutationFn: unitsApi.create,
    onSuccess: () => {
      message.success('تم إنشاء الوحدة بنجاح');
      queryClient.invalidateQueries({ queryKey: ['units'] });
      handleCloseModal();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: any }) => unitsApi.update(id, data),
    onSuccess: () => {
      message.success('تم تحديث الوحدة بنجاح');
      queryClient.invalidateQueries({ queryKey: ['units'] });
      handleCloseModal();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: unitsApi.delete,
    onSuccess: () => {
      message.success('تم حذف الوحدة بنجاح');
      queryClient.invalidateQueries({ queryKey: ['units'] });
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const handleOpenModal = (unit?: Unit) => {
    if (unit) {
      setEditingUnit(unit);
      form.setFieldsValue(unit);
    } else {
      setEditingUnit(null);
      form.resetFields();
    }
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setEditingUnit(null);
    form.resetFields();
  };

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      if (editingUnit) {
        updateMutation.mutate({ id: editingUnit.id, data: values });
      } else {
        createMutation.mutate(values);
      }
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  const columns: ProColumns<Unit>[] = [
    {
      title: 'الاسم',
      dataIndex: 'name',
      key: 'name',
    },
    {
      title: 'الاختصار',
      dataIndex: 'short_name',
      key: 'short_name',
      render: (val) => <Tag color="blue">{val}</Tag>,
    },
    {
      title: 'الوحدة الأساسية',
      dataIndex: ['baseUnit', 'name'],
      key: 'baseUnit',
      render: (val) => val || <Tag>أساسية</Tag>,
    },
    {
      title: 'معدل التحويل',
      dataIndex: 'conversion_rate',
      key: 'conversion_rate',
      render: (val, record) => (
        record.base_unit_id ? (
          <Space>
            <SwapOutlined />
            <span>1 = {val} {record.baseUnit?.short_name}</span>
          </Space>
        ) : '-'
      ),
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
            title="هل أنت متأكد من حذف هذه الوحدة؟"
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
        title="الوحدات"
        subtitle="إدارة وحدات قياس المنتجات"
        breadcrumbs={[{ label: 'المخزون' }, { label: 'الوحدات' }]}
        extra={
          <Button 
            type="primary" 
            icon={<PlusOutlined />}
            onClick={() => handleOpenModal()}
          >
            إضافة وحدة
          </Button>
        }
      />

      <ProTable<Unit>
        columns={columns}
        request={async (params) => {
          const response = await unitsApi.getAll({
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
        title={editingUnit ? 'تعديل الوحدة' : 'إضافة وحدة جديدة'}
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
            {editingUnit ? 'تحديث' : 'إنشاء'}
          </Button>,
        ]}
      >
        <Form form={form} layout="vertical" initialValues={{ is_active: true, conversion_rate: 1 }}>
          <Form.Item
            name="name"
            label="الاسم"
            rules={[{ required: true, message: 'الاسم مطلوب' }]}
          >
            <Input placeholder="مثال: كيلوغرام" />
          </Form.Item>

          <Form.Item
            name="short_name"
            label="الاختصار"
            rules={[{ required: true, message: 'الاختصار مطلوب' }]}
          >
            <Input placeholder="مثال: كغ" />
          </Form.Item>

          <Form.Item name="base_unit_id" label="الوحدة الأساسية">
            <Select placeholder="اختر الوحدة الأساسية (اتركها فارغة إذا كانت هذه وحدة أساسية)" allowClear>
              {baseUnitsData?.data?.map((unit: Unit) => (
                <Option key={unit.id} value={unit.id}>{unit.name} ({unit.short_name})</Option>
              ))}
            </Select>
          </Form.Item>

          <Form.Item 
            name="conversion_rate" 
            label="معدل التحويل"
            tooltip="كم وحدة أساسية تساوي هذه الوحدة"
          >
            <InputNumber min={0.0001} style={{ width: '100%' }} />
          </Form.Item>

          <Form.Item name="description" label="الوصف">
            <Input.TextArea rows={2} placeholder="وصف الوحدة..." />
          </Form.Item>

          <Form.Item name="is_active" label="الحالة" valuePropName="checked">
            <Switch checkedChildren="نشط" unCheckedChildren="معطل" />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
};

export default UnitsPage;
