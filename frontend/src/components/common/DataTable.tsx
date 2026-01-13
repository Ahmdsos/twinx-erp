import React from 'react';
import { Table, type TableProps, Card, Input, Space, Button } from 'antd';
import { SearchOutlined, ReloadOutlined, PlusOutlined } from '@ant-design/icons';

export interface DataTableProps<T> extends Omit<TableProps<T>, 'title'> {
  title?: string;
  searchPlaceholder?: string;
  onSearch?: (value: string) => void;
  onRefresh?: () => void;
  onAdd?: () => void;
  addButtonText?: string;
  showSearch?: boolean;
  showRefresh?: boolean;
  showAdd?: boolean;
  extra?: React.ReactNode;
}

function DataTable<T extends object>({
  title,
  searchPlaceholder = 'بحث...',
  onSearch,
  onRefresh,
  onAdd,
  addButtonText = 'إضافة',
  showSearch = true,
  showRefresh = true,
  showAdd = true,
  extra,
  ...tableProps
}: DataTableProps<T>) {
  return (
    <Card
      title={title}
      style={{ borderRadius: 12 }}
      extra={
        <Space>
          {showSearch && onSearch && (
            <Input
              prefix={<SearchOutlined />}
              placeholder={searchPlaceholder}
              allowClear
              onChange={(e) => onSearch(e.target.value)}
              style={{ width: 200 }}
            />
          )}
          {showRefresh && onRefresh && (
            <Button icon={<ReloadOutlined />} onClick={onRefresh} />
          )}
          {showAdd && onAdd && (
            <Button type="primary" icon={<PlusOutlined />} onClick={onAdd}>
              {addButtonText}
            </Button>
          )}
          {extra}
        </Space>
      }
    >
      <Table<T>
        {...tableProps}
        style={{ ...tableProps.style }}
        scroll={{ x: 'max-content', ...tableProps.scroll }}
      />
    </Card>
  );
}

export default DataTable;
