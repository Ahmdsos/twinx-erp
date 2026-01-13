import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ProTable, type ProColumns } from '@ant-design/pro-components';
import { Button, Tag, Space, Modal, Dropdown, message } from 'antd';
import {
  PlusOutlined,
  DeleteOutlined,
  FileTextOutlined,
  ExclamationCircleOutlined,
  MoreOutlined,
  EditOutlined,
} from '@ant-design/icons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { suppliersApi } from '../../../api/suppliers.api';
import type { Supplier } from '../../../types/customer.types';
import SupplierForm from '../components/SupplierForm';

const SuppliersPage: React.FC = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [modalOpen, setModalOpen] = useState(false);
  const [selectedSupplier, setSelectedSupplier] = useState<Supplier | null>(null);
  const [selectedRows, setSelectedRows] = useState<Supplier[]>([]);

  const deleteMutation = useMutation({
    mutationFn: suppliersApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['suppliers'] });
      message.success('تم حذف المورد بنجاح');
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ أثناء الحذف');
    },
  });

  const bulkDeleteMutation = useMutation({
    mutationFn: suppliersApi.bulkDelete,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['suppliers'] });
      setSelectedRows([]);
      message.success(`تم حذف ${data.data.deleted} مورد بنجاح`);
      if (data.data.errors.length > 0) {
        Modal.warning({
          title: 'تحذيرات',
          content: data.data.errors.join('\n'),
        });
      }
    },
  });

  const columns: ProColumns<Supplier>[] = [
    {
      title: 'الكود',
      dataIndex: 'code',
      width: 120,
      copyable: true,
      fixed: 'left',
    },
    {
      title: 'الاسم',
      dataIndex: 'name',
      width: 200,
      fixed: 'left',
      render: (_, record) => (
        <Space direction="vertical" size={0}>
          <strong>{record.name}</strong>
          {record.name_ar && <small style={{ color: '#888' }}>{record.name_ar}</small>}
        </Space>
      ),
    },
    {
      title: 'الهاتف',
      dataIndex: 'phone',
      width: 150,
      copyable: true,
    },
    {
      title: 'المدينة',
      dataIndex: 'city',
      width: 120,
      hideInTable: true,
    },
    {
      title: 'الرصيد المستحق',
      dataIndex: 'total_balance',
      width: 150,
      hideInSearch: true,
      render: (_, record) => {
        if (!record.total_balance || record.total_balance === 0) {
          return <Tag color="success">مسدد</Tag>;
        }
        return (
          <span style={{ color: 'red', fontWeight: 'bold' }}>
            {record.total_balance.toFixed(2)} ر.س
          </span>
        );
      },
    },
    {
      title: 'شروط الدفع',
      dataIndex: 'payment_terms_label',
      width: 120,
      hideInSearch: true,
    },
    {
      title: 'الحالة',
      dataIndex: 'is_active',
      width: 100,
      valueType: 'select',
      valueEnum: {
        true: { text: 'نشط', status: 'Success' },
        false: { text: 'معطل', status: 'Default' },
      },
      render: (_, record) => (
        <Tag color={record.is_active ? 'success' : 'default'}>
          {record.is_active ? 'نشط' : 'معطل'}
        </Tag>
      ),
    },
    {
      title: 'الإجراءات',
      key: 'actions',
      width: 150,
      fixed: 'right',
      hideInSearch: true,
      render: (_, record) => (
        <Dropdown
          menu={{
            items: [
              {
                key: 'edit',
                icon: <EditOutlined />,
                label: 'تعديل',
                onClick: () => {
                  setSelectedSupplier(record);
                  setModalOpen(true);
                },
              },
              {
                key: 'statement',
                icon: <FileTextOutlined />,
                label: 'كشف حساب',
                onClick: () => navigate(`/suppliers/${record.id}/statement`),
              },
              {
                type: 'divider',
              },
              {
                key: 'delete',
                icon: <DeleteOutlined />,
                label: 'حذف',
                danger: true,
                onClick: () => {
                  Modal.confirm({
                    title: 'تأكيد الحذف',
                    icon: <ExclamationCircleOutlined />,
                    content: `هل تريد حذف المورد "${record.name}"؟`,
                    okText: 'حذف',
                    cancelText: 'إلغاء',
                    okButtonProps: { danger: true },
                    onOk: () => deleteMutation.mutate(record.id),
                  });
                },
              },
            ],
          }}
        >
          <Button type="text" icon={<MoreOutlined />} />
        </Dropdown>
      ),
    },
  ];

  return (
    <div style={{ padding: '24px' }}>
      <ProTable<Supplier>
        columns={columns}
        request={async (params) => {
          const response = await suppliersApi.list({
            page: params.current,
            per_page: params.pageSize,
            search: params.name || params.code || params.phone,
            is_active: params.is_active,
          });
          return {
            data: response.data,
            success: response.success,
            total: response.meta.total,
          };
        }}
        rowKey="id"
        search={{
          labelWidth: 'auto',
        }}
        pagination={{
          pageSize: 20,
          showSizeChanger: true,
        }}
        rowSelection={{
          onChange: (_, selectedRows) => {
            setSelectedRows(selectedRows);
          },
        }}
        toolBarRender={() => [
          selectedRows.length > 0 && (
            <Button
              key="bulk-delete"
              danger
              icon={<DeleteOutlined />}
              onClick={() => {
                Modal.confirm({
                  title: 'حذف متعدد',
                  content: `هل تريد حذف ${selectedRows.length} مورد؟`,
                  okText: 'حذف',
                  cancelText: 'إلغاء',
                  okButtonProps: { danger: true },
                  onOk: () => {
                    bulkDeleteMutation.mutate(selectedRows.map((r) => r.id));
                  },
                });
              }}
            >
              حذف ({selectedRows.length})
            </Button>
          ),
          <Button
            key="add"
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => {
              setSelectedSupplier(null);
              setModalOpen(true);
            }}
          >
            إضافة مورد جديد
          </Button>,
        ]}
        headerTitle="إدارة الموردين"
        scroll={{ x: 1200 }}
      />

      <SupplierForm
        open={modalOpen}
        supplier={selectedSupplier}
        onClose={() => {
          setModalOpen(false);
          setSelectedSupplier(null);
        }}
        onSuccess={() => {
          queryClient.invalidateQueries({ queryKey: ['suppliers'] });
          setModalOpen(false);
          setSelectedSupplier(null);
        }}
      />
    </div>
  );
};

export default SuppliersPage;
