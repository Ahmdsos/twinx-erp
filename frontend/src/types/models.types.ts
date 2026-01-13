/**
 * Model Types
 */

// User
export interface User {
  id: string;
  name: string;
  email: string;
  role?: string;
  avatar?: string;
  phone?: string;
  company_id?: string;
  is_active?: boolean;
  email_verified_at?: string;
  created_at?: string;
  updated_at?: string;
}

// Customer
export interface Customer {
  id: string;
  code: string;
  name: string;
  email?: string;
  phone?: string;
  mobile?: string;
  vat_number?: string;
  credit_limit: number;
  payment_terms: number;
  pricing_tier: 'retail' | 'semi_wholesale' | 'quarter_wholesale' | 'wholesale' | 'distributor';
  address?: string;
  city?: string;
  country?: string;
  is_active: boolean;
  company_id: string;
  created_at?: string;
  updated_at?: string;
}

// Supplier
export interface Supplier {
  id: string;
  code: string;
  name: string;
  email?: string;
  phone?: string;
  mobile?: string;
  vat_number?: string;
  cr_number?: string;
  payment_terms: number;
  address?: string;
  city?: string;
  country?: string;
  is_active: boolean;
  company_id: string;
  created_at?: string;
  updated_at?: string;
}

// Product
export interface Product {
  id: string;
  code: string;
  barcode?: string;
  name: string;
  name_ar?: string;
  description?: string;
  category_id?: string;
  brand_id?: string;
  unit_id?: string;
  
  // Pricing
  cost_price: number;
  retail_price: number;
  semi_wholesale_price: number;
  quarter_wholesale_price: number;
  wholesale_price: number;
  distributor_price?: number;
  minimum_price?: number;
  
  // Stock
  stock_quantity: number;
  reorder_level: number;
  track_stock: boolean;
  
  // Tax
  tax_rate: number;
  tax_type: 'inclusive' | 'exclusive';
  
  // Media
  image_url?: string;
  
  // Status
  is_active: boolean;
  is_sellable: boolean;
  is_purchasable: boolean;
  
  // Meta
  company_id: string;
  created_at?: string;
  updated_at?: string;
  
  // Relations
  category?: Category;
  brand?: Brand;
  unit?: Unit;
}

// Category
export interface Category {
  id: string;
  name: string;
  name_ar?: string;
  slug: string;
  parent_id?: string;
  image?: string;
  sort_order: number;
  is_active: boolean;
  company_id: string;
}

// Brand
export interface Brand {
  id: string;
  name: string;
  name_ar?: string;
  logo?: string;
  website?: string;
  description?: string;
  is_active: boolean;
  company_id: string;
  created_at?: string;
  updated_at?: string;
}

// Unit
export interface Unit {
  id: string;
  name: string;
  name_ar?: string;
  short_name: string;
  base_unit_id?: string;
  conversion_rate: number;
  is_active: boolean;
  company_id: string;
  baseUnit?: Unit;
  created_at?: string;
  updated_at?: string;
}

// Invoice
export interface Invoice {
  id: string;
  invoice_number: string;
  invoice_date: string;
  due_date?: string;
  customer_id: string;
  
  // Type
  sales_type: 'retail' | 'wholesale' | 'online' | 'pos';
  order_type: 'store_pickup' | 'delivery';
  
  // Amounts
  subtotal: number;
  discount_amount: number;
  tax_amount: number;
  shipping_amount: number;
  total: number;
  paid_amount: number;
  balance_due: number;
  
  // Status
  status: 'draft' | 'issued' | 'sent' | 'partial' | 'paid' | 'overdue' | 'cancelled';
  payment_status: 'unpaid' | 'partial' | 'paid';
  
  // Meta
  notes?: string;
  company_id: string;
  created_at?: string;
  updated_at?: string;
  
  // Relations
  customer?: Customer;
  items?: InvoiceItem[];
}

// InvoiceItem
export interface InvoiceItem {
  id: string;
  invoice_id: string;
  product_id: string;
  quantity: number;
  unit_price: number;
  discount: number;
  tax: number;
  total: number;
  product?: Product;
}

// Employee
export interface Employee {
  id: string;
  employee_number: string;
  first_name: string;
  last_name: string;
  name_ar?: string;
  email: string;
  phone?: string;
  mobile?: string;
  department_id?: string;
  designation_id?: string;
  role: 'admin' | 'manager' | 'accountant' | 'sales' | 'warehouse' | 'cashier' | 'driver';
  hire_date: string;
  basic_salary: number;
  status: 'active' | 'on_leave' | 'resigned' | 'terminated';
  
  // Driver specific
  vehicle_type?: 'car' | 'motorcycle' | 'bicycle' | 'van';
  vehicle_number?: string;
  delivery_status?: 'available' | 'busy' | 'offline';
  
  company_id: string;
  created_at?: string;
  updated_at?: string;
}

// Currency
export interface Currency {
  id: string;
  code: string;
  name: string;
  name_ar: string;
  symbol: string;
  decimal_places: number;
  exchange_rate: number;
  is_default: boolean;
  is_active: boolean;
  company_id?: string;
}
