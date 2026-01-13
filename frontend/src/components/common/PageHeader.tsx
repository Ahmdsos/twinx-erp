import React from 'react';
import { Typography, Space, Breadcrumb } from 'antd';
import { Link } from 'react-router-dom';
import { HomeOutlined } from '@ant-design/icons';

const { Title, Text } = Typography;

export interface BreadcrumbItem {
  label: string;
  path?: string;
}

export interface PageHeaderProps {
  title: string;
  subtitle?: string;
  breadcrumbs?: BreadcrumbItem[];
  extra?: React.ReactNode;
  showHome?: boolean;
}

const PageHeader: React.FC<PageHeaderProps> = ({
  title,
  subtitle,
  breadcrumbs = [],
  extra,
  showHome = true,
}) => {
  const breadcrumbItems = [
    ...(showHome
      ? [
          {
            title: (
              <Link to="/">
                <HomeOutlined /> الرئيسية
              </Link>
            ),
          },
        ]
      : []),
    ...breadcrumbs.map((item) => ({
      title: item.path ? <Link to={item.path}>{item.label}</Link> : item.label,
    })),
  ];

  return (
    <div style={{ marginBottom: 24 }}>
      {breadcrumbItems.length > 0 && (
        <Breadcrumb items={breadcrumbItems} style={{ marginBottom: 12 }} />
      )}
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          flexWrap: 'wrap',
          gap: 16,
        }}
      >
        <Space direction="vertical" size={0}>
          <Title level={3} style={{ margin: 0 }}>
            {title}
          </Title>
          {subtitle && <Text type="secondary">{subtitle}</Text>}
        </Space>
        {extra && <div>{extra}</div>}
      </div>
    </div>
  );
};

export default PageHeader;
