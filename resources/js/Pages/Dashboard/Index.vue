<template>
    <AdminLayout title="لوحة التحكم">
        <template #header>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">لوحة التحكم</h2>
                <p class="text-gray-600">مرحباً بك في نظام TWINX ERP</p>
            </div>
        </template>
        
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-blue-100">مبيعات اليوم</p>
                        <p class="text-3xl font-bold mt-2">{{ formatCurrency(kpis.todaySales) }}</p>
                        <p class="text-blue-200 text-sm mt-1">{{ kpis.todayOrders }} طلب</p>
                    </div>
                    <span class="text-4xl">💰</span>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-green-100">مبيعات الشهر</p>
                        <p class="text-3xl font-bold mt-2">{{ formatCurrency(kpis.monthSales) }}</p>
                        <p class="text-green-200 text-sm mt-1">+{{ kpis.monthGrowth }}% من الشهر السابق</p>
                    </div>
                    <span class="text-4xl">📈</span>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-orange-100">فواتير معلقة</p>
                        <p class="text-3xl font-bold mt-2">{{ kpis.pendingInvoices }}</p>
                        <p class="text-orange-200 text-sm mt-1">{{ formatCurrency(kpis.pendingAmount) }}</p>
                    </div>
                    <span class="text-4xl">⏳</span>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-purple-100">منتجات منخفضة المخزون</p>
                        <p class="text-3xl font-bold mt-2">{{ kpis.lowStock }}</p>
                        <p class="text-purple-200 text-sm mt-1">تحتاج إعادة طلب</p>
                    </div>
                    <span class="text-4xl">📦</span>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Sales Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">مبيعات الأسبوع</h3>
                <div class="h-64 flex items-end gap-2">
                    <div v-for="(day, index) in weeklyData" :key="index" class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-blue-500 rounded-t transition-all duration-500 hover:bg-blue-600"
                             :style="{ height: (day.value / maxSales * 200) + 'px' }"></div>
                        <span class="text-xs text-gray-500 mt-2">{{ day.day }}</span>
                        <span class="text-xs font-medium">{{ formatCurrency(day.value) }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Category Distribution -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">توزيع المبيعات حسب الفئة</h3>
                <div class="space-y-4">
                    <div v-for="cat in categoryData" :key="cat.name" class="flex items-center gap-4">
                        <span class="text-2xl">{{ cat.emoji }}</span>
                        <div class="flex-1">
                            <div class="flex justify-between mb-1">
                                <span class="font-medium">{{ cat.name }}</span>
                                <span class="text-gray-500">{{ cat.percentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-500"
                                     :class="cat.color"
                                     :style="{ width: cat.percentage + '%' }"></div>
                            </div>
                        </div>
                        <span class="font-medium text-gray-600">{{ formatCurrency(cat.amount) }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Orders -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">أحدث الطلبات</h3>
                    <a href="/admin/invoices" class="text-blue-600 hover:underline text-sm">عرض الكل</a>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="text-right text-gray-500 border-b">
                            <th class="pb-3 font-medium">رقم الطلب</th>
                            <th class="pb-3 font-medium">العميل</th>
                            <th class="pb-3 font-medium">المبلغ</th>
                            <th class="pb-3 font-medium">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in recentOrders" :key="order.id" class="border-b last:border-0">
                            <td class="py-3 font-mono text-sm">{{ order.number }}</td>
                            <td class="py-3">{{ order.customer }}</td>
                            <td class="py-3 font-medium">{{ formatCurrency(order.amount) }}</td>
                            <td class="py-3">
                                <span :class="getStatusClass(order.status)">{{ getStatusText(order.status) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Top Products -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">أفضل المنتجات</h3>
                    <a href="/admin/products" class="text-blue-600 hover:underline text-sm">عرض الكل</a>
                </div>
                <div class="space-y-4">
                    <div v-for="product in topProducts" :key="product.id" class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-2xl">
                            {{ product.emoji }}
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">{{ product.name }}</p>
                            <p class="text-sm text-gray-500">{{ product.sold }} مبيعة</p>
                        </div>
                        <p class="font-semibold text-green-600">{{ formatCurrency(product.revenue) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const kpis = ref({
    todaySales: 28500,
    todayOrders: 45,
    monthSales: 425000,
    monthGrowth: 12,
    pendingInvoices: 8,
    pendingAmount: 35000,
    lowStock: 5
});

const weeklyData = ref([
    { day: 'السبت', value: 35000 },
    { day: 'الأحد', value: 42000 },
    { day: 'الاثنين', value: 28000 },
    { day: 'الثلاثاء', value: 51000 },
    { day: 'الأربعاء', value: 39000 },
    { day: 'الخميس', value: 45000 },
    { day: 'الجمعة', value: 28500 },
]);

const maxSales = computed(() => Math.max(...weeklyData.value.map(d => d.value)));

const categoryData = ref([
    { name: 'إلكترونيات', emoji: '📱', percentage: 45, amount: 191250, color: 'bg-blue-500' },
    { name: 'ملابس', emoji: '👕', percentage: 25, amount: 106250, color: 'bg-purple-500' },
    { name: 'طعام', emoji: '🍔', percentage: 20, amount: 85000, color: 'bg-orange-500' },
    { name: 'مشروبات', emoji: '🥤', percentage: 10, amount: 42500, color: 'bg-green-500' },
]);

const recentOrders = ref([
    { id: 1, number: '#1234', customer: 'أحمد محمد', amount: 1500, status: 'paid' },
    { id: 2, number: '#1233', customer: 'سارة علي', amount: 2300, status: 'issued' },
    { id: 3, number: '#1232', customer: 'محمد خالد', amount: 890, status: 'paid' },
    { id: 4, number: '#1231', customer: 'نورة سعد', amount: 3200, status: 'draft' },
    { id: 5, number: '#1230', customer: 'عبدالله أحمد', amount: 1100, status: 'paid' },
]);

const topProducts = ref([
    { id: 1, name: 'آيفون 15 برو', emoji: '📱', sold: 150, revenue: 675000 },
    { id: 2, name: 'ماك بوك برو', emoji: '💻', sold: 85, revenue: 722500 },
    { id: 3, name: 'أبل واتش', emoji: '⌚', sold: 120, revenue: 180000 },
    { id: 4, name: 'سماعات AirPods', emoji: '🎧', sold: 200, revenue: 160000 },
]);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA').format(amount) + ' ر.س';
};

const getStatusClass = (status) => {
    const classes = {
        paid: 'px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium',
        issued: 'px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium',
        draft: 'px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium',
    };
    return classes[status] || classes.draft;
};

const getStatusText = (status) => {
    const texts = { paid: 'مدفوعة', issued: 'صادرة', draft: 'مسودة' };
    return texts[status] || status;
};
</script>
