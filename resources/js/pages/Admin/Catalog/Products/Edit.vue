<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PackageCheck } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import ProductForm from '@/modules/admin/catalog/components/ProductForm.vue';
defineProps([
    'categories',
    'groups',
    'productTypes',
    'billingCycles',
    'currency',
    'product',
]);
</script>

<template>
    <Head :title="`Edit ${product.name}`" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"
        >
            <div>
                <p class="text-sm font-medium text-primary">Catalog</p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Edit product
                </h1>
                <p class="mt-1 text-muted-foreground">
                    Update {{ product.name }} and its product type.
                </p>
            </div>
            <Button as-child variant="outline">
                <Link :href="`/admin/products/${product.id}/releases`">
                    <PackageCheck class="size-4" /> Manage releases
                </Link>
            </Button>
        </div>
        <ProductForm
            :categories="categories"
            :groups="groups"
            :product-types="productTypes"
            :billing-cycles="billingCycles"
            :currency="currency"
            :product="product"
        />
    </div>
</template>
