import React from 'react';
import { Card, Statistic, Space, Typography } from 'antd';
import { ArrowUpOutlined, ArrowDownOutlined, MinusOutlined } from '@ant-design/icons';

const { Text } = Typography;

export interface StatsCardProps {
  title: string;
  value: number | string;
  prefix?: React.ReactNode;
  suffix?: string;
  change?: number;
  changeLabel?: string;
  trend?: 'up' | 'down' | 'neutral';
  color?: 'primary' | 'success' | 'warning' | 'error';
  loading?: boolean;
}

const colorMap = {
  primary: 'linear-gradient(135deg, #1890ff 0%, #40a9ff 100%)',
  success: 'linear-gradient(135deg, #52c41a 0%, #95de64 100%)',
  warning: 'linear-gradient(135deg, #faad14 0%, #ffc53d 100%)',
  error: 'linear-gradient(135deg, #ff4d4f 0%, #ff7875 100%)',
};

const StatsCard: React.FC<StatsCardProps> = ({
  title,
  value,
  prefix,
  suffix,
  change,
  changeLabel,
  trend = 'neutral',
  color = 'primary',
  loading = false,
}) => {
  const getTrendIcon = () => {
    switch (trend) {
      case 'up':
        return <ArrowUpOutlined style={{ color: '#52c41a' }} />;
      case 'down':
        return <ArrowDownOutlined style={{ color: '#ff4d4f' }} />;
      default:
        return <MinusOutlined style={{ color: '#999' }} />;
    }
  };

  return (
    <Card
      style={{
        background: colorMap[color],
        border: 'none',
        borderRadius: 12,
      }}
      loading={loading}
    >
      <Statistic
        title={<Text style={{ color: 'rgba(255,255,255,0.8)' }}>{title}</Text>}
        value={value}
        prefix={prefix}
        suffix={suffix}
        valueStyle={{ color: '#fff', fontSize: 28 }}
      />
      {(change !== undefined || changeLabel) && (
        <div style={{ marginTop: 8 }}>
          <Space>
            {getTrendIcon()}
            <Text style={{ color: 'rgba(255,255,255,0.8)', fontSize: 12 }}>
              {change !== undefined && `${Math.abs(change)}%`} {changeLabel}
            </Text>
          </Space>
        </div>
      )}
    </Card>
  );
};

export default StatsCard;
