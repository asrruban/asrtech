<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import ProductCard from '@/modules/client/components/ProductCard.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
const props = defineProps([
    'filters',
    'productTypes',
    'categories',
    'products',
]);
const search = ref(props.filters.search);
const type = ref(props.filters.type);
const applyFilters = () =>
    router.get(
        '/products',
        { search: search.value, type: type.value },
        { preserveState: true, replace: true },
    );
const paginationLabel = (value) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <SeoHead
        title="Products"
        description="Browse ASRTech WHMCS modules, templates, licenses, and professional web development services."
    />
    <section class="border-b bg-slate-950 py-18 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p
                class="text-sm font-semibold tracking-widest text-blue-400 uppercase"
            >
                ASRTech catalog
            </p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">
                Products and services
            </h1>
            <p class="mt-4 max-w-2xl text-lg text-slate-300">
                Flexible one-time, monthly, and yearly options for hosting
                companies and growing businesses.
            </p>
        </div>
    </section>
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <form
            class="grid gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:grid-cols-[1fr_240px_auto]"
            @submit.prevent="applyFilters"
        >
            <Input
                v-model="search"
                placeholder="Search products and services"
            />
            <select
                v-model="type"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All product types</option>
                <option
                    v-for="productType in productTypes"
                    :key="productType.key"
                    :value="productType.key"
                >
                    {{ productType.name }}
                </option>
            </select>
            <Button type="submit"><Search class="size-4" /> Search</Button>
        </form>

        <div
            v-if="products.data.length"
            class="mt-10 grid gap-7 md:grid-cols-2 lg:grid-cols-3"
        >
            <ProductCard
                v-for="product in products.data"
                :key="product.slug"
                :product="product"
            />
        </div>
        <div
            v-else
            class="mt-10 rounded-2xl border border-dashed p-16 text-center text-muted-foreground"
        >
            No products match these filters.
        </div>

        <div
            v-if="products.last_page > 1"
            class="mt-10 flex items-center justify-between gap-4 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ products.from }}–{{ products.to }} of
                {{ products.total }}
            </p>
            <div class="flex gap-2">
                <template v-for="link in products.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        as-child
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        ><Link :href="link.url">{{
                            paginationLabel(link.label)
                        }}</Link></Button
                    >
                    <Button v-else size="sm" variant="outline" disabled>{{
                        paginationLabel(link.label)
                    }}</Button>
                </template>
            </div>
        </div>
    </section>
</template>
