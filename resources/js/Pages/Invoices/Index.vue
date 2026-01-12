<template>
    <AdminLayout title="الفواتير">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">الفواتير</h2>
                    <p class="text-gray-600">إدارة فواتير المبيعات</p>
                </div>
                <button @click="openNewModal" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <span>➕</span>
                    <span>فاتورة جديدة</span>
                </button>
            </div>
        </template>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-gray-500 text-sm">مبيعات اليوم</p>
                <p class="text-2xl font-bold text-green-600">{{ formatCurrency(props.stats?.today_sales || 0) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-gray-500 text-sm">فواتير اليوم</p>
                <p class="text-2xl font-bold text-blue-600">{{ props.stats?.today_count || 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-gray-500 text-sm">معلقة</p>
                <p class="text-2xl font-bold text-yellow-600">{{ props.stats?.pending || 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-gray-500 text-sm">مبيعات الشهر</p>
                <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(props.stats?.month_sales || 0) }}</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input v-model="searchQuery" @input="debouncedSearch" type="text" 
                           placeholder="🔍 بحث برقم الفاتورة..."
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <select v-model="statusFilter" @change="applyFilters" class="px-4 py-2 border rounded-lg">
                    <option value="">كل الحالات</option>
                    <option value="draft">مسودة</option>
                    <option value="issued">صادرة</option>
                    <option value="paid">مدفوعة</option>
                    <option value="cancelled">ملغية</option>
                </select>
                <input v-model="dateFrom" @change="applyFilters" type="date" class="px-4 py-2 border rounded-lg">
                <input v-model="dateTo" @change="applyFilters" type="date" class="px-4 py-2 border rounded-lg">
            </div>
        </div>
        
        <!-- Invoices Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">رقم الفاتورة</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">العميل</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">التاريخ</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">الإجمالي</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">الحالة</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="invoice in invoicesList" :key="invoice.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-sm font-medium">{{ invoice.invoice_number }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ invoice.customer?.name || '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ formatDate(invoice.invoice_date) }}</td>
                        <td class="px-4 py-3 font-mono text-sm font-bold text-green-600">{{ formatCurrency(invoice.total) }}</td>
                        <td class="px-4 py-3">
                            <span :class="getStatusBadge(invoice.status)">{{ getStatusName(invoice.status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-1">
                                <button v-if="invoice.status === 'issued'" @click="collectPayment(invoice)" 
                                        class="p-1.5 text-green-600 hover:bg-green-50 rounded" title="تحصيل">💰</button>
                                <button @click="printInvoice(invoice)" 
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="طباعة">🖨️</button>
                                <button v-if="invoice.status !== 'cancelled' && invoice.status !== 'paid'" 
                                        @click="cancelInvoice(invoice)" 
                                        class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="إلغاء">❌</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="invoicesList.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">لا توجد فواتير</td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div v-if="props.invoices?.last_page > 1" class="px-4 py-3 border-t flex justify-center gap-2">
                <Link v-for="link in props.invoices.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="['px-3 py-1 rounded', link.active ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
                      v-html="link.label">
                </Link>
            </div>
        </div>
        
        <!-- New Invoice Modal -->
        <div v-if="showNewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">فاتورة جديدة</h3>
                    <button @click="showNewModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                
                <form @submit.prevent="createInvoice" class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">العميل *</label>
                        <select v-model="form.customer_id" required class="w-full px-4 py-2 border rounded-lg">
                            <option value="">اختر العميل</option>
                            <option v-for="c in props.customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الأصناف</label>
                        <div v-for="(item, i) in form.items" :key="i" class="flex gap-2 mb-2">
                            <input v-model="item.product_id" type="text" placeholder="كود المنتج" class="flex-1 px-3 py-2 border rounded">
                            <input v-model.number="item.quantity" type="number" placeholder="الكمية" min="1" class="w-24 px-3 py-2 border rounded">
                            <input v-model.number="item.unit_price" type="number" placeholder="السعر" step="0.01" class="w-32 px-3 py-2 border rounded">
                            <button type="button" @click="form.items.splice(i, 1)" class="px-3 py-2 text-red-600">🗑️</button>
                        </div>
                        <button type="button" @click="form.items.push({ product_id: '', quantity: 1, unit_price: 0 })"
                                class="text-blue-600 text-sm">+ إضافة صنف</button>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between text-lg font-bold">
                            <span>الإجمالي:</span>
                            <span>{{ formatCurrency(calculateTotal()) }}</span>
                        </div>
                    </div>
                </form>
                
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button type="button" @click="showNewModal = false" class="px-4 py-2 border rounded-lg">إلغاء</button>
                    <button @click="createInvoice" :disabled="form.processing"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        {{ form.processing ? 'جاري الإنشاء...' : 'إنشاء الفاتورة' }}
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.success" 
             class="fixed bottom-4 left-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 bg-green-600">
            {{ $page.props.flash.success }}
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    invoices: Object,
    customers: Array,
    stats: Object,
    filters: Object,
});

const showNewModal = ref(false);
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

const form = useForm({
    customer_id: '',
    items: [{ product_id: '', quantity: 1, unit_price: 0 }],
    notes: '',
});

const invoicesList = computed(() => props.invoices?.data || []);

const formatCurrency = (amount) => {
    if (!amount && amount !== 0) return '-';
    return new Intl.NumberFormat('ar-SA').format(amount) + ' ر.س';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const getStatusName = (status) => {
    const names = { draft: 'مسودة', issued: 'صادرة', paid: 'مدفوعة', cancelled: 'ملغية' };
    return names[status] || status;
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
        issued: 'px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
        paid: 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800',
        cancelled: 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800',
    };
    return badges[status] || '';
};

const calculateTotal = () => {
    return form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};

let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
};

const applyFilters = () => {
    router.get('/admin/invoices', {
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const openNewModal = () => {
    form.reset();
    form.items = [{ product_id: '', quantity: 1, unit_price: 0 }];
    showNewModal.value = true;
};

const createInvoice = () => {
    form.post('/admin/invoices', {
        onSuccess: () => { showNewModal.value = false; },
    });
};

const collectPayment = (invoice) => {
    if (confirm('هل تريد تحصيل هذه الفاتورة؟')) {
        router.post(`/admin/invoices/${invoice.id}/pay`);
    }
};

const cancelInvoice = (invoice) => {
    if (confirm('هل تريد إلغاء هذه الفاتورة؟')) {
        router.post(`/admin/invoices/${invoice.id}/cancel`);
    }
};

const printInvoice = (invoice) => {
    window.print();
};
</script>
