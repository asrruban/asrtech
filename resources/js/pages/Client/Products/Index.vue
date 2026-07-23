<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import ProductCard from '@/modules/client/components/ProductCard.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

const props = defineProps([
    'filters',
    'productTypes',
    'categories',
    'products',
    'landing',
]);
const search = ref(props.filters.search);
const type = ref(props.filters.type);
const pageUrl = computed(() => props.landing?.url ?? '/products');
const pageTitle = computed(
    () => props.landing?.name ?? 'Products and services',
);
const pageDescription = computed(
    () =>
        props.landing?.description ||
        'Flexible one-time, monthly, and yearly options for hosting companies and growing businesses.',
);
const eyebrow = computed(() =>
    props.landing?.kind === 'subcategory'
        ? 'Product subcategory'
        : props.landing?.kind === 'category'
          ? 'Product category'
          : 'ASRTech catalog',
);
const applyFilters = () =>
    router.get(
        pageUrl.value,
        { search: search.value, type: type.value },
        { preserveState: true, replace: true },
    );
const paginationLabel = (value) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <SeoHead
        :title="pageTitle"
        :description="pageDescription"
        :seo="landing?.seo"
    />

    <section class="border-b bg-slate-950 py-18 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                v-if="landing?.parent"
                class="mb-4 flex items-center gap-2 text-sm text-slate-400"
            >
                <Link href="/products" class="hover:text-white">Products</Link>
                <span>/</span>
                <Link :href="landing.parent.url" class="hover:text-white">
                    {{ landing.parent.name }}
                </Link>
            </div>
            <p
                class="text-sm font-semibold tracking-widest text-blue-400 uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">
                {{ pageTitle }}
            </h1>
            <p class="mt-4 max-w-2xl text-lg text-slate-300">
                {{ pageDescription }}
            </p>

            <div
                v-if="landing?.subcategories?.length"
                class="mt-7 flex flex-wrap gap-2"
            >
                <Button
                    v-for="subcategory in landing.subcategories"
                    :key="subcategory.id"
                    as-child
                    size="sm"
                    :variant="subcategory.active ? 'default' : 'secondary'"
                >
                    <Link :href="subcategory.url">
                        {{ subcategory.name }}
                    </Link>
                </Button>
            </div>
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

        <nav
            v-if="categories.length"
            aria-label="Product categories"
            class="mt-6 flex flex-wrap gap-2"
        >
            <Button
                as-child
                size="sm"
                :variant="landing ? 'outline' : 'default'"
            >
                <Link href="/products">All products</Link>
            </Button>
            <Button
                v-for="category in categories"
                :key="category.id"
                as-child
                size="sm"
                :variant="
                    landing?.parent?.name === category.name ||
                    (landing?.kind === 'category' &&
                        landing?.name === category.name)
                        ? 'default'
                        : 'outline'
                "
            >
                <Link :href="category.url">{{ category.name }}</Link>
            </Button>
        </nav>

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
                    >
                        <Link :href="link.url">
                            {{ paginationLabel(link.label) }}
                        </Link>
                    </Button>
                    <Button v-else size="sm" variant="outline" disabled>
                        {{ paginationLabel(link.label) }}
                    </Button>
                </template>
            </div>
        </div>
    </section>
</template>
