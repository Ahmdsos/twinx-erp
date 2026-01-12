<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * ChartOfAccountsSeeder
 * 
 * Seeds a standard Chart of Accounts based on Saudi GAAP.
 * Creates hierarchical account structure.
 */
class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get company (or create for testing)
        $company = Company::first();
        
        if (!$company) {
            $company = Company::create([
                'name' => 'Demo Company',
                'name_ar' => 'شركة تجريبية',
                'base_currency' => 'SAR',
                'is_active' => true,
            ]);
        }

        // Skip if accounts already exist for this company
        if (Account::where('company_id', $company->id)->exists()) {
            $this->command->info('Chart of Accounts already exists for this company. Skipping...');
            return;
        }

        $this->seedAccounts($company);
    }

    private function seedAccounts(Company $company): void
    {
        $structure = $this->getAccountStructure();

        foreach ($structure as $typeKey => $typeData) {
            $type = AccountType::from($typeKey);
            
            foreach ($typeData['groups'] as $groupData) {
                $this->createAccountWithChildren($company, $type, $groupData, null);
            }
        }
    }

    private function createAccountWithChildren(
        Company $company,
        AccountType $type,
        array $data,
        ?string $parentId
    ): void {
        $isGroup = !empty($data['children']);

        $account = Account::create([
            'company_id' => $company->id,
            'parent_id' => $parentId,
            'code' => $data['code'],
            'name' => $data['name'],
            'name_ar' => $data['name_ar'],
            'type' => $type,
            'is_group' => $isGroup,
            'is_system' => $data['is_system'] ?? false,
            'level' => $parentId ? Account::find($parentId)->level + 1 : 1,
            'normal_balance' => $type->normalBalance(),
            'is_active' => true,
            'allow_direct_posting' => !$isGroup,
        ]);

        if (!empty($data['children'])) {
            foreach ($data['children'] as $childData) {
                $this->createAccountWithChildren($company, $type, $childData, $account->id);
            }
        }
    }

    private function getAccountStructure(): array
    {
        return [
            'asset' => [
                'groups' => [
                    [
                        'code' => '1000',
                        'name' => 'Current Assets',
                        'name_ar' => 'الأصول المتداولة',
                        'children' => [
                            [
                                'code' => '1100',
                                'name' => 'Cash and Cash Equivalents',
                                'name_ar' => 'النقدية وما في حكمها',
                                'children' => [
                                    ['code' => '1101', 'name' => 'Cash on Hand', 'name_ar' => 'صندوق النقدية'],
                                    ['code' => '1102', 'name' => 'Bank Accounts', 'name_ar' => 'الحسابات البنكية'],
                                    ['code' => '1103', 'name' => 'Petty Cash', 'name_ar' => 'العهد النقدية'],
                                ],
                            ],
                            [
                                'code' => '1200',
                                'name' => 'Receivables',
                                'name_ar' => 'المدينون',
                                'children' => [
                                    ['code' => '1201', 'name' => 'Accounts Receivable', 'name_ar' => 'العملاء'],
                                    ['code' => '1202', 'name' => 'Notes Receivable', 'name_ar' => 'أوراق القبض'],
                                    ['code' => '1203', 'name' => 'Employee Advances', 'name_ar' => 'سلف الموظفين'],
                                    ['code' => '1209', 'name' => 'Allowance for Doubtful Accounts', 'name_ar' => 'مخصص الديون المشكوك فيها'],
                                ],
                            ],
                            [
                                'code' => '1300',
                                'name' => 'Inventory',
                                'name_ar' => 'المخزون',
                                'children' => [
                                    ['code' => '1301', 'name' => 'Merchandise Inventory', 'name_ar' => 'بضاعة بالمخازن'],
                                    ['code' => '1302', 'name' => 'Raw Materials', 'name_ar' => 'مواد خام'],
                                    ['code' => '1303', 'name' => 'Work in Progress', 'name_ar' => 'إنتاج تحت التشغيل'],
                                    ['code' => '1304', 'name' => 'Finished Goods', 'name_ar' => 'منتجات تامة'],
                                ],
                            ],
                            [
                                'code' => '1400',
                                'name' => 'Prepaid Expenses',
                                'name_ar' => 'مصروفات مدفوعة مقدماً',
                                'children' => [
                                    ['code' => '1401', 'name' => 'Prepaid Rent', 'name_ar' => 'إيجار مدفوع مقدماً'],
                                    ['code' => '1402', 'name' => 'Prepaid Insurance', 'name_ar' => 'تأمين مدفوع مقدماً'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'code' => '1500',
                        'name' => 'Non-Current Assets',
                        'name_ar' => 'الأصول غير المتداولة',
                        'children' => [
                            [
                                'code' => '1510',
                                'name' => 'Property, Plant & Equipment',
                                'name_ar' => 'الأصول الثابتة',
                                'children' => [
                                    ['code' => '1511', 'name' => 'Land', 'name_ar' => 'الأراضي'],
                                    ['code' => '1512', 'name' => 'Buildings', 'name_ar' => 'المباني'],
                                    ['code' => '1513', 'name' => 'Vehicles', 'name_ar' => 'السيارات'],
                                    ['code' => '1514', 'name' => 'Furniture & Fixtures', 'name_ar' => 'الأثاث والتجهيزات'],
                                    ['code' => '1515', 'name' => 'Equipment', 'name_ar' => 'المعدات'],
                                    ['code' => '1519', 'name' => 'Accumulated Depreciation', 'name_ar' => 'مجمع الإهلاك'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'liability' => [
                'groups' => [
                    [
                        'code' => '2000',
                        'name' => 'Current Liabilities',
                        'name_ar' => 'الالتزامات المتداولة',
                        'children' => [
                            [
                                'code' => '2100',
                                'name' => 'Payables',
                                'name_ar' => 'الدائنون',
                                'children' => [
                                    ['code' => '2101', 'name' => 'Accounts Payable', 'name_ar' => 'الموردون'],
                                    ['code' => '2102', 'name' => 'Notes Payable', 'name_ar' => 'أوراق الدفع'],
                                    ['code' => '2103', 'name' => 'Accrued Expenses', 'name_ar' => 'مصروفات مستحقة'],
                                ],
                            ],
                            [
                                'code' => '2200',
                                'name' => 'Tax Liabilities',
                                'name_ar' => 'الضرائب المستحقة',
                                'children' => [
                                    ['code' => '2201', 'name' => 'VAT Payable', 'name_ar' => 'ضريبة القيمة المضافة'],
                                    ['code' => '2202', 'name' => 'Income Tax Payable', 'name_ar' => 'ضريبة الدخل المستحقة'],
                                ],
                            ],
                            [
                                'code' => '2300',
                                'name' => 'Employee Liabilities',
                                'name_ar' => 'مستحقات الموظفين',
                                'children' => [
                                    ['code' => '2301', 'name' => 'Salaries Payable', 'name_ar' => 'رواتب مستحقة'],
                                    ['code' => '2302', 'name' => 'GOSI Payable', 'name_ar' => 'تأمينات اجتماعية مستحقة'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'code' => '2500',
                        'name' => 'Non-Current Liabilities',
                        'name_ar' => 'الالتزامات غير المتداولة',
                        'children' => [
                            ['code' => '2501', 'name' => 'Long-term Loans', 'name_ar' => 'قروض طويلة الأجل'],
                            ['code' => '2502', 'name' => 'End of Service Benefits', 'name_ar' => 'مكافآت نهاية الخدمة'],
                        ],
                    ],
                ],
            ],
            'equity' => [
                'groups' => [
                    [
                        'code' => '3000',
                        'name' => 'Owner\'s Equity',
                        'name_ar' => 'حقوق الملكية',
                        'children' => [
                            ['code' => '3001', 'name' => 'Capital', 'name_ar' => 'رأس المال', 'is_system' => true],
                            ['code' => '3002', 'name' => 'Retained Earnings', 'name_ar' => 'أرباح محتجزة', 'is_system' => true],
                            ['code' => '3003', 'name' => 'Drawings', 'name_ar' => 'مسحوبات'],
                            ['code' => '3004', 'name' => 'Current Year Profit/Loss', 'name_ar' => 'ربح/خسارة السنة الحالية', 'is_system' => true],
                        ],
                    ],
                ],
            ],
            'revenue' => [
                'groups' => [
                    [
                        'code' => '4000',
                        'name' => 'Revenue',
                        'name_ar' => 'الإيرادات',
                        'children' => [
                            [
                                'code' => '4100',
                                'name' => 'Sales Revenue',
                                'name_ar' => 'إيرادات المبيعات',
                                'children' => [
                                    ['code' => '4101', 'name' => 'Product Sales', 'name_ar' => 'مبيعات بضاعة'],
                                    ['code' => '4102', 'name' => 'Service Revenue', 'name_ar' => 'إيرادات خدمات'],
                                    ['code' => '4103', 'name' => 'Sales Returns', 'name_ar' => 'مردودات المبيعات'],
                                    ['code' => '4104', 'name' => 'Sales Discounts', 'name_ar' => 'خصم المبيعات'],
                                ],
                            ],
                            [
                                'code' => '4200',
                                'name' => 'Other Revenue',
                                'name_ar' => 'إيرادات أخرى',
                                'children' => [
                                    ['code' => '4201', 'name' => 'Interest Income', 'name_ar' => 'إيرادات فوائد'],
                                    ['code' => '4202', 'name' => 'Rental Income', 'name_ar' => 'إيرادات إيجار'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'cogs' => [
                'groups' => [
                    [
                        'code' => '5000',
                        'name' => 'Cost of Goods Sold',
                        'name_ar' => 'تكلفة البضاعة المباعة',
                        'children' => [
                            ['code' => '5001', 'name' => 'Cost of Goods Sold', 'name_ar' => 'تكلفة البضاعة المباعة'],
                            ['code' => '5002', 'name' => 'Purchase Discounts', 'name_ar' => 'خصم المشتريات'],
                            ['code' => '5003', 'name' => 'Purchase Returns', 'name_ar' => 'مردودات المشتريات'],
                        ],
                    ],
                ],
            ],
            'expense' => [
                'groups' => [
                    [
                        'code' => '6000',
                        'name' => 'Operating Expenses',
                        'name_ar' => 'المصروفات التشغيلية',
                        'children' => [
                            [
                                'code' => '6100',
                                'name' => 'Employee Expenses',
                                'name_ar' => 'مصروفات الموظفين',
                                'children' => [
                                    ['code' => '6101', 'name' => 'Salaries & Wages', 'name_ar' => 'الرواتب والأجور'],
                                    ['code' => '6102', 'name' => 'Benefits', 'name_ar' => 'المزايا'],
                                    ['code' => '6103', 'name' => 'GOSI Expense', 'name_ar' => 'مصروف التأمينات'],
                                ],
                            ],
                            [
                                'code' => '6200',
                                'name' => 'Rent & Utilities',
                                'name_ar' => 'الإيجار والمرافق',
                                'children' => [
                                    ['code' => '6201', 'name' => 'Rent Expense', 'name_ar' => 'مصروف الإيجار'],
                                    ['code' => '6202', 'name' => 'Electricity', 'name_ar' => 'الكهرباء'],
                                    ['code' => '6203', 'name' => 'Water', 'name_ar' => 'المياه'],
                                    ['code' => '6204', 'name' => 'Telephone & Internet', 'name_ar' => 'الهاتف والإنترنت'],
                                ],
                            ],
                            [
                                'code' => '6300',
                                'name' => 'Administrative Expenses',
                                'name_ar' => 'مصروفات إدارية',
                                'children' => [
                                    ['code' => '6301', 'name' => 'Office Supplies', 'name_ar' => 'مستلزمات مكتبية'],
                                    ['code' => '6302', 'name' => 'Professional Fees', 'name_ar' => 'أتعاب مهنية'],
                                    ['code' => '6303', 'name' => 'Insurance Expense', 'name_ar' => 'مصروف التأمين'],
                                ],
                            ],
                            [
                                'code' => '6400',
                                'name' => 'Depreciation & Amortization',
                                'name_ar' => 'الإهلاك والإطفاء',
                                'children' => [
                                    ['code' => '6401', 'name' => 'Depreciation Expense', 'name_ar' => 'مصروف الإهلاك'],
                                ],
                            ],
                            [
                                'code' => '6500',
                                'name' => 'Financial Expenses',
                                'name_ar' => 'مصروفات مالية',
                                'children' => [
                                    ['code' => '6501', 'name' => 'Bank Charges', 'name_ar' => 'مصاريف بنكية'],
                                    ['code' => '6502', 'name' => 'Interest Expense', 'name_ar' => 'مصروف الفوائد'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
