/**
 * Validate email format
 */
export const isValidEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

/**
 * Validate Saudi phone number
 */
export const isValidSaudiPhone = (phone: string): boolean => {
  const digits = phone.replace(/\D/g, '');
  // Saudi numbers: 05xxxxxxxx or 966xxxxxxxxx
  return /^(05|5|966)\d{8,9}$/.test(digits);
};

/**
 * Validate Egyptian phone number
 */
export const isValidEgyptianPhone = (phone: string): boolean => {
  const digits = phone.replace(/\D/g, '');
  // Egyptian numbers: 01xxxxxxxxx or 201xxxxxxxxxx
  return /^(01|201)\d{9,10}$/.test(digits);
};

/**
 * Validate phone number (Saudi or Egyptian)
 */
export const isValidPhone = (phone: string): boolean => {
  return isValidSaudiPhone(phone) || isValidEgyptianPhone(phone);
};

/**
 * Validate Saudi VAT number (15 digits starting with 3)
 */
export const isValidSaudiVAT = (vat: string): boolean => {
  const digits = vat.replace(/\D/g, '');
  return /^3\d{14}$/.test(digits);
};

/**
 * Validate Saudi CR number (10 digits)
 */
export const isValidSaudiCR = (cr: string): boolean => {
  const digits = cr.replace(/\D/g, '');
  return /^\d{10}$/.test(digits);
};

/**
 * Validate password strength
 */
export const isStrongPassword = (password: string): {
  isValid: boolean;
  errors: string[];
} => {
  const errors: string[] = [];

  if (password.length < 8) {
    errors.push('يجب أن تكون كلمة المرور 8 أحرف على الأقل');
  }
  if (!/[A-Z]/.test(password)) {
    errors.push('يجب أن تحتوي على حرف كبير واحد على الأقل');
  }
  if (!/[a-z]/.test(password)) {
    errors.push('يجب أن تحتوي على حرف صغير واحد على الأقل');
  }
  if (!/[0-9]/.test(password)) {
    errors.push('يجب أن تحتوي على رقم واحد على الأقل');
  }

  return {
    isValid: errors.length === 0,
    errors,
  };
};

/**
 * Validate required field
 */
export const isRequired = (value: any): boolean => {
  if (value === null || value === undefined) return false;
  if (typeof value === 'string') return value.trim().length > 0;
  if (Array.isArray(value)) return value.length > 0;
  return true;
};

/**
 * Validate min length
 */
export const hasMinLength = (value: string, min: number): boolean => {
  return value.length >= min;
};

/**
 * Validate max length
 */
export const hasMaxLength = (value: string, max: number): boolean => {
  return value.length <= max;
};

/**
 * Validate number range
 */
export const isInRange = (value: number, min: number, max: number): boolean => {
  return value >= min && value <= max;
};

/**
 * Validate positive number
 */
export const isPositive = (value: number): boolean => {
  return value > 0;
};

/**
 * Validate URL format
 */
export const isValidUrl = (url: string): boolean => {
  try {
    new URL(url);
    return true;
  } catch {
    return false;
  }
};

/**
 * Validate barcode (EAN-13)
 */
export const isValidBarcode = (barcode: string): boolean => {
  if (!/^\d{13}$/.test(barcode)) return false;

  // Calculate check digit
  let sum = 0;
  for (let i = 0; i < 12; i++) {
    sum += parseInt(barcode[i]) * (i % 2 === 0 ? 1 : 3);
  }
  const checkDigit = (10 - (sum % 10)) % 10;

  return parseInt(barcode[12]) === checkDigit;
};
