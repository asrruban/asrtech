<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProductForm from '@/modules/admin/catalog/components/ProductForm.vue';
defineProps([
    'categories',
    'groups',
    'productTypes',
    'billingCycles',
    'currency',
]);
</script>

<template>
    <Head title="Create product" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Catalog</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                Create product
            </h1>
            <p class="mt-1 text-muted-foreground">
                Add an ASRTech module, template, license, or service.
            </p>
        </div>
        <ProductForm
            :categories="categories"
            :groups="groups"
            :product-types="productTypes"
            :billing-cycles="billingCycles"
            :currency="currency"
        />
    </div>
</template>
