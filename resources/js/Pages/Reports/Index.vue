<template>
    <AdminLayout title="التقارير">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">التقارير</h2>
                    <p class="text-gray-600">تقارير شاملة للمبيعات والمخزون والعملاء والمالية</p>
                </div>
                <div class="flex gap-2">
                    <button @click="exportReport('excel')" class="px-4 py-2 border rounded-lg hover:bg-gray-50 flex items-center gap-2">
                        <span>📊</span> تصدير Excel
                    </button>
                    <button @click="exportReport('pdf')" class="px-4 py-2 border rounded-lg hover:bg-gray-50 flex items-center gap-2">
                        <span>📄</span> تصدير PDF
                    </button>
                </div>
            </div>
        </template>
        
        <!-- Report Categories -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <button v-for="cat in categories" :key="cat.id" 
                    @click="activeCategory = cat.id"
                    :class="['p-4 rounded-xl border-2 transition-all text-center',
                             activeCategory === cat.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300']">
                <span class="text-3xl">{{ cat.icon }}</span>
                <p class="font-semibold mt-2">{{ cat.name }}</p>
                <p class="text-sm text-gray-500">{{ cat.reports.length }} تقرير</p>
            </button>
        </div>
        
        <!-- Report Selection -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Report Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نوع التقرير</label>
                    <select v-model="selectedReport" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">اختر التقرير</option>
                        <option v-for="report in currentReports" :key="report.id" :value="report.id">
                            {{ report.name }}
                        </option>
                    </select>
                </div>
                
                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">من تاريخ</label>
                    <input v-model="filters.dateFrom" type="date" class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">إلى تاريخ</label>
                    <input v-model="filters.dateTo" type="date" class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <!-- Generate Button -->
                <div class="flex items-end">
                    <button @click="generateReport" 
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        📊 إنشاء التقرير
                    </button>
                </div>
            </div>
            
            <!-- Additional Filters -->
            <div v-if="selectedReport" class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 pt-4 border-t">
                <div v-if="showFilter('branch')">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الفرع</label>
                    <select v-model="filters.branch" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">كل الفروع</option>
                        <option value="main">الفرع الرئيسي</option>
                        <option value="branch1">فرع 1</option>
                    </select>
                </div>
                <div v-if="showFilter('employee')">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الموظف</label>
                    <select v-model="filters.employee" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">كل الموظفين</option>
                        <option value="emp1">أحمد محمد</option>
                        <option value="emp2">سارة علي</option>
                    </select>
                </div>
                <div v-if="showFilter('customer')">
                    <label class="block text-sm font-medium text-gray-700 mb-1">العميل</label>
                    <select v-model="filters.customer" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">كل العملاء</option>
                        <option value="c1">أحمد الحربي</option>
                        <option value="c2">شركة الفجر</option>
                    </select>
                </div>
                <div v-if="showFilter('category')">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الفئة</label>
                    <select v-model="filters.category" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">كل الفئات</option>
                        <option value="electronics">إلكترونيات</option>
                        <option value="clothes">ملابس</option>
                    </select>
                </div>
                <div v-if="showFilter('warehouse')">
                    <label class="block text-sm font-medium text-gray-700 mb-1">المستودع</label>
                    <select v-model="filters.warehouse" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">كل المستودعات</option>
                        <option value="main">المستودع الرئيسي</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Report Content -->
        <div v-if="reportData" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <!-- Report Header -->
            <div class="px-6 py-4 border-b bg-gray-50">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold">{{ getReportTitle() }}</h3>
                        <p class="text-sm text-gray-500">الفترة: {{ filters.dateFrom }} - {{ filters.dateTo }}</p>
                    </div>
                    <div class="text-left">
                        <p class="text-sm text-gray-500">تاريخ الإنشاء</p>
                        <p class="font-medium">{{ new Date().toLocaleDateString('ar-SA') }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Summary Cards (for relevant reports) -->
            <div v-if="reportData.summary" class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 border-b">
                <div v-for="(item, key) in reportData.summary" :key="key" 
                     class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600">{{ item.label }}</p>
                    <p class="text-2xl font-bold" :class="item.color || 'text-gray-800'">{{ item.value }}</p>
                    <p v-if="item.change" :class="item.change > 0 ? 'text-green-600' : 'text-red-600'" class="text-sm">
                        {{ item.change > 0 ? '↑' : '↓' }} {{ Math.abs(item.change) }}%
                    </p>
                </div>
            </div>
            
            <!-- Data Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th v-for="col in reportData.columns" :key="col.key" 
                                class="px-4 py-3 text-right text-sm font-semibold text-gray-600"
                                :class="col.important ? 'bg-blue-50' : ''">
                                {{ col.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="(row, i) in reportData.rows" :key="i" 
                            :class="row.isTotal ? 'bg-gray-100 font-bold' : 'hover:bg-gray-50'">
                            <td v-for="col in reportData.columns" :key="col.key" 
                                class="px-4 py-3"
                                :class="[col.important ? 'bg-blue-50 font-medium' : '', col.type === 'currency' ? 'text-left font-mono' : '']">
                                <span v-if="col.type === 'currency'" :class="row[col.key] < 0 ? 'text-red-600' : ''">
                                    {{ formatCurrency(row[col.key]) }}
                                </span>
                                <span v-else-if="col.type === 'percent'" class="text-blue-600">
                                    {{ row[col.key] }}%
                                </span>
                                <span v-else-if="col.type === 'status'">
                                    <span :class="getStatusClass(row[col.key])">{{ row[col.key] }}</span>
                                </span>
                                <span v-else>{{ row[col.key] }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Chart (if applicable) -->
            <div v-if="reportData.showChart" class="p-6 border-t">
                <h4 class="font-semibold mb-4">رسم بياني</h4>
                <div class="h-64 flex items-end gap-2">
                    <div v-for="(item, i) in reportData.chartData" :key="i" 
                         class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-blue-500 rounded-t transition-all hover:bg-blue-600"
                             :style="{ height: (item.value / maxChartValue * 200) + 'px' }"></div>
                        <span class="text-xs text-gray-500 mt-2 truncate w-full text-center">{{ item.label }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Empty State -->
        <div v-else class="bg-white rounded-xl shadow-sm p-12 text-center">
            <span class="text-6xl">📊</span>
            <h3 class="text-xl font-semibold mt-4">اختر تقريراً لعرضه</h3>
            <p class="text-gray-500 mt-2">اختر نوع التقرير والفترة الزمنية ثم اضغط على "إنشاء التقرير"</p>
        </div>
        
        <!-- Toast -->
        <div v-if="toast.show" class="fixed bottom-4 left-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 bg-blue-600">
            {{ toast.message }}
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const activeCategory = ref('sales');
const selectedReport = ref('');
const reportData = ref(null);
const toast = reactive({ show: false, message: '' });

const filters = reactive({
    dateFrom: new Date(new Date().setDate(1)).toISOString().split('T')[0], // First of month
    dateTo: new Date().toISOString().split('T')[0],
    branch: '',
    employee: '',
    customer: '',
    category: '',
    warehouse: '',
});

const categories = ref([
    {
        id: 'sales',
        name: 'تقارير المبيعات',
        icon: '💰',
        reports: [
            { id: 'daily_sales', name: 'المبيعات اليومية', filters: ['branch', 'employee'] },
            { id: 'sales_by_product', name: 'المبيعات حسب المنتج', filters: ['category'] },
            { id: 'sales_by_customer', name: 'المبيعات حسب العميل', filters: ['customer'] },
            { id: 'sales_by_employee', name: 'المبيعات حسب الموظف', filters: ['branch', 'employee'] },
            { id: 'top_products', name: 'أفضل 10 منتجات', filters: ['category'] },
            { id: 'top_customers', name: 'أفضل 10 عملاء', filters: [] },
            { id: 'sales_comparison', name: 'مقارنة الفترات', filters: [] },
            { id: 'profit_margins', name: 'هوامش الربح', filters: ['category'] },
        ]
    },
    {
        id: 'inventory',
        name: 'تقارير المخزون',
        icon: '📦',
        reports: [
            { id: 'stock_balance', name: 'رصيد المخزون', filters: ['warehouse', 'category'] },
            { id: 'stock_movement', name: 'حركة المخزون', filters: ['warehouse'] },
            { id: 'low_stock', name: 'منتجات منخفضة المخزون', filters: ['warehouse'] },
            { id: 'dead_stock', name: 'منتجات راكدة', filters: ['warehouse'] },
            { id: 'expiry_report', name: 'تقرير الصلاحية', filters: ['warehouse'] },
            { id: 'stock_valuation', name: 'قيمة المخزون', filters: ['warehouse', 'category'] },
            { id: 'inventory_count', name: 'تقرير الجرد', filters: ['warehouse'] },
        ]
    },
    {
        id: 'customers',
        name: 'تقارير العملاء',
        icon: '👥',
        reports: [
            { id: 'customer_statement', name: 'كشف حساب العميل', filters: ['customer'] },
            { id: 'aging_report', name: 'أعمار الديون', filters: [] },
            { id: 'collection_report', name: 'تقرير التحصيل', filters: ['employee'] },
            { id: 'overdue_customers', name: 'العملاء المتعثرين', filters: [] },
        ]
    },
    {
        id: 'financial',
        name: 'تقارير مالية',
        icon: '📈',
        reports: [
            { id: 'profit_loss', name: 'الأرباح والخسائر', filters: [] },
            { id: 'cash_flow', name: 'التدفق النقدي', filters: [] },
            { id: 'trial_balance', name: 'ميزان المراجعة', filters: [] },
            { id: 'general_ledger', name: 'دفتر الأستاذ', filters: [] },
            { id: 'tax_report', name: 'تقرير الضريبة', filters: [] },
        ]
    },
]);

const currentReports = computed(() => {
    const cat = categories.value.find(c => c.id === activeCategory.value);
    return cat ? cat.reports : [];
});

const maxChartValue = computed(() => {
    if (!reportData.value?.chartData) return 100;
    return Math.max(...reportData.value.chartData.map(d => d.value));
});

const showFilter = (filterName) => {
    const report = currentReports.value.find(r => r.id === selectedReport.value);
    return report?.filters?.includes(filterName);
};

const getReportTitle = () => {
    const report = currentReports.value.find(r => r.id === selectedReport.value);
    return report?.name || 'تقرير';
};

const formatCurrency = (amount) => {
    if (amount === undefined || amount === null) return '-';
    return new Intl.NumberFormat('ar-SA').format(amount) + ' ر.س';
};

const getStatusClass = (status) => {
    const classes = {
        'مدفوعة': 'px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs',
        'معلقة': 'px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs',
        'متأخرة': 'px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs',
    };
    return classes[status] || '';
};

const showToast = (message) => {
    toast.message = message;
    toast.show = true;
    setTimeout(() => { toast.show = false; }, 3000);
};

const generateReport = () => {
    if (!selectedReport.value) {
        showToast('يرجى اختيار نوع التقرير');
        return;
    }
    
    // Sample data based on report type
    const reports = {
        'daily_sales': {
            summary: [
                { label: 'إجمالي المبيعات', value: '125,000 ر.س', color: 'text-green-600', change: 12 },
                { label: 'عدد الفواتير', value: '45', color: 'text-blue-600', change: 8 },
                { label: 'متوسط الفاتورة', value: '2,778 ر.س', color: 'text-purple-600', change: 3 },
                { label: 'صافي الربح', value: '31,250 ر.س', color: 'text-green-600', change: 15 },
            ],
            columns: [
                { key: 'date', label: 'التاريخ' },
                { key: 'invoices', label: 'عدد الفواتير' },
                { key: 'gross', label: 'إجمالي المبيعات', type: 'currency', important: true },
                { key: 'returns', label: 'المرتجعات', type: 'currency' },
                { key: 'discount', label: 'الخصومات', type: 'currency' },
                { key: 'net', label: 'صافي المبيعات', type: 'currency', important: true },
                { key: 'cost', label: 'التكلفة', type: 'currency' },
                { key: 'profit', label: 'الربح', type: 'currency', important: true },
                { key: 'margin', label: 'الهامش', type: 'percent' },
            ],
            rows: [
                { date: '2026-01-01', invoices: 8, gross: 28500, returns: 500, discount: 250, net: 27750, cost: 21000, profit: 6750, margin: 24.3 },
                { date: '2026-01-02', invoices: 12, gross: 42000, returns: 0, discount: 1200, net: 40800, cost: 30000, profit: 10800, margin: 26.5 },
                { date: '2026-01-03', invoices: 6, gross: 18000, returns: 1500, discount: 0, net: 16500, cost: 12500, profit: 4000, margin: 24.2 },
                { date: '2026-01-04', invoices: 10, gross: 35000, returns: 0, discount: 500, net: 34500, cost: 25000, profit: 9500, margin: 27.5 },
                { date: '2026-01-05', invoices: 9, gross: 31500, returns: 800, discount: 300, net: 30400, cost: 22500, profit: 7900, margin: 26.0 },
                { date: 'الإجمالي', invoices: 45, gross: 155000, returns: 2800, discount: 2250, net: 149950, cost: 111000, profit: 38950, margin: 26.0, isTotal: true },
            ],
            showChart: true,
            chartData: [
                { label: 'السبت', value: 28500 },
                { label: 'الأحد', value: 42000 },
                { label: 'الاثنين', value: 18000 },
                { label: 'الثلاثاء', value: 35000 },
                { label: 'الأربعاء', value: 31500 },
            ],
        },
        'aging_report': {
            summary: [
                { label: 'إجمالي المديونيات', value: '245,000 ر.س', color: 'text-red-600' },
                { label: 'حالية (0-30)', value: '85,000 ر.س', color: 'text-green-600' },
                { label: 'متأخرة (31-60)', value: '65,000 ر.س', color: 'text-yellow-600' },
                { label: 'متعثرة (+90)', value: '45,000 ر.س', color: 'text-red-600' },
            ],
            columns: [
                { key: 'customer', label: 'العميل', important: true },
                { key: 'current', label: '0-30 يوم', type: 'currency' },
                { key: 'days31', label: '31-60 يوم', type: 'currency' },
                { key: 'days61', label: '61-90 يوم', type: 'currency' },
                { key: 'days90', label: '+90 يوم', type: 'currency' },
                { key: 'total', label: 'الإجمالي', type: 'currency', important: true },
            ],
            rows: [
                { customer: 'شركة الفجر للتجارة', current: 25000, days31: 15000, days61: 10000, days90: 25000, total: 75000 },
                { customer: 'مؤسسة البركة', current: 18000, days31: 8500, days61: 0, days90: 8500, total: 35000 },
                { customer: 'سارة علي العتيبي', current: 5000, days31: 3500, days61: 2000, days90: 1500, total: 12000 },
                { customer: 'محمد خالد السعيد', current: 8000, days31: 0, days61: 5000, days90: 0, total: 13000 },
                { customer: 'الإجمالي', current: 85000, days31: 65000, days61: 50000, days90: 45000, total: 245000, isTotal: true },
            ],
            showChart: false,
        },
        'stock_balance': {
            summary: [
                { label: 'إجمالي الأصناف', value: '1,250', color: 'text-blue-600' },
                { label: 'قيمة المخزون', value: '2.5M ر.س', color: 'text-green-600' },
                { label: 'أصناف نشطة', value: '1,180', color: 'text-green-600' },
                { label: 'منخفض المخزون', value: '35', color: 'text-red-600' },
            ],
            columns: [
                { key: 'sku', label: 'SKU' },
                { key: 'name', label: 'المنتج', important: true },
                { key: 'category', label: 'الفئة' },
                { key: 'qty', label: 'الكمية', important: true },
                { key: 'unit', label: 'الوحدة' },
                { key: 'cost', label: 'التكلفة', type: 'currency' },
                { key: 'value', label: 'القيمة', type: 'currency', important: true },
                { key: 'min', label: 'الحد الأدنى' },
                { key: 'status', label: 'الحالة', type: 'status' },
            ],
            rows: [
                { sku: 'IP15P-001', name: 'آيفون 15 برو', category: 'إلكترونيات', qty: 25, unit: 'قطعة', cost: 3500, value: 87500, min: 10, status: 'متوفر' },
                { sku: 'SS24-001', name: 'سامسونج S24', category: 'إلكترونيات', qty: 18, unit: 'قطعة', cost: 2800, value: 50400, min: 10, status: 'متوفر' },
                { sku: 'MBP-001', name: 'ماك بوك برو', category: 'إلكترونيات', qty: 5, unit: 'قطعة', cost: 6500, value: 32500, min: 5, status: 'منخفض' },
                { sku: 'TS-001', name: 'تيشيرت قطن', category: 'ملابس', qty: 150, unit: 'قطعة', cost: 45, value: 6750, min: 50, status: 'متوفر' },
            ],
            showChart: false,
        },
        'profit_loss': {
            summary: [
                { label: 'إجمالي الإيرادات', value: '850,000 ر.س', color: 'text-green-600' },
                { label: 'إجمالي المصروفات', value: '680,000 ر.س', color: 'text-red-600' },
                { label: 'صافي الربح', value: '170,000 ر.س', color: 'text-green-600', change: 18 },
                { label: 'هامش الربح', value: '20%', color: 'text-blue-600' },
            ],
            columns: [
                { key: 'item', label: 'البند', important: true },
                { key: 'amount', label: 'المبلغ', type: 'currency', important: true },
                { key: 'percent', label: 'النسبة', type: 'percent' },
            ],
            rows: [
                { item: '📈 الإيرادات', amount: null, percent: null, isTotal: true },
                { item: 'المبيعات', amount: 800000, percent: 94.1 },
                { item: 'إيرادات أخرى', amount: 50000, percent: 5.9 },
                { item: 'إجمالي الإيرادات', amount: 850000, percent: 100, isTotal: true },
                { item: '📉 المصروفات', amount: null, percent: null, isTotal: true },
                { item: 'تكلفة البضاعة المباعة', amount: 520000, percent: 61.2 },
                { item: 'رواتب الموظفين', amount: 80000, percent: 9.4 },
                { item: 'إيجار', amount: 35000, percent: 4.1 },
                { item: 'مصروفات تشغيلية', amount: 25000, percent: 2.9 },
                { item: 'مصروفات أخرى', amount: 20000, percent: 2.4 },
                { item: 'إجمالي المصروفات', amount: 680000, percent: 80, isTotal: true },
                { item: '💰 صافي الربح', amount: 170000, percent: 20, isTotal: true },
            ],
            showChart: false,
        },
    };
    
    reportData.value = reports[selectedReport.value] || reports['daily_sales'];
    showToast('✅ تم إنشاء التقرير');
};

const exportReport = (format) => {
    showToast(`📤 جاري تصدير التقرير بصيغة ${format.toUpperCase()}`);
};
</script>
