<template>
    <AdminLayout title="العملاء">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">العملاء</h2>
                    <p class="text-gray-600">إدارة قاعدة العملاء مع نظام التسعير المتعدد</p>
                </div>
                <button @click="openAddModal" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <span>➕</span>
                    <span>إضافة عميل</span>
                </button>
            </div>
        </template>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-gray-500 text-sm">إجمالي العملاء</p>
                <p class="text-2xl font-bold text-gray-800">{{ props.stats?.total || 0 }}</p>
            </div>
            <div class="bg-blue-50 rounded-xl shadow-sm p-4">
                <p class="text-blue-600 text-sm">تجزئة</p>
                <p class="text-2xl font-bold text-blue-800">{{ props.stats?.retail || 0 }}</p>
            </div>
            <div class="bg-purple-50 rounded-xl shadow-sm p-4">
                <p class="text-purple-600 text-sm">جملة</p>
                <p class="text-2xl font-bold text-purple-800">{{ props.stats?.wholesale || 0 }}</p>
            </div>
            <div class="bg-red-50 rounded-xl shadow-sm p-4">
                <p class="text-red-600 text-sm">مديونيات</p>
                <p class="text-2xl font-bold text-red-800">{{ formatCurrency(Math.abs(props.stats?.total_debt || 0)) }}</p>
            </div>
            <div class="bg-green-50 rounded-xl shadow-sm p-4">
                <p class="text-green-600 text-sm">حد الائتمان</p>
                <p class="text-lg font-bold text-green-800">{{ formatCurrency(props.stats?.total_credit_limit || 0) }}</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input v-model="searchQuery" @input="debouncedSearch" type="text" 
                           placeholder="🔍 بحث بالاسم أو الهاتف..."
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <select v-model="typeFilter" @change="applyFilters" class="px-4 py-2 border rounded-lg">
                    <option value="">كل الأنواع</option>
                    <option value="retail">تجزئة</option>
                    <option value="semi_wholesale">نصف جملة</option>
                    <option value="wholesale">جملة</option>
                    <option value="distributor">موزع</option>
                </select>
                <select v-model="balanceFilter" @change="applyFilters" class="px-4 py-2 border rounded-lg">
                    <option value="">كل الأرصدة</option>
                    <option value="debit">مدين</option>
                    <option value="credit">دائن</option>
                </select>
            </div>
        </div>
        
        <!-- Customers Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">العميل</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">الهاتف</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">النوع</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">حد الائتمان</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">الرصيد</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="customer in customersList" :key="customer.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800">{{ customer.name }}</p>
                            <p class="text-xs text-gray-500">{{ customer.customer_number }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-sm">{{ customer.phone }}</td>
                        <td class="px-4 py-3">
                            <span :class="getTypeBadge(customer.customer_type)">{{ getTypeName(customer.customer_type) }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-sm">{{ formatCurrency(customer.credit_limit) }}</td>
                        <td class="px-4 py-3">
                            <span :class="customer.balance < 0 ? 'text-red-600 font-bold' : 'text-green-600'" class="font-mono">
                                {{ formatCurrency(Math.abs(customer.balance || 0)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-1">
                                <button @click="openEditModal(customer)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded">✏️</button>
                                <button @click="deleteCustomer(customer)" class="p-1.5 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="customersList.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">لا يوجد عملاء</td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div v-if="props.customers?.last_page > 1" class="px-4 py-3 border-t flex justify-center gap-2">
                <Link v-for="link in props.customers.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="['px-3 py-1 rounded', link.active ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
                      v-html="link.label">
                </Link>
            </div>
        </div>
        
        <!-- Add/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">{{ editingCustomer ? 'تعديل عميل' : 'إضافة عميل جديد' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                
                <form @submit.prevent="saveCustomer" class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">اسم العميل *</label>
                            <input v-model="form.name" type="text" required class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الهاتف *</label>
                            <input v-model="form.phone" type="tel" required class="w-full px-4 py-2 border rounded-lg">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                            <input v-model="form.email" type="email" class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">نوع العميل *</label>
                            <select v-model="form.customer_type" required class="w-full px-4 py-2 border rounded-lg">
                                <option value="retail">تجزئة</option>
                                <option value="semi_wholesale">نصف جملة</option>
                                <option value="quarter_wholesale">ربع جملة</option>
                                <option value="wholesale">جملة</option>
                                <option value="distributor">موزع</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">حد الائتمان</label>
                            <input v-model.number="form.credit_limit" type="number" step="0.01" class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">مدة الدفع (أيام)</label>
                            <select v-model.number="form.payment_terms_days" class="w-full px-4 py-2 border rounded-lg">
                                <option value="0">نقدي</option>
                                <option value="15">15 يوم</option>
                                <option value="30">30 يوم</option>
                                <option value="60">60 يوم</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الخصم %</label>
                            <input v-model.number="form.discount_percentage" type="number" step="0.1" class="w-full px-4 py-2 border rounded-lg">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                        <textarea v-model="form.address" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea>
                    </div>
                </form>
                
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button type="button" @click="closeModal" class="px-4 py-2 border rounded-lg hover:bg-gray-100">إلغاء</button>
                    <button @click="saveCustomer" :disabled="form.processing"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        {{ form.processing ? 'جاري الحفظ...' : (editingCustomer ? 'حفظ' : 'إضافة') }}
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
    customers: Object,
    stats: Object,
    filters: Object,
});

const showModal = ref(false);
const editingCustomer = ref(null);
const searchQuery = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const balanceFilter = ref(props.filters?.balance || '');

const form = useForm({
    name: '',
    phone: '',
    email: '',
    customer_type: 'retail',
    credit_limit: 0,
    payment_terms_days: 0,
    discount_percentage: 0,
    address: '',
});

const customersList = computed(() => props.customers?.data || []);

const formatCurrency = (amount) => {
    if (!amount && amount !== 0) return '-';
    return new Intl.NumberFormat('ar-SA').format(amount) + ' ر.س';
};

const getTypeName = (type) => {
    const names = { retail: 'تجزئة', semi_wholesale: 'نصف جملة', quarter_wholesale: 'ربع جملة', wholesale: 'جملة', distributor: 'موزع' };
    return names[type] || type;
};

const getTypeBadge = (type) => {
    const badges = {
        retail: 'px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
        wholesale: 'px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800',
        distributor: 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800',
    };
    return badges[type] || 'px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
};

let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
};

const applyFilters = () => {
    router.get('/admin/customers', {
        search: searchQuery.value || undefined,
        type: typeFilter.value || undefined,
        balance: balanceFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const openAddModal = () => {
    editingCustomer.value = null;
    form.reset();
    form.customer_type = 'retail';
    showModal.value = true;
};

const openEditModal = (customer) => {
    editingCustomer.value = customer;
    Object.assign(form, customer);
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCustomer.value = null;
};

const saveCustomer = () => {
    if (editingCustomer.value) {
        form.put(`/admin/customers/${editingCustomer.value.id}`, { onSuccess: () => closeModal() });
    } else {
        form.post('/admin/customers', { onSuccess: () => closeModal() });
    }
};

const deleteCustomer = (customer) => {
    if (confirm(`هل أنت متأكد من حذف "${customer.name}"؟`)) {
        router.delete(`/admin/customers/${customer.id}`);
    }
};
</script>
