<template>
    <div class="min-h-screen bg-gray-100" dir="rtl">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 right-0 w-64 bg-gradient-to-b from-blue-800 to-blue-900 text-white z-50">
            <div class="p-4 border-b border-blue-700">
                <h1 class="text-xl font-bold">TWINX ERP</h1>
                <p class="text-blue-200 text-sm">نظام إدارة الموارد</p>
            </div>
            
            <nav class="p-4">
                <a :href="route('dashboard')" 
                   :class="['flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors', 
                            isActive('dashboard') ? 'bg-blue-700' : 'hover:bg-blue-700']">
                    <span>📊</span>
                    <span>لوحة التحكم</span>
                </a>
                <a :href="route('products.index')" 
                   :class="['flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors',
                            isActive('products') ? 'bg-blue-700' : 'hover:bg-blue-700']">
                    <span>📦</span>
                    <span>المنتجات</span>
                </a>
                <a :href="route('customers.index')" 
                   :class="['flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors',
                            isActive('customers') ? 'bg-blue-700' : 'hover:bg-blue-700']">
                    <span>👥</span>
                    <span>العملاء</span>
                </a>
                <a :href="route('invoices.index')" 
                   :class="['flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors',
                            isActive('invoices') ? 'bg-blue-700' : 'hover:bg-blue-700']">
                    <span>🧾</span>
                    <span>الفواتير</span>
                </a>
                <a :href="route('reports.index')" 
                   :class="['flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors',
                            isActive('reports') ? 'bg-blue-700' : 'hover:bg-blue-700']">
                    <span>📈</span>
                    <span>التقارير</span>
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-700 mb-2">
                    <span>⚙️</span>
                    <span>الإعدادات</span>
                </a>
            </nav>
            
            <!-- User Info -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-blue-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <span>👤</span>
                    </div>
                    <div>
                        <p class="font-medium text-sm">{{ $page.props.auth?.user?.name || 'مستخدم' }}</p>
                        <p class="text-blue-200 text-xs">مدير النظام</p>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="mr-64 min-h-screen">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm px-8 py-4 flex justify-between items-center">
                <div>
                    <slot name="header">
                        <h2 class="text-xl font-semibold text-gray-800">{{ title }}</h2>
                    </slot>
                </div>
                <div class="flex items-center gap-4">
                    <button class="p-2 text-gray-500 hover:text-gray-700 relative">
                        <span class="text-xl">🔔</span>
                        <span class="absolute top-0 left-0 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <a href="/pos/index.html" target="_blank" 
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        🛒 نقطة البيع
                    </a>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="p-8">
                <slot></slot>
            </div>
        </main>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'TWINX ERP'
    }
});

const page = usePage();

const isActive = (routeName) => {
    const currentRoute = page.url;
    if (routeName === 'dashboard') return currentRoute === '/admin' || currentRoute === '/admin/dashboard';
    return currentRoute.includes(routeName);
};

const route = (name) => {
    const routes = {
        'dashboard': '/admin/dashboard',
        'products.index': '/admin/products',
        'customers.index': '/admin/customers',
        'invoices.index': '/admin/invoices',
        'reports.index': '/admin/reports',
    };
    return routes[name] || '#';
};
</script>
