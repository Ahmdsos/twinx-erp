import React, { useState } from 'react';
import { 
  Button, Modal, Form, Input, InputNumber, Select, Switch, 
  Upload, message, Space, Tag, Popconfirm, Tooltip, Card, 
  Dropdown, Badge, Image, Alert, Table
} from 'antd';
import type { UploadFile } from 'antd/es/upload';
import type { ColumnsType } from 'antd/es/table';
import type { MenuProps } from 'antd';
import { 
  PlusOutlined, EditOutlined, DeleteOutlined, 
  UploadOutlined, DownloadOutlined, BarcodeOutlined,
  InboxOutlined, CheckCircleOutlined
} from '@ant-design/icons';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import productsApi from '../../../api/products.api';
import categoriesApi from '../../../api/categories.api';
import brandsApi from '../../../api/brands.api';
import unitsApi from '../../../api/units.api';
import { PageHeader } from '../../../components/common';
import { formatCurrency } from '../../../store/settings.store';
import type { Product, Category, Brand, Unit } from '../../../types';

const { TextArea } = Input;
const { Option } = Select;
const { Dragger } = Upload;

const ProductsPage: React.FC = () => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isImportModalOpen, setIsImportModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [selectedRowKeys, setSelectedRowKeys] = useState<React.Key[]>([]);
  const [imageFile, setImageFile] = useState<UploadFile | null>(null);
  const [importFile, setImportFile] = useState<File | null>(null);
  const [searchText, setSearchText] = useState('');
  const [pagination, setPagination] = useState({ current: 1, pageSize: 20, total: 0 });
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  // Fetch products
  const { data: productsData, isLoading, refetch } = useQuery({
    queryKey: ['products', pagination.current, pagination.pageSize, searchText],
    queryFn: () => productsApi.getAll({
      page: pagination.current,
      per_page: pagination.pageSize,
      search: searchText,
    }),
  });

  // Fetch lookup data
  const { data: categoriesData } = useQuery({
    queryKey: ['categories', 'tree'],
    queryFn: () => categoriesApi.getTree(),
  });

  const { data: brandsData } = useQuery({
    queryKey: ['brands', 'all'],
    queryFn: () => brandsApi.getAllForDropdown(),
  });

  const { data: unitsData } = useQuery({
    queryKey: ['units', 'all'],
    queryFn: () => unitsApi.getAllForDropdown(),
  });

  // Update pagination from response
  React.useEffect(() => {
    if (productsData?.meta) {
      setPagination(prev => ({
        ...prev,
        total: productsData.meta.total,
      }));
    }
  }, [productsData]);

  // Mutations
  const createMutation = useMutation({
    mutationFn: productsApi.create,
    onSuccess: () => {
      message.success('تم إنشاء المنتج بنجاح');
      queryClient.invalidateQueries({ queryKey: ['products'] });
      refetch();
      handleCloseModal();
    },
    onError: (error: Error & { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Product> }) => productsApi.update(id, data),
    onSuccess: () => {
      message.success('تم تحديث المنتج بنجاح');
      queryClient.invalidateQueries({ queryKey: ['products'] });
      refetch();
      handleCloseModal();
    },
    onError: (error: Error & { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: productsApi.delete,
    onSuccess: () => {
      message.success('تم حذف المنتج بنجاح');
      queryClient.invalidateQueries({ queryKey: ['products'] });
      refetch();
    },
    onError: (error: Error & { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  // Bulk delete mutation
  const bulkDeleteMutation = useMutation({
    mutationFn: (ids: string[]) => productsApi.bulkDelete(ids),
    onSuccess: (data) => {
      message.success(`تم حذف ${data?.data?.deleted || 0} منتج بنجاح`);
      setSelectedRowKeys([]);
      queryClient.invalidateQueries({ queryKey: ['products'] });
      refetch();
    },
    onError: (error: Error & { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  // Import mutation
  const importMutation = useMutation({
    mutationFn: productsApi.import,
    onSuccess: (data) => {
      message.success(`تم استيراد ${data?.data?.imported || 0} منتج بنجاح`);
      if (data?.data?.errors && data.data.errors.length > 0) {
        Modal.warning({
          title: 'بعض الأخطاء حدثت',
          content: (
            <div>
              {data.data.errors.map((err: string, i: number) => (
                <div key={i}>{err}</div>
              ))}
            </div>
          ),
        });
      }
      setIsImportModalOpen(false);
      setImportFile(null);
      refetch();
    },
    onError: (error: Error & { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'حدث خطأ في الاستيراد');
    },
  });

  const handleOpenModal = (product?: Product) => {
    if (product) {
      setEditingProduct(product);
      form.setFieldsValue(product);
    } else {
      setEditingProduct(null);
      form.resetFields();
    }
    setImageFile(null);
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setEditingProduct(null);
    setImageFile(null);
    form.resetFields();
  };

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      if (editingProduct) {
        updateMutation.mutate({ id: editingProduct.id, data: values });
      } else {
        createMutation.mutate(values);
      }
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  // Handle export
  const handleExport = async () => {
    try {
      message.loading({ content: 'جارٍ التصدير...', key: 'export' });
      const blob = await productsApi.export('csv');
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `products_${new Date().toISOString().slice(0,10)}.csv`;
      a.click();
      window.URL.revokeObjectURL(url);
      message.success({ content: 'تم التصدير بنجاح', key: 'export' });
    } catch {
      message.error({ content: 'حدث خطأ في التصدير', key: 'export' });
    }
  };

  // Handle import
  const handleImport = () => {
    if (!importFile) {
      message.error('اختر ملف أولاً');
      return;
    }
    importMutation.mutate(importFile);
  };

  // Handle bulk delete
  const handleBulkDelete = () => {
    Modal.confirm({
      title: 'حذف المنتجات المحددة',
      content: `هل أنت متأكد من حذف ${selectedRowKeys.length} منتج؟`,
      okText: 'نعم، احذف',
      okType: 'danger',
      cancelText: 'إلغاء',
      onOk: () => {
        bulkDeleteMutation.mutate(selectedRowKeys as string[]);
      },
    });
  };

  // Bulk actions menu
  const bulkActionsMenu: MenuProps['items'] = [
    {
      key: 'delete',
      label: 'حذف المحددة',
      icon: <DeleteOutlined />,
      danger: true,
      onClick: handleBulkDelete,
    },
    {
      key: 'activate',
      label: 'تفعيل المحددة',
      icon: <CheckCircleOutlined />,
    },
    {
      key: 'deactivate',
      label: 'تعطيل المحددة',
      icon: <CheckCircleOutlined />,
    },
  ];

  // Image upload props
  const uploadProps = {
    maxCount: 1,
    beforeUpload: (file: File) => {
      const isImage = file.type.startsWith('image/');
      if (!isImage) {
        message.error('يمكنك رفع صور فقط!');
        return Upload.LIST_IGNORE;
      }
      const isLt2M = file.size / 1024 / 1024 < 2;
      if (!isLt2M) {
        message.error('حجم الصورة يجب أن يكون أقل من 2MB!');
        return Upload.LIST_IGNORE;
      }
      setImageFile({
        uid: '-1',
        name: file.name,
        status: 'done',
      } as UploadFile);
      return false;
    },
    onRemove: () => {
      setImageFile(null);
    },
    fileList: imageFile ? [imageFile] : [],
    listType: 'picture-card' as const,
  };

  const columns: ColumnsType<Product> = [
    {
      title: 'الصورة',
      dataIndex: 'image_url',
      key: 'image',
      width: 80,
      render: (url: string) => (
        url ? (
          <Image
            width={40}
            height={40}
            src={url}
            style={{ objectFit: 'cover', borderRadius: 4 }}
            fallback="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="
          />
        ) : (
          <div style={{ 
            width: 40, 
            height: 40, 
            background: '#f5f5f5', 
            borderRadius: 4,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}>
            <InboxOutlined style={{ color: '#999' }} />
          </div>
        )
      ),
    },
    {
      title: 'الكود',
      dataIndex: 'sku',
      key: 'sku',
      width: 110,
      ellipsis: true,
    },
    {
      title: 'الاسم',
      dataIndex: 'name',
      key: 'name',
      ellipsis: true,
      render: (text: string, record: Product) => (
        <Space direction="vertical" size={0}>
          <span style={{ fontWeight: 500 }}>{text}</span>
          {record.name_ar && (
            <span style={{ fontSize: 12, color: '#888' }}>{record.name_ar}</span>
          )}
        </Space>
      ),
    },
    {
      title: 'الباركود',
      dataIndex: 'barcode',
      key: 'barcode',
      width: 130,
      render: (val: string) => val ? (
        <Space>
          <BarcodeOutlined />
          <span style={{ fontSize: 11, fontFamily: 'monospace' }}>{val}</span>
        </Space>
      ) : '-',
    },
    {
      title: 'التصنيف',
      dataIndex: ['category', 'name'],
      key: 'category',
      width: 120,
    },
    {
      title: 'الماركة',
      dataIndex: ['brand', 'name'],
      key: 'brand',
      width: 100,
    },
    {
      title: 'سعر التكلفة',
      dataIndex: 'cost_price',
      key: 'cost_price',
      width: 110,
      render: (val: number) => formatCurrency(val),
    },
    {
      title: 'سعر البيع',
      dataIndex: 'retail_price',
      key: 'retail_price',
      width: 110,
      render: (val: number) => <strong>{formatCurrency(val)}</strong>,
    },
    {
      title: 'المخزون',
      dataIndex: 'stock_quantity',
      key: 'stock_quantity',
      width: 90,
      render: (val: number, record: Product) => {
        const level = record.reorder_level || 10;
        let color = 'green';
        let text = 'متوفر';
        if (val <= 0) { color = 'red'; text = 'نفد'; }
        else if (val <= level) { color = 'orange'; text = 'منخفض'; }
        return (
          <Tooltip title={text}>
            <Tag color={color}>{val}</Tag>
          </Tooltip>
        );
      },
    },
    {
      title: 'الحالة',
      dataIndex: 'is_active',
      key: 'is_active',
      width: 80,
      render: (val: boolean) => (
        <Badge 
          status={val ? 'success' : 'default'} 
          text={val ? 'نشط' : 'معطل'} 
        />
      ),
    },
    {
      title: 'الإجراءات',
      key: 'actions',
      width: 100,
      fixed: 'right',
      render: (_: unknown, record: Product) => (
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
            title="هل أنت متأكد من حذف هذا المنتج؟"
            description="سيتم حذف المنتج نهائياً"
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

  // Row selection config
  const rowSelection = {
    selectedRowKeys,
    onChange: (keys: React.Key[]) => setSelectedRowKeys(keys),
    preserveSelectedRowKeys: true,
  };

  // Handle table change
  const handleTableChange = (paginationConfig: { current?: number; pageSize?: number }) => {
    setPagination(prev => ({
      ...prev,
      current: paginationConfig.current || 1,
      pageSize: paginationConfig.pageSize || 20,
    }));
  };

  // Handle search
  const handleSearch = (value: string) => {
    setSearchText(value);
    setPagination(prev => ({ ...prev, current: 1 }));
  };

  return (
    <div style={{ padding: 24 }}>
      <PageHeader
        title="المنتجات"
        subtitle="إدارة كافة المنتجات والأصناف"
        breadcrumbs={[{ label: 'المخزون' }, { label: 'المنتجات' }]}
        extra={
          <Space>
            {selectedRowKeys.length > 0 && (
              <Dropdown menu={{ items: bulkActionsMenu }} placement="bottomRight">
                <Button>
                  إجراءات جماعية ({selectedRowKeys.length})
                </Button>
              </Dropdown>
            )}
            <Button 
              icon={<UploadOutlined />}
              onClick={() => setIsImportModalOpen(true)}
            >
              استيراد
            </Button>
            <Button 
              icon={<DownloadOutlined />}
              onClick={handleExport}
            >
              تصدير
            </Button>
            <Button 
              type="primary" 
              icon={<PlusOutlined />}
              onClick={() => handleOpenModal()}
            >
              إضافة منتج
            </Button>
          </Space>
        }
      />

      {/* Search */}
      <Card style={{ marginBottom: 16, borderRadius: 8 }}>
        <Input.Search
          placeholder="بحث بالاسم أو الكود أو الباركود..."
          allowClear
          enterButton="بحث"
          size="large"
          onSearch={handleSearch}
          style={{ maxWidth: 500 }}
        />
      </Card>

      {/* Table */}
      <Card style={{ borderRadius: 8 }}>
        <Table<Product>
          columns={columns}
          dataSource={productsData?.data || []}
          rowKey="id"
          rowSelection={rowSelection}
          loading={isLoading}
          pagination={{
            ...pagination,
            showSizeChanger: true,
            showQuickJumper: true,
            showTotal: (total) => `إجمالي ${total} منتج`,
          }}
          onChange={handleTableChange}
          scroll={{ x: 1400 }}
        />
      </Card>

      {/* Add/Edit Modal */}
      <Modal
        title={editingProduct ? 'تعديل منتج' : 'إضافة منتج جديد'}
        open={isModalOpen}
        onCancel={handleCloseModal}
        width={900}
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
            {editingProduct ? 'تحديث' : 'إنشاء'}
          </Button>,
        ]}
      >
        <Form
          form={form}
          layout="vertical"
          initialValues={{ 
            is_active: true, 
            track_stock: true,
            tax_rate: 15,
          }}
        >
          <Card title="المعلومات الأساسية" size="small" style={{ marginBottom: 16 }}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 16 }}>
              <Form.Item
                name="sku"
                label="الكود (SKU)"
                tooltip="اتركه فارغاً للتوليد التلقائي"
              >
                <Input placeholder="PRD-000001" />
              </Form.Item>

              <Form.Item 
                name="barcode" 
                label="الباركود"
                tooltip="اتركه فارغاً للتوليد التلقائي (EAN-13)"
              >
                <Input placeholder="2001234567890" suffix={<BarcodeOutlined />} />
              </Form.Item>

              <Form.Item name="is_active" label="الحالة" valuePropName="checked">
                <Switch checkedChildren="نشط" unCheckedChildren="معطل" />
              </Form.Item>

              <Form.Item
                name="name"
                label="الاسم (إنجليزي)"
                rules={[{ required: true, message: 'الاسم مطلوب' }]}
              >
                <Input placeholder="Product Name" />
              </Form.Item>

              <Form.Item name="name_ar" label="الاسم (عربي)">
                <Input placeholder="اسم المنتج" dir="rtl" />
              </Form.Item>

              <Form.Item name="category_id" label="التصنيف">
                <Select 
                  placeholder="اختر التصنيف" 
                  allowClear 
                  showSearch
                  optionFilterProp="children"
                >
                  {categoriesData?.data?.map((cat: Category) => (
                    <Option key={cat.id} value={cat.id}>{cat.name}</Option>
                  ))}
                </Select>
              </Form.Item>

              <Form.Item name="brand_id" label="الماركة">
                <Select 
                  placeholder="اختر الماركة" 
                  allowClear
                  showSearch
                  optionFilterProp="children"
                >
                  {brandsData?.data?.map((brand: Brand) => (
                    <Option key={brand.id} value={brand.id}>{brand.name}</Option>
                  ))}
                </Select>
              </Form.Item>

              <Form.Item name="unit_id" label="الوحدة">
                <Select 
                  placeholder="اختر الوحدة" 
                  allowClear
                  showSearch
                  optionFilterProp="children"
                >
                  {unitsData?.data?.map((unit: Unit) => (
                    <Option key={unit.id} value={unit.id}>
                      {unit.name} ({unit.short_name})
                    </Option>
                  ))}
                </Select>
              </Form.Item>

              <div>
                <Form.Item label="صورة المنتج" style={{ marginBottom: 0 }}>
                  <Upload {...uploadProps}>
                    {!imageFile && (
                      <div>
                        <PlusOutlined />
                        <div style={{ marginTop: 8 }}>رفع صورة</div>
                      </div>
                    )}
                  </Upload>
                </Form.Item>
              </div>
            </div>

            <Form.Item name="description" label="الوصف">
              <TextArea rows={2} placeholder="وصف المنتج..." />
            </Form.Item>
          </Card>

          <Card title="الأسعار (Multi-Tier Pricing)" size="small" style={{ marginBottom: 16 }}>
            <Alert 
              message="نظام التسعير متعدد المستويات يسمح بتحديد أسعار مختلفة لكل فئة من العملاء" 
              type="info" 
              showIcon 
              style={{ marginBottom: 16 }}
            />
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 16 }}>
              <Form.Item
                name="cost_price"
                label="سعر التكلفة"
                rules={[{ required: true, message: 'مطلوب' }]}
              >
                <InputNumber 
                  min={0} 
                  style={{ width: '100%' }} 
                  placeholder="0.00"
                  precision={2}
                />
              </Form.Item>

              <Form.Item
                name="selling_price"
                label="سعر البيع (التجزئة)"
                rules={[{ required: true, message: 'مطلوب' }]}
              >
                <InputNumber 
                  min={0} 
                  style={{ width: '100%' }} 
                  placeholder="0.00"
                  precision={2}
                />
              </Form.Item>

              <Form.Item name="semi_wholesale_price" label="نصف جملة">
                <InputNumber min={0} style={{ width: '100%' }} precision={2} />
              </Form.Item>

              <Form.Item name="quarter_wholesale_price" label="ربع جملة">
                <InputNumber min={0} style={{ width: '100%' }} precision={2} />
              </Form.Item>

              <Form.Item name="wholesale_price" label="جملة">
                <InputNumber min={0} style={{ width: '100%' }} precision={2} />
              </Form.Item>

              <Form.Item name="distributor_price" label="موزع">
                <InputNumber min={0} style={{ width: '100%' }} precision={2} />
              </Form.Item>

              <Form.Item name="minimum_price" label="أقل سعر مسموح">
                <InputNumber min={0} style={{ width: '100%' }} precision={2} />
              </Form.Item>

              <Form.Item name="tax_rate" label="نسبة الضريبة %">
                <InputNumber min={0} max={100} style={{ width: '100%' }} />
              </Form.Item>
            </div>
          </Card>

          <Card title="المخزون" size="small">
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 16 }}>
              <Form.Item name="stock_quantity" label="الكمية الحالية">
                <InputNumber min={0} style={{ width: '100%' }} />
              </Form.Item>

              <Form.Item name="reorder_level" label="حد إعادة الطلب">
                <InputNumber min={0} style={{ width: '100%' }} placeholder="10" />
              </Form.Item>

              <Form.Item name="track_stock" label="تتبع المخزون" valuePropName="checked">
                <Switch checkedChildren="نعم" unCheckedChildren="لا" />
              </Form.Item>
            </div>
          </Card>
        </Form>
      </Modal>

      {/* Import Modal */}
      <Modal
        title="استيراد منتجات من ملف CSV"
        open={isImportModalOpen}
        onCancel={() => {
          setIsImportModalOpen(false);
          setImportFile(null);
        }}
        footer={[
          <Button key="cancel" onClick={() => setIsImportModalOpen(false)}>
            إلغاء
          </Button>,
          <Button 
            key="import" 
            type="primary" 
            onClick={handleImport}
            loading={importMutation.isPending}
            disabled={!importFile}
          >
            استيراد
          </Button>,
        ]}
      >
        <Alert 
          message="تنسيق الملف المطلوب"
          description="CSV بالأعمدة: SKU, الاسم (إنجليزي), الاسم (عربي), الباركود, سعر التكلفة, سعر البيع, الكمية"
          type="info"
          showIcon
          style={{ marginBottom: 16 }}
        />
        
        <Dragger
          maxCount={1}
          accept=".csv"
          beforeUpload={(file) => {
            setImportFile(file);
            return false;
          }}
          onRemove={() => setImportFile(null)}
          fileList={importFile ? [{
            uid: '-1',
            name: importFile.name,
            status: 'done' as const,
          }] : []}
        >
          <p className="ant-upload-drag-icon">
            <InboxOutlined />
          </p>
          <p className="ant-upload-text">اضغط أو اسحب ملف CSV هنا</p>
          <p className="ant-upload-hint">يدعم ملفات CSV فقط (الحد الأقصى 5MB)</p>
        </Dragger>
      </Modal>
    </div>
  );
};

export default ProductsPage;
