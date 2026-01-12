<template>
    <AdminLayout title="المنتجات">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">المنتجات</h2>
                    <p class="text-gray-600">إدارة منتجات المتجر مع التسعير المتعدد</p>
                </div>
                <button @click="openAddModal" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <span>➕</span>
                    <span>إضافة منتج</span>
                </button>
            </div>
        </template>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">إجمالي المنتجات</p>
                        <p class="text-2xl font-bold text-gray-800">{{ props.products?.total || 0 }}</p>
                    </div>
                    <span class="text-3xl">📦</span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">منتجات نشطة</p>
                        <p class="text-2xl font-bold text-green-600">{{ activeCount }}</p>
                    </div>
                    <span class="text-3xl">✅</span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">منخفض المخزون</p>
                        <p class="text-2xl font-bold text-red-600">{{ lowStockCount }}</p>
                    </div>
                    <span class="text-3xl">⚠️</span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">قيمة المخزون</p>
                        <p class="text-xl font-bold text-blue-600">{{ formatCurrency(totalValue) }}</p>
                    </div>
                    <span class="text-3xl">💰</span>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input v-model="searchQuery" @input="debouncedSearch" type="text" 
                           placeholder="🔍 بحث بالاسم، SKU، أو الباركود..."
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <select v-model="categoryFilter" @change="applyFilters" class="px-4 py-2 border rounded-lg">
                    <option value="">كل الفئات</option>
                    <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
                <select v-model="statusFilter" @change="applyFilters" class="px-4 py-2 border rounded-lg">
                    <option value="">كل الحالات</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
        </div>
        
        <!-- Products Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">المنتج</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">SKU</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">الفئة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600 bg-red-50">التكلفة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600 bg-blue-50">تجزئة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600 bg-purple-50">جملة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">المخزون</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="product in productsList" :key="product.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-xl">📦</div>
                                    <div>
                                        <p class="font-medium text-gray-800">{{ product.name }}</p>
                                        <p class="text-xs text-gray-500">{{ product.barcode }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-sm">{{ product.sku }}</td>
                            <td class="px-4 py-3 text-sm">{{ product.category?.name || '-' }}</td>
                            <td class="px-4 py-3 bg-red-50 font-mono text-sm text-red-600">{{ formatCurrency(product.cost_price) }}</td>
                            <td class="px-4 py-3 bg-blue-50 font-mono text-sm text-blue-600">{{ formatCurrency(product.selling_price) }}</td>
                            <td class="px-4 py-3 bg-purple-50 font-mono text-sm text-purple-600">{{ formatCurrency(product.wholesale_price) }}</td>
                            <td class="px-4 py-3">
                                <span :class="product.stock_count < 10 ? 'text-red-600 font-bold' : 'text-gray-600'">
                                    {{ product.stock_count || 0 }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-1">
                                    <button @click="openEditModal(product)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="تعديل">✏️</button>
                                    <button @click="deleteProduct(product)" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="حذف">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="productsList.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                لا توجد منتجات
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="props.products?.last_page > 1" class="px-4 py-3 border-t flex justify-center gap-2">
                <Link v-for="link in props.products.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="['px-3 py-1 rounded', link.active ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
                      v-html="link.label">
                </Link>
            </div>
        </div>
        
        <!-- Add/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">{{ editingProduct ? 'تعديل منتج' : 'إضافة منتج جديد' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                
                <form @submit.prevent="saveProduct" class="flex-1 overflow-y-auto p-6 space-y-4">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">اسم المنتج *</label>
                            <input v-model="form.name" type="text" required
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                            <input v-model="form.sku" type="text" required
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الباركود</label>
                            <input v-model="form.barcode" type="text"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الفئة</label>
                            <select v-model="form.category_id" class="w-full px-4 py-2 border rounded-lg">
                                <option value="">بدون فئة</option>
                                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Pricing -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold mb-3">💰 التسعير</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">التكلفة *</label>
                                <input v-model.number="form.cost_price" type="number" step="0.01" required
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">سعر التجزئة *</label>
                                <input v-model.number="form.selling_price" type="number" step="0.01" required
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">سعر الجملة</label>
                                <input v-model.number="form.wholesale_price" type="number" step="0.01"
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stock -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">المخزون الحالي</label>
                            <input v-model.number="form.stock_count" type="number"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الحد الأدنى</label>
                            <input v-model.number="form.min_stock" type="number"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div class="flex items-center pt-6">
                            <label class="flex items-center gap-2">
                                <input v-model="form.is_active" type="checkbox" class="w-4 h-4">
                                <span>نشط</span>
                            </label>
                        </div>
                    </div>
                </form>
                
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button type="button" @click="closeModal" class="px-4 py-2 border rounded-lg hover:bg-gray-100">إلغاء</button>
                    <button @click="saveProduct" :disabled="form.processing"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        {{ form.processing ? 'جاري الحفظ...' : (editingProduct ? 'حفظ التعديلات' : 'إضافة المنتج') }}
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
    products: Object,
    categories: Array,
    filters: Object,
});

const showModal = ref(false);
const editingProduct = ref(null);
const searchQuery = ref(props.filters?.search || '');
const categoryFilter = ref(props.filters?.category || '');
const statusFilter = ref(props.filters?.status || '');

const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    category_id: '',
    cost_price: 0,
    selling_price: 0,
    wholesale_price: null,
    stock_count: 0,
    min_stock: 0,
    is_active: true,
});

const productsList = computed(() => props.products?.data || []);
const activeCount = computed(() => productsList.value.filter(p => p.is_active).length);
const lowStockCount = computed(() => productsList.value.filter(p => (p.stock_count || 0) < 10).length);
const totalValue = computed(() => productsList.value.reduce((sum, p) => sum + ((p.cost_price || 0) * (p.stock_count || 0)), 0));

const formatCurrency = (amount) => {
    if (!amount && amount !== 0) return '-';
    return new Intl.NumberFormat('ar-SA').format(amount) + ' ر.س';
};

let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
};

const applyFilters = () => {
    router.get('/admin/products', {
        search: searchQuery.value || undefined,
        category: categoryFilter.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const openAddModal = () => {
    editingProduct.value = null;
    form.reset();
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (product) => {
    editingProduct.value = product;
    form.name = product.name;
    form.sku = product.sku;
    form.barcode = product.barcode || '';
    form.category_id = product.category_id || '';
    form.cost_price = product.cost_price;
    form.selling_price = product.selling_price;
    form.wholesale_price = product.wholesale_price;
    form.stock_count = product.stock_count || 0;
    form.min_stock = product.min_stock || 0;
    form.is_active = product.is_active;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingProduct.value = null;
    form.reset();
};

const saveProduct = () => {
    if (editingProduct.value) {
        form.put(`/admin/products/${editingProduct.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/products', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteProduct = (product) => {
    if (confirm(`هل أنت متأكد من حذف "${product.name}"؟`)) {
        router.delete(`/admin/products/${product.id}`);
    }
};
</script>
