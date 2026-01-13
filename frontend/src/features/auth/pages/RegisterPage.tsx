import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Form, Input, Button, Card, Typography, message, Divider } from 'antd';
import { UserOutlined, LockOutlined, MailOutlined } from '@ant-design/icons';
import { useAuthStore } from '../../../store/auth.store';

const { Title, Text } = Typography;

const RegisterPage: React.FC = () => {
  const navigate = useNavigate();
  const { register, isLoading, error } = useAuthStore();
  const [form] = Form.useForm();

  const onFinish = async (values: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) => {
    try {
      await register(values);
      message.success('تم إنشاء الحساب بنجاح');
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
        background: 'linear-gradient(135deg, #52c41a 0%, #95de64 100%)',
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
              background: 'linear-gradient(135deg, #52c41a 0%, #95de64 100%)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
            }}
          >
            <UserOutlined style={{ fontSize: 32, color: '#fff' }} />
          </div>
          <Title level={2} style={{ marginBottom: 8 }}>
            إنشاء حساب جديد
          </Title>
          <Text type="secondary">أنشئ حسابك للبدء في استخدام النظام</Text>
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

        <Form form={form} layout="vertical" onFinish={onFinish} size="large">
          <Form.Item
            name="name"
            rules={[{ required: true, message: 'الاسم مطلوب' }]}
          >
            <Input
              prefix={<UserOutlined />}
              placeholder="الاسم الكامل"
              style={{ borderRadius: 8 }}
            />
          </Form.Item>

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
            rules={[
              { required: true, message: 'كلمة المرور مطلوبة' },
              { min: 8, message: 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' },
            ]}
          >
            <Input.Password
              prefix={<LockOutlined />}
              placeholder="كلمة المرور"
              style={{ borderRadius: 8 }}
            />
          </Form.Item>

          <Form.Item
            name="password_confirmation"
            dependencies={['password']}
            rules={[
              { required: true, message: 'تأكيد كلمة المرور مطلوب' },
              ({ getFieldValue }) => ({
                validator(_, value) {
                  if (!value || getFieldValue('password') === value) {
                    return Promise.resolve();
                  }
                  return Promise.reject(new Error('كلمتا المرور غير متطابقتين'));
                },
              }),
            ]}
          >
            <Input.Password
              prefix={<LockOutlined />}
              placeholder="تأكيد كلمة المرور"
              style={{ borderRadius: 8 }}
            />
          </Form.Item>

          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              loading={isLoading}
              block
              style={{ 
                borderRadius: 8, 
                height: 48,
                background: 'linear-gradient(135deg, #52c41a 0%, #95de64 100%)',
                border: 'none',
              }}
            >
              إنشاء الحساب
            </Button>
          </Form.Item>
        </Form>

        <Divider>أو</Divider>

        <div style={{ textAlign: 'center' }}>
          <Text>لديك حساب بالفعل؟ </Text>
          <Link to="/login">تسجيل الدخول</Link>
        </div>
      </Card>
    </div>
  );
};

export default RegisterPage;
