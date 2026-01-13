import api from './axios';
import type { ApiResponse, Currency } from '../types';

export interface CompanySettings {
  id: string;
  name: string;
  name_ar?: string;
  email?: string;
  phone?: string;
  mobile?: string;
  address?: string;
  city?: string;
  country?: string;
  postal_code?: string;
  logo?: string;
  vat_number?: string;
  cr_number?: string;
  website?: string;
}

export interface CurrencySettings {
  default_currency: string;
  decimal_places: number;
  currency_position: 'before' | 'after';
  thousand_separator: string;
  decimal_separator: string;
}

export interface TaxSettings {
  tax_rate: number;
  tax_number?: string;
  tax_type: 'inclusive' | 'exclusive';
  tax_name: string;
}

export interface InvoiceSettings {
  invoice_prefix: string;
  quotation_prefix: string;
  credit_note_prefix: string;
  invoice_footer?: string;
  invoice_terms?: string;
}

export interface GeneralSettings {
  date_format: string;
  time_format: string;
  timezone: string;
  language: 'ar' | 'en';
}

export interface AllSettings {
  company: CompanySettings;
  currency: CurrencySettings;
  tax: TaxSettings;
  invoice: InvoiceSettings;
  general: GeneralSettings;
}

export interface UpdateSettingsData {
  // Company
  name?: string;
  name_ar?: string;
  email?: string;
  phone?: string;
  mobile?: string;
  address?: string;
  city?: string;
  country?: string;
  postal_code?: string;
  vat_number?: string;
  cr_number?: string;
  website?: string;
  
  // Currency
  default_currency?: string;
  decimal_places?: number;
  currency_position?: 'before' | 'after';
  thousand_separator?: string;
  decimal_separator?: string;
  
  // Tax
  tax_rate?: number;
  tax_type?: 'inclusive' | 'exclusive';
  tax_name?: string;
  
  // Invoice
  invoice_prefix?: string;
  quotation_prefix?: string;
  credit_note_prefix?: string;
  invoice_footer?: string;
  invoice_terms?: string;
  
  // General
  date_format?: string;
  time_format?: string;
  timezone?: string;
  language?: 'ar' | 'en';
}

export interface CurrencyExchangeUpdate {
  code: string;
  exchange_rate: number;
  is_active?: boolean;
}

const settingsApi = {
  getAll: async () => {
    const response = await api.get<ApiResponse<AllSettings>>('/v1/settings');
    return response.data;
  },

  update: async (data: UpdateSettingsData) => {
    const response = await api.put<ApiResponse<any>>('/v1/settings', data);
    return response.data;
  },

  uploadLogo: async (file: File) => {
    const formData = new FormData();
    formData.append('logo', file);
    const response = await api.post<ApiResponse<{ logo: string }>>('/v1/settings/logo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  getCurrencies: async () => {
    const response = await api.get<ApiResponse<Currency[]>>('/v1/settings/currencies');
    return response.data;
  },

  updateCurrencies: async (currencies: CurrencyExchangeUpdate[]) => {
    const response = await api.put<ApiResponse<null>>('/v1/settings/currencies', { currencies });
    return response.data;
  },

  resetToDefault: async () => {
    const response = await api.post<ApiResponse<any>>('/v1/settings/reset');
    return response.data;
  },
};

export default settingsApi;
