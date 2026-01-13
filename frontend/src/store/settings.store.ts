import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export type ThemeMode = 'light' | 'dark';
export type Language = 'ar' | 'en';

export interface Currency {
  code: string;
  name: string;
  name_ar: string;
  symbol: string;
  decimal_places: number;
  exchange_rate: number;
}

// Available currencies
export const CURRENCIES: Currency[] = [
  { code: 'SAR', name: 'Saudi Riyal', name_ar: 'ريال سعودي', symbol: '﷼', decimal_places: 2, exchange_rate: 1 },
  { code: 'EGP', name: 'Egyptian Pound', name_ar: 'جنيه مصري', symbol: 'ج.م', decimal_places: 2, exchange_rate: 0.08 },
  { code: 'AED', name: 'UAE Dirham', name_ar: 'درهم إماراتي', symbol: 'د.إ', decimal_places: 2, exchange_rate: 0.98 },
  { code: 'USD', name: 'US Dollar', name_ar: 'دولار أمريكي', symbol: '$', decimal_places: 2, exchange_rate: 3.75 },
  { code: 'EUR', name: 'Euro', name_ar: 'يورو', symbol: '€', decimal_places: 2, exchange_rate: 4.10 },
];

interface SettingsState {
  theme: ThemeMode;
  language: Language;
  currency: Currency;
  sidebarCollapsed: boolean;
  
  // Actions
  setTheme: (theme: ThemeMode) => void;
  toggleTheme: () => void;
  setLanguage: (language: Language) => void;
  setCurrency: (currencyCode: string) => void;
  toggleSidebar: () => void;
}

export const useSettingsStore = create<SettingsState>()(
  persist(
    (set) => ({
      theme: 'light',
      language: 'ar',
      currency: CURRENCIES[0], // SAR default
      sidebarCollapsed: false,

      setTheme: (theme) => set({ theme }),
      
      toggleTheme: () => set((state) => ({ 
        theme: state.theme === 'light' ? 'dark' : 'light' 
      })),
      
      setLanguage: (language) => set({ language }),
      
      setCurrency: (currencyCode) => {
        const currency = CURRENCIES.find((c) => c.code === currencyCode);
        if (currency) {
          set({ currency });
        }
      },
      
      toggleSidebar: () => set((state) => ({ 
        sidebarCollapsed: !state.sidebarCollapsed 
      })),
    }),
    {
      name: 'settings-storage',
    }
  )
);

// Helper function to format currency
export const formatCurrency = (amount: number, currency?: Currency): string => {
  const curr = currency || useSettingsStore.getState().currency;
  return new Intl.NumberFormat('ar-SA', {
    style: 'currency',
    currency: curr.code,
    minimumFractionDigits: curr.decimal_places,
    maximumFractionDigits: curr.decimal_places,
  }).format(amount);
};
