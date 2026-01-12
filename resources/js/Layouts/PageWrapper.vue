<template>
    <AdminLayout :title="pageTitle">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ pageTitle }}</h2>
                    <p class="text-gray-600">{{ subtitle }}</p>
                </div>
                <slot name="actions"></slot>
            </div>
        </template>
        
        <slot></slot>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from './AdminLayout.vue';

defineProps({
    pageTitle: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        default: ''
    }
});
</script>
