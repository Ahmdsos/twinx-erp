<template>
    <div class="min-h-screen bg-gray-100" dir="rtl">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 right-0 w-64 bg-gradient-to-b from-blue-800 to-blue-900 text-white">
            <div class="p-4 border-b border-blue-700">
                <h1 class="text-xl font-bold">TWINX ERP</h1>
                <p class="text-blue-200 text-sm">نظام إدارة الموارد</p>
            </div>
            
            <nav class="p-4">
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-blue-700 mb-2">
                    <span>📊</span>
                    <span>لوحة التحكم</span>
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-700 mb-2">
                    <span>📦</span>
                    <span>المنتجات</span>
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-700 mb-2">
                    <span>👥</span>
                    <span>العملاء</span>
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-700 mb-2">
                    <span>🧾</span>
                    <span>الفواتير</span>
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-700 mb-2">
                    <span>📈</span>
                    <span>التقارير</span>
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-700 mb-2">
                    <span>⚙️</span>
                    <span>الإعدادات</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="mr-64 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800">لوحة التحكم</h2>
                <p class="text-gray-600">مرحباً بك في نظام TWINX ERP</p>
            </div>
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-blue-100">المبيعات اليوم</p>
                            <p class="text-3xl font-bold mt-2">{{ formatCurrency(kpis.sales) }}</p>
                        </div>
                        <span class="text-4xl">💰</span>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-green-100">الطلبات</p>
                            <p class="text-3xl font-bold mt-2">{{ kpis.orders }}</p>
                        </div>
                        <span class="text-4xl">📦</span>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-orange-100">فواتير معلقة</p>
                            <p class="text-3xl font-bold mt-2">{{ kpis.pending }}</p>
                        </div>
                        <span class="text-4xl">⏳</span>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-purple-100">عملاء جدد</p>
                            <p class="text-3xl font-bold mt-2">{{ kpis.newCustomers }}</p>
                        </div>
                        <span class="text-4xl">👥</span>
                    </div>
                </div>
            </div>
            
            <!-- Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Orders -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">أحدث الطلبات</h3>
                    <table class="w-full">
                        <thead>
                            <tr class="text-right text-gray-500 border-b">
                                <th class="pb-3">رقم الطلب</th>
                                <th class="pb-3">العميل</th>
                                <th class="pb-3">المبلغ</th>
                                <th class="pb-3">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b">
                                <td class="py-3">#1234</td>
                                <td class="py-3">أحمد محمد</td>
                                <td class="py-3">1,500 ر.س</td>
                                <td class="py-3"><span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">مكتمل</span></td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-3">#1233</td>
                                <td class="py-3">سارة علي</td>
                                <td class="py-3">2,300 ر.س</td>
                                <td class="py-3"><span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">قيد التنفيذ</span></td>
                            </tr>
                            <tr>
                                <td class="py-3">#1232</td>
                                <td class="py-3">محمد خالد</td>
                                <td class="py-3">890 ر.س</td>
                                <td class="py-3"><span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">مكتمل</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Top Products -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">أفضل المنتجات</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-2xl">📱</div>
                            <div class="flex-1">
                                <p class="font-medium">آيفون 15 برو</p>
                                <p class="text-sm text-gray-500">150 مبيعة</p>
                            </div>
                            <p class="font-semibold text-green-600">45,000 ر.س</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-2xl">💻</div>
                            <div class="flex-1">
                                <p class="font-medium">ماك بوك برو</p>
                                <p class="text-sm text-gray-500">85 مبيعة</p>
                            </div>
                            <p class="font-semibold text-green-600">38,000 ر.س</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center text-2xl">⌚</div>
                            <div class="flex-1">
                                <p class="font-medium">أبل واتش</p>
                                <p class="text-sm text-gray-500">120 مبيعة</p>
                            </div>
                            <p class="font-semibold text-green-600">22,000 ر.س</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const kpis = ref({
    sales: 125000,
    orders: 45,
    pending: 12,
    newCustomers: 8
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('ar-SA', { 
        style: 'decimal',
        minimumFractionDigits: 0 
    }).format(value) + ' ر.س';
};
</script>
