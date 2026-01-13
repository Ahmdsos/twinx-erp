import React from 'react';
import { Link } from 'react-router-dom';
import { Form, Input, Button, Card, Typography, message, Result } from 'antd';
import { MailOutlined, ArrowLeftOutlined } from '@ant-design/icons';
import authApi from '../../../api/auth.api';

const { Title, Text } = Typography;

const ForgotPasswordPage: React.FC = () => {
  const [form] = Form.useForm();
  const [isLoading, setIsLoading] = React.useState(false);
  const [isSuccess, setIsSuccess] = React.useState(false);

  const onFinish = async (values: { email: string }) => {
    setIsLoading(true);
    try {
      await authApi.forgotPassword(values.email);
      setIsSuccess(true);
      message.success('تم إرسال رابط إعادة تعيين كلمة المرور');
    } catch (error: any) {
      message.error(error.response?.data?.message || 'حدث خطأ');
    } finally {
      setIsLoading(false);
    }
  };

  if (isSuccess) {
    return (
      <div
        style={{
          minHeight: '100vh',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          background: 'linear-gradient(135deg, #722ed1 0%, #9254de 100%)',
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
          <Result
            status="success"
            title="تم إرسال البريد الإلكتروني"
            subTitle="تفقد بريدك الإلكتروني للحصول على رابط إعادة تعيين كلمة المرور"
            extra={[
              <Link to="/login" key="login">
                <Button type="primary">العودة لتسجيل الدخول</Button>
              </Link>,
            ]}
          />
        </Card>
      </div>
    );
  }

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #722ed1 0%, #9254de 100%)',
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
              background: 'linear-gradient(135deg, #722ed1 0%, #9254de 100%)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
            }}
          >
            <MailOutlined style={{ fontSize: 32, color: '#fff' }} />
          </div>
          <Title level={2} style={{ marginBottom: 8 }}>
            نسيت كلمة المرور؟
          </Title>
          <Text type="secondary">
            أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة التعيين
          </Text>
        </div>

        <Form form={form} layout="vertical" onFinish={onFinish} size="large">
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

          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              loading={isLoading}
              block
              style={{
                borderRadius: 8,
                height: 48,
                background: 'linear-gradient(135deg, #722ed1 0%, #9254de 100%)',
                border: 'none',
              }}
            >
              إرسال رابط إعادة التعيين
            </Button>
          </Form.Item>
        </Form>

        <div style={{ textAlign: 'center' }}>
          <Link to="/login">
            <ArrowLeftOutlined /> العودة لتسجيل الدخول
          </Link>
        </div>
      </Card>
    </div>
  );
};

export default ForgotPasswordPage;
