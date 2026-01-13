import dayjs from 'dayjs';
import 'dayjs/locale/ar';

dayjs.locale('ar');

/**
 * Format currency with symbol
 */
export const formatCurrency = (
  amount: number,
  currencyCode: string = 'SAR',
  locale: string = 'ar-SA'
): string => {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currencyCode,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
};

/**
 * Format number with commas
 */
export const formatNumber = (
  num: number,
  locale: string = 'ar-SA'
): string => {
  return new Intl.NumberFormat(locale).format(num);
};

/**
 * Format percentage
 */
export const formatPercentage = (
  value: number,
  decimals: number = 2
): string => {
  return `${value.toFixed(decimals)}%`;
};

/**
 * Format date to Arabic format
 */
export const formatDate = (
  date: string | Date,
  format: string = 'YYYY-MM-DD'
): string => {
  return dayjs(date).format(format);
};

/**
 * Format date to relative time (e.g., "منذ 5 دقائق")
 */
export const formatRelativeTime = (date: string | Date): string => {
  const now = dayjs();
  const then = dayjs(date);
  const diffMinutes = now.diff(then, 'minute');
  const diffHours = now.diff(then, 'hour');
  const diffDays = now.diff(then, 'day');

  if (diffMinutes < 1) return 'الآن';
  if (diffMinutes < 60) return `منذ ${diffMinutes} دقيقة`;
  if (diffHours < 24) return `منذ ${diffHours} ساعة`;
  if (diffDays < 7) return `منذ ${diffDays} يوم`;
  return formatDate(date);
};

/**
 * Format phone number
 */
export const formatPhone = (phone: string): string => {
  // Remove non-digits
  const digits = phone.replace(/\D/g, '');
  
  // Format Saudi phone
  if (digits.startsWith('966')) {
    return `+${digits.slice(0, 3)} ${digits.slice(3, 5)} ${digits.slice(5, 8)} ${digits.slice(8)}`;
  }
  
  // Format Egyptian phone
  if (digits.startsWith('20')) {
    return `+${digits.slice(0, 2)} ${digits.slice(2, 4)} ${digits.slice(4, 8)} ${digits.slice(8)}`;
  }
  
  return phone;
};

/**
 * Format file size
 */
export const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

/**
 * Truncate text with ellipsis
 */
export const truncateText = (
  text: string,
  maxLength: number,
  ellipsis: string = '...'
): string => {
  if (text.length <= maxLength) return text;
  return text.slice(0, maxLength - ellipsis.length) + ellipsis;
};

/**
 * Capitalize first letter
 */
export const capitalize = (text: string): string => {
  return text.charAt(0).toUpperCase() + text.slice(1);
};

/**
 * Generate initials from name
 */
export const getInitials = (name: string, count: number = 2): string => {
  return name
    .split(' ')
    .slice(0, count)
    .map((n) => n.charAt(0))
    .join('')
    .toUpperCase();
};
