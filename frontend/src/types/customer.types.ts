// Customer Types
export interface Customer {
  id: string;
  code: string;
  name: string;
  name_ar?: string;
  display_name: string;
  
  // Contact
  email?: string;
  phone: string;
  mobile?: string;
  
  // Legal & Tax
  vat_number?: string;
  cr_number?: string;
  
  // Address
  address?: string;
  city?: string;
  country?: string;
  
  // Pricing & Payment
  price_list_id?: string;
  price_list?: {
    id: string;
    name: string;
  };
  credit_limit: number;
  payment_terms: number;
  payment_terms_label: string;
  
  // Accounting
  receivable_account_id?: string;
  
  // Computed - Balance
  total_balance?: number;
  balance_status: 'clear' | 'due' | 'overpaid';
  credit_status: 'no_credit' | 'good' | 'warning' | 'exceeded';
  available_credit: number;
  
  // Statistics
  total_invoices?: number;
  total_payments?: number;
  
  // Status
  is_active: boolean;
  
  // Metadata
  metadata?: CustomerMetadata;
  
  // Timestamps
  created_at: string;
  updated_at: string;
}

export interface CustomerMetadata {
  notes?: string;
  tags?: string[];
  custom_fields?: Record<string, any>;
}

export interface CustomerFormData {
  code?: string;
  name: string;
  name_ar?: string;
  email?: string;
  phone: string;
  mobile?: string;
  vat_number?: string;
  cr_number?: string;
  address?: string;
  city?: string;
  country?: string;
  price_list_id?: string;
  credit_limit?: number;
  payment_terms?: number;
  receivable_account_id?: string;
  is_active?: boolean;
  metadata?: CustomerMetadata;
}

export interface CustomerStatement {
  customer: Customer;
  period: {
    from: string;
    to: string;
  };
  opening_balance: number;
  transactions: StatementTransaction[];
  closing_balance: number;
}

export interface StatementTransaction {
  id: string;
  type: 'invoice' | 'payment' | 'credit_note';
  date: string;
  reference: string;
  description: string;
  debit: number;
  credit: number;
  balance: number;
}

// Supplier Types (similar structure)
export interface Supplier {
  id: string;
  code: string;
  name: string;
  name_ar?: string;
  display_name: string;
  
  // Contact
  email?: string;
  phone: string;
  mobile?: string;
  
  // Legal & Tax
  vat_number?: string;
  cr_number?: string;
  
  // Address
  address?: string;
  city?: string;
  country?: string;
  
  // Payment
  payment_terms: number;
  payment_terms_label: string;
  
  // Accounting
  payable_account_id?: string;
  
  // Computed - Balance
  total_balance?: number;
  balance_status: 'clear' | 'due' | 'overpaid';
  
  // Statistics
  total_purchase_orders?: number;
  total_bills?: number;
  total_payments?: number;
  
  // Status
  is_active: boolean;
  
  // Metadata
  metadata?: SupplierMetadata;
  
  // Timestamps
  created_at: string;
  updated_at: string;
}

export interface SupplierMetadata {
  notes?: string;
  tags?: string[];
  bank_details?: {
    bank_name?: string;
    account_number?: string;
    iban?: string;
    swift_code?: string;
  };
}

export interface SupplierFormData {
  code?: string;
  name: string;
  name_ar?: string;
  email?: string;
  phone: string;
  mobile?: string;
  vat_number?: string;
  cr_number?: string;
  address?: string;
  city?: string;
  country?: string;
  payment_terms?: number;
  payable_account_id?: string;
  is_active?: boolean;
  metadata?: SupplierMetadata;
}

export interface SupplierStatement {
  supplier: Supplier;
  period: {
    from: string;
    to: string;
  };
  opening_balance: number;
  transactions: StatementTransaction[];
  closing_balance: number;
}
