import React, { useState } from 'react';
import { 
  Button, Modal, Form, Input, Tree, Card, Space, message, 
  Popconfirm, Empty, Spin, Switch, Alert
} from 'antd';
import type { TreeDataNode, TreeProps } from 'antd';
import { 
  PlusOutlined, EditOutlined, DeleteOutlined, 
  FolderOutlined, FolderOpenOutlined, DragOutlined
} from '@ant-design/icons';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import categoriesApi from '../../../api/categories.api';
import { PageHeader } from '../../../components/common';
import type { CategoryTreeItem } from '../../../api/categories.api';

const CategoriesPage: React.FC = () => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<CategoryTreeItem | null>(null);
  const [parentId, setParentId] = useState<string | null>(null);
  const [draggingEnabled, setDraggingEnabled] = useState(false);
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  // Fetch categories tree
  const { data: categoriesData, isLoading } = useQuery({
    queryKey: ['categories', 'tree'],
    queryFn: () => categoriesApi.getTree(),
  });

  // Mutations
  const createMutation = useMutation({
    mutationFn: categoriesApi.create,
    onSuccess: () => {
      message.success('تم إنشاء التصنيف بنجاح');
      queryClient.invalidateQueries({ queryKey: ['categories'] });
      handleCloseModal();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: any }) => categoriesApi.update(id, data),
    onSuccess: () => {
      message.success('تم تحديث التصنيف بنجاح');
      queryClient.invalidateQueries({ queryKey: ['categories'] });
      handleCloseModal();
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: categoriesApi.delete,
    onSuccess: () => {
      message.success('تم حذف التصنيف بنجاح');
      queryClient.invalidateQueries({ queryKey: ['categories'] });
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ');
    },
  });

  // Reorder mutation
  const reorderMutation = useMutation({
    mutationFn: categoriesApi.reorder,
    onSuccess: () => {
      message.success('تم إعادة ترتيب التصنيفات بنجاح');
      queryClient.invalidateQueries({ queryKey: ['categories'] });
    },
    onError: (error: any) => {
      message.error(error.response?.data?.message || 'حدث خطأ في الترتيب');
    },
  });

  // Convert to tree data with drag handle
  const convertToTreeData = (categories: CategoryTreeItem[]): TreeDataNode[] => {
    return categories.map((cat) => ({
      key: cat.id,
      title: (
        <Space>
          {draggingEnabled && <DragOutlined style={{ cursor: 'grab', color: '#999' }} />}
          <span>{cat.name}</span>
          {cat.name_ar && <span style={{ color: '#888' }}>({cat.name_ar})</span>}
          {!draggingEnabled && (
            <Space size={4}>
              <Button 
                type="text" 
                size="small" 
                icon={<EditOutlined />}
                onClick={(e) => {
                  e.stopPropagation();
                  handleEdit(cat);
                }}
              />
              <Button 
                type="text" 
                size="small" 
                icon={<PlusOutlined />}
                onClick={(e) => {
                  e.stopPropagation();
                  handleAddChild(cat.id);
                }}
              />
              <Popconfirm
                title="هل أنت متأكد من حذف هذا التصنيف؟"
                description="سيتم حذف جميع التصنيفات الفرعية أيضاً"
                onConfirm={(e) => {
                  e?.stopPropagation();
                  deleteMutation.mutate(cat.id);
                }}
                onCancel={(e) => e?.stopPropagation()}
              >
                <Button 
                  type="text" 
                  size="small" 
                  danger
                  icon={<DeleteOutlined />}
                  onClick={(e) => e.stopPropagation()}
                />
              </Popconfirm>
            </Space>
          )}
        </Space>
      ),
      icon: ({ expanded }: any) => expanded ? <FolderOpenOutlined /> : <FolderOutlined />,
      children: cat.children ? convertToTreeData(cat.children) : undefined,
    }));
  };

  // Handle drag and drop
  const handleDrop: TreeProps['onDrop'] = (info) => {
    const dropKey = info.node.key as string;
    const dragKey = info.dragNode.key as string;
    const dropPos = info.node.pos.split('-');
    const dropPosition = info.dropPosition - Number(dropPos[dropPos.length - 1]);

    // Prepare reorder data
    const items: { id: string; parent_id: string | null; sort_order: number }[] = [];

    // Build the new tree structure
    const loop = (
      data: TreeDataNode[],
      key: string,
      callback: (item: TreeDataNode, index: number, arr: TreeDataNode[]) => void
    ) => {
      for (let i = 0; i < data.length; i++) {
        if (data[i].key === key) {
          return callback(data[i], i, data);
        }
        if (data[i].children) {
          loop(data[i].children!, key, callback);
        }
      }
    };

    const data = JSON.parse(JSON.stringify(convertToTreeData(categoriesData?.data || [])));
    let dragObj: TreeDataNode;

    loop(data, dragKey, (item, index, arr) => {
      arr.splice(index, 1);
      dragObj = item;
    });

    if (!info.dropToGap) {
      // Drop inside the node - make it a child
      loop(data, dropKey, (item) => {
        item.children = item.children || [];
        item.children.unshift(dragObj!);
      });
    } else if (dropPosition === -1) {
      // Drop before the node
      loop(data, dropKey, (_, index, arr) => {
        arr.splice(index, 0, dragObj!);
      });
    } else {
      // Drop after the node
      loop(data, dropKey, (_, index, arr) => {
        arr.splice(index + 1, 0, dragObj!);
      });
    }

    // Build items for API
    const buildItems = (nodes: TreeDataNode[], parentId: string | null = null) => {
      nodes.forEach((node, index) => {
        items.push({
          id: node.key as string,
          parent_id: parentId,
          sort_order: index + 1,
        });
        if (node.children) {
          buildItems(node.children, node.key as string);
        }
      });
    };

    buildItems(data);

    // Call API to persist the new order
    reorderMutation.mutate(items);
  };

  const handleAddChild = (parentIdVal: string) => {
    setParentId(parentIdVal);
    setEditingCategory(null);
    form.resetFields();
    setIsModalOpen(true);
  };

  const handleEdit = (category: CategoryTreeItem) => {
    setEditingCategory(category);
    setParentId(category.parent_id || null);
    form.setFieldsValue(category);
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setEditingCategory(null);
    setParentId(null);
    form.resetFields();
  };

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      if (parentId) {
        values.parent_id = parentId;
      }
      if (editingCategory) {
        updateMutation.mutate({ id: editingCategory.id, data: values });
      } else {
        createMutation.mutate(values);
      }
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  const treeData = categoriesData?.data ? convertToTreeData(categoriesData.data) : [];

  return (
    <div style={{ padding: 24 }}>
      <PageHeader
        title="التصنيفات"
        subtitle="إدارة تصنيفات المنتجات بشكل هرمي"
        breadcrumbs={[{ label: 'المخزون' }, { label: 'التصنيفات' }]}
        extra={
          <Space>
            <Button
              icon={<DragOutlined />}
              type={draggingEnabled ? 'primary' : 'default'}
              onClick={() => setDraggingEnabled(!draggingEnabled)}
            >
              {draggingEnabled ? 'إيقاف الترتيب' : 'إعادة ترتيب'}
            </Button>
            <Button 
              type="primary" 
              icon={<PlusOutlined />}
              onClick={() => {
                setParentId(null);
                setEditingCategory(null);
                form.resetFields();
                setIsModalOpen(true);
              }}
            >
              إضافة تصنيف رئيسي
            </Button>
          </Space>
        }
      />

      {draggingEnabled && (
        <Alert
          message="وضع الترتيب نشط"
          description="اسحب التصنيفات وأفلتها لتغيير ترتيبها. يمكنك إفلات التصنيف داخل تصنيف آخر لجعله تصنيفاً فرعياً."
          type="info"
          showIcon
          style={{ marginBottom: 16 }}
        />
      )}

      <Card style={{ borderRadius: 12 }}>
        {isLoading ? (
          <div style={{ textAlign: 'center', padding: 48 }}>
            <Spin size="large" />
          </div>
        ) : treeData.length > 0 ? (
          <Tree
            showLine
            showIcon
            defaultExpandAll
            draggable={draggingEnabled}
            blockNode
            onDrop={handleDrop}
            treeData={treeData}
            style={{ fontSize: 14 }}
          />
        ) : (
          <Empty description="لا توجد تصنيفات" />
        )}
      </Card>

      {/* Add/Edit Modal */}
      <Modal
        title={editingCategory ? 'تعديل التصنيف' : 'إضافة تصنيف جديد'}
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
            {editingCategory ? 'تحديث' : 'إنشاء'}
          </Button>,
        ]}
      >
        <Form form={form} layout="vertical" initialValues={{ is_active: true }}>
          <Form.Item
            name="name"
            label="الاسم (إنجليزي)"
            rules={[{ required: true, message: 'الاسم مطلوب' }]}
          >
            <Input placeholder="Category Name" />
          </Form.Item>

          <Form.Item name="name_ar" label="الاسم (عربي)">
            <Input placeholder="اسم التصنيف" dir="rtl" />
          </Form.Item>

          <Form.Item name="slug" label="الـ Slug">
            <Input placeholder="category-slug (يتم التوليد تلقائياً)" />
          </Form.Item>

          <Form.Item name="description" label="الوصف">
            <Input.TextArea rows={2} placeholder="وصف التصنيف..." />
          </Form.Item>

          <Form.Item name="is_active" label="الحالة" valuePropName="checked">
            <Switch checkedChildren="نشط" unCheckedChildren="معطل" />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
};

export default CategoriesPage;
