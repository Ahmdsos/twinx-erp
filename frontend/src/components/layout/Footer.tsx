import React from 'react';
import { Layout, Typography, Space } from 'antd';
import { HeartFilled } from '@ant-design/icons';

const { Footer: AntFooter } = Layout;
const { Text, Link } = Typography;

const Footer: React.FC = () => {
  const currentYear = new Date().getFullYear();

  return (
    <AntFooter
      style={{
        textAlign: 'center',
        background: 'transparent',
        padding: '16px 24px',
      }}
    >
      <Space direction="vertical" size={4}>
        <Text type="secondary">
          © {currentYear} TWINX ERP. جميع الحقوق محفوظة.
        </Text>
        <Text type="secondary" style={{ fontSize: 12 }}>
          صنع بـ <HeartFilled style={{ color: '#ff4d4f' }} /> في مصر
        </Text>
      </Space>
    </AntFooter>
  );
};

export default Footer;
