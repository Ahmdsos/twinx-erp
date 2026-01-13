import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ProTable, type ProColumns } from '@ant-design/pro-components';
import { Button, Tag, Space, Modal, Badge, Dropdown, message } from 'antd';
import {
  PlusOutlined,
  DeleteOutlined,
  FileTextOutlined,
  ExclamationCircleOutlined,
  MoreOutlined,
  EditOutlined,
} from '@ant-design/icons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { customersApi } from '../../../api/customers.api';
import type { Customer } from '../../../types/customer.types';
import CustomerForm from '../components/CustomerForm';

const CustomersPage: React.FC = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [modalOpen, setModalOpen] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);
  const [selectedRows, setSelectedRows] = useState<Customer[]>([]);

  const deleteMutation = useMutation({
    mutationFn: customersApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      message.success('تم حذف العميل بنجاح');
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ أثناء الحذف');
    },
  });

  const bulkDeleteMutation = useMutation({
    mutationFn: customersApi.bulkDelete,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      setSelectedRows([]);
      message.success(`تم حذف ${data.data.deleted} عميل بنجاح`);
      if (data.data.errors.length > 0) {
        Modal.warning({
          title: 'تحذيرات',
          content: data.data.errors.join('\n'),
        });
      }
    },
  });

  const columns: ProColumns<Customer>[] = [
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
      title: 'البريد الإلكتروني',
      dataIndex: 'email',
      width: 200,
      hideInTable: true,
    },
    {
      title: 'المدينة',
      dataIndex: 'city',
      width: 120,
      hideInTable: true,
    },
    {
      title: 'قائمة الأسعار',
      dataIndex: ['price_list', 'name'],
      width: 150,
      hideInSearch: true,
    },
    {
      title: 'الرصيد المستحق',
      dataIndex: 'total_balance',
      width: 150,
      hideInSearch: true,
      valueType: 'money',
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
      title: 'حد الائتمان',
      dataIndex: 'credit_limit',
      width: 150,
      hideInSearch: true,
      render: (_, record) => {
        if (record.credit_limit === 0) {
          return <Tag>لا يوجد</Tag>;
        }
        
        let status: 'success' | 'warning' | 'error' = 'success';
        if (record.credit_status === 'exceeded') status = 'error';
        else if (record.credit_status === 'warning') status = 'warning';
        
        return (
          <Space direction="vertical" size={0}>
            <span>{record.credit_limit.toFixed(2)} ر.س</span>
            <Badge
              status={status}
              text={`متاح: ${record.available_credit.toFixed(2)}`}
            />
          </Space>
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
                  setSelectedCustomer(record);
                  setModalOpen(true);
                },
              },
              {
                key: 'statement',
                icon: <FileTextOutlined />,
                label: 'كشف حساب',
                onClick: () => navigate(`/customers/${record.id}/statement`),
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
                    content: `هل تريد حذف العميل "${record.name}"؟`,
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
      <ProTable<Customer>
        columns={columns}
        request={async (params) => {
          const response = await customersApi.list({
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
          showQuickJumper: true,
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
                  content: `هل تريد حذف ${selectedRows.length} عميل؟`,
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
              setSelectedCustomer(null);
              setModalOpen(true);
            }}
          >
            إضافة عميل جديد
          </Button>,
        ]}
        headerTitle="إدارة العملاء"
        scroll={{ x: 1500 }}
      />

      <CustomerForm
        open={modalOpen}
        customer={selectedCustomer}
        onClose={() => {
          setModalOpen(false);
          setSelectedCustomer(null);
        }}
        onSuccess={() => {
          queryClient.invalidateQueries({ queryKey: ['customers'] });
          setModalOpen(false);
          setSelectedCustomer(null);
        }}
      />
    </div>
  );
};

export default CustomersPage;
