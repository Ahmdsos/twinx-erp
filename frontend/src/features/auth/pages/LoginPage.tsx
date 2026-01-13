import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Form, Input, Button, Card, Typography, message, Divider, Space } from 'antd';
import { UserOutlined, LockOutlined, MailOutlined } from '@ant-design/icons';
import { useAuthStore } from '../../../store/auth.store';

const { Title, Text } = Typography;

const LoginPage: React.FC = () => {
  const navigate = useNavigate();
  const { login, isLoading, error, clearError } = useAuthStore();
  const [form] = Form.useForm();

  const onFinish = async (values: { email: string; password: string; remember: boolean }) => {
    try {
      await login(values);
      message.success('تم تسجيل الدخول بنجاح');
      navigate('/');
    } catch {
      // Error is handled in store
    }
  };

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #1890ff 0%, #40a9ff 100%)',
        padding: 24,
      }}
    >
      <Card
        style={{
          width: '100%',
          maxWidth: 420,
          borderRadius: 16,
          boxShadow: '0 10px 40px rgba(0,0,0,0.2)',
        }}
      >
        <div style={{ textAlign: 'center', marginBottom: 32 }}>
          <div
            style={{
              width: 64,
              height: 64,
              borderRadius: 16,
              background: 'linear-gradient(135deg, #1890ff 0%, #40a9ff 100%)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
            }}
          >
            <UserOutlined style={{ fontSize: 32, color: '#fff' }} />
          </div>
          <Title level={2} style={{ marginBottom: 8 }}>
            مرحباً بك
          </Title>
          <Text type="secondary">سجل دخولك للوصول إلى لوحة التحكم</Text>
        </div>

        {error && (
          <div
            style={{
              padding: '12px 16px',
              background: '#fff2f0',
              border: '1px solid #ffccc7',
              borderRadius: 8,
              marginBottom: 24,
              color: '#ff4d4f',
            }}
          >
            {error}
          </div>
        )}

        <Form
          form={form}
          layout="vertical"
          onFinish={onFinish}
          initialValues={{ remember: true }}
          size="large"
        >
          <Form.Item
            name="email"
            rules={[
              { required: true, message: 'البريد الإلكتروني مطلوب' },
              { type: 'email', message: 'البريد الإلكتروني غير صحيح' },
            ]}
          >
            <Input
              prefix={<MailOutlined />}
              placeholder="البريد الإلكتروني"
              style={{ borderRadius: 8 }}
            />
          </Form.Item>

          <Form.Item
            name="password"
            rules={[{ required: true, message: 'كلمة المرور مطلوبة' }]}
          >
            <Input.Password
              prefix={<LockOutlined />}
              placeholder="كلمة المرور"
              style={{ borderRadius: 8 }}
            />
          </Form.Item>

          <Form.Item>
            <Space style={{ width: '100%', justifyContent: 'space-between' }}>
              <Form.Item name="remember" valuePropName="checked" noStyle>
                <Button type="link" style={{ padding: 0 }}>تذكرني</Button>
              </Form.Item>
              <Link to="/forgot-password">نسيت كلمة المرور؟</Link>
            </Space>
          </Form.Item>

          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              loading={isLoading}
              block
              style={{ borderRadius: 8, height: 48 }}
            >
              تسجيل الدخول
            </Button>
          </Form.Item>
        </Form>

        <Divider>أو</Divider>

        <div style={{ textAlign: 'center' }}>
          <Text>ليس لديك حساب؟ </Text>
          <Link to="/register">إنشاء حساب جديد</Link>
        </div>
      </Card>
    </div>
  );
};

export default LoginPage;
