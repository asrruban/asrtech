<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Search, SlidersHorizontal } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import ProductCard from '@/modules/client/components/ProductCard.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

const props = defineProps([
    'filters',
    'productTypes',
    'categories',
    'products',
    'landing',
]);
const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');
const page = usePage();
const versionPlatforms = [
    { key: 'whmcs_version', label: 'WHMCS version' },
    { key: 'wordpress_version', label: 'WordPress version' },
    { key: 'php_version', label: 'PHP version' },
];
const versions = reactive<Record<string, string>>({
    whmcs_version: props.filters.whmcs_version ?? '',
    wordpress_version: props.filters.wordpress_version ?? '',
    php_version: props.filters.php_version ?? '',
});
watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        type.value = filters.type ?? '';

        for (const platform of versionPlatforms) {
            versions[platform.key] = filters[platform.key] ?? '';
        }
    },
);
const hasFilters = computed(
    () =>
        !!search.value || !!type.value || Object.values(versions).some(Boolean),
);
const activeFilterData = computed(() => ({
    search: search.value,
    type: type.value,
    ...versions,
}));
const filteredUrl = (url: string) => {
    const query = new URLSearchParams(
        Object.entries(activeFilterData.value).filter(([, value]) => !!value),
    );

    return `${url}${query.size ? `?${query.toString()}` : ''}`;
};
const filtering = ref(false);
const pageUrl = computed(() => props.landing?.url ?? '/products');
const pageTitle = computed(() => props.landing?.name ?? 'Built to do more.');
const pageDescription = computed(
    () =>
        props.landing?.description ||
        'Explore the ASR Tech catalog. Find product details, available plans, and documentation for your next project.',
);
const applyFilters = () =>
    router.get(pageUrl.value, activeFilterData.value, {
        preserveState: true,
        replace: true,
        onStart: () => {
            filtering.value = true;
        },
        onFinish: () => {
            filtering.value = false;
        },
    });
const clearFilters = () => {
    search.value = '';
    type.value = '';

    for (const platform of versionPlatforms) {
        versions[platform.key] = '';
    }

    applyFilters();
};
const paginationLabel = (value: string) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <SeoHead
        :title="landing?.name || 'Products'"
        :description="pageDescription"
        :seo="landing?.seo"
    />
    <header class="page-intro border-b border-border bg-muted">
        <div class="site-container py-16 sm:py-20">
            <nav
                v-if="landing"
                aria-label="Breadcrumb"
                class="mb-6 flex flex-wrap gap-2 text-sm text-muted-foreground"
            >
                <Link
                    :href="filteredUrl('/products')"
                    class="hover:text-primary"
                    >Products</Link
                >
                <template v-if="landing.parent"
                    ><span aria-hidden="true">/</span
                    ><Link
                        :href="landing.parent.url"
                        class="hover:text-primary"
                        >{{ landing.parent.name }}</Link
                    ></template
                >
            </nav>
            <div class="grid gap-7 lg:grid-cols-[1.3fr_1fr] lg:items-end">
                <div>
                    <p class="section-kicker">The ASR Tech catalog</p>
                    <h1 class="display-title mt-4 max-w-3xl">
                        {{ pageTitle }}
                    </h1>
                </div>
                <p class="body-copy max-w-lg">{{ pageDescription }}</p>
            </div>
            <nav
                v-if="landing?.subcategories?.length"
                aria-label="Product subcategories"
                class="mt-8 flex flex-wrap gap-2"
            >
                <Link
                    v-for="subcategory in landing.subcategories"
                    :key="subcategory.id"
                    :href="filteredUrl(subcategory.url)"
                    :aria-current="subcategory.active ? 'page' : undefined"
                    class="rounded-full border px-4 py-2 text-sm font-medium"
                    :class="
                        subcategory.active
                            ? 'border-[#087f75] bg-[#087f75] text-white'
                            : 'border-border bg-card text-muted-foreground hover:border-[#087f75]'
                    "
                    >{{ subcategory.name }}</Link
                >
            </nav>
        </div>
    </header>
    <section class="site-container py-10 sm:py-14" aria-label="Product catalog">
        <form
            class="grid gap-4 rounded-2xl border border-border bg-card p-5 sm:grid-cols-[minmax(0,1fr)_260px] sm:items-end"
            role="search"
            @submit.prevent="applyFilters"
        >
            <div>
                <label
                    for="catalog-search"
                    class="mb-2 block text-sm font-medium"
                    >Search the catalog</label
                >
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-3.5 left-3.5 size-4 text-muted-foreground"
                    /><input
                        id="catalog-search"
                        v-model="search"
                        type="search"
                        placeholder="Product name or keyword"
                        class="h-11 w-full rounded-lg border border-border bg-background pr-3 pl-10 text-sm"
                    />
                </div>
            </div>
            <div>
                <label for="catalog-type" class="mb-2 block text-sm font-medium"
                    >Product type</label
                >
                <select
                    id="catalog-type"
                    v-model="type"
                    class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm"
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
            </div>
            <fieldset
                class="grid gap-4 border-t border-border pt-5 sm:col-span-2 sm:grid-cols-3"
            >
                <legend class="sr-only">
                    Filter by verified software compatibility
                </legend>
                <div v-for="platform in versionPlatforms" :key="platform.key">
                    <label
                        :for="`catalog-${platform.key}`"
                        class="mb-2 block text-sm font-medium"
                        >{{ platform.label }}</label
                    >
                    <input
                        :id="`catalog-${platform.key}`"
                        v-model="versions[platform.key]"
                        type="text"
                        placeholder="Any stable version"
                        maxlength="11"
                        class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm"
                        :aria-invalid="!!page.props.errors[platform.key]"
                        :aria-describedby="`compatibility-help catalog-${platform.key}-error`"
                    />
                    <p
                        v-if="page.props.errors[platform.key]"
                        :id="`catalog-${platform.key}-error`"
                        class="mt-2 text-sm text-destructive"
                        role="alert"
                    >
                        {{ page.props.errors[platform.key] }}
                    </p>
                </div>
                <p
                    id="compatibility-help"
                    class="text-sm leading-6 text-muted-foreground sm:col-span-3"
                >
                    Enter the installed version, such as 8.2.1. A version such
                    as 8.2 means 8.2.0. Results must have verified compatibility
                    for every version you enter; products without verified data
                    are excluded.
                </p>
            </fieldset>
            <div class="flex flex-wrap gap-3 sm:col-span-2">
                <button
                    type="submit"
                    class="button-primary sm:col-span-2 lg:col-span-1"
                    :disabled="filtering"
                >
                    <SlidersHorizontal class="size-4" />{{
                        filtering ? 'Searching…' : 'Find products'
                    }}
                </button>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="button-secondary"
                    :disabled="filtering"
                    @click="clearFilters"
                >
                    Clear filters
                </button>
            </div>
        </form>
        <div class="mt-7 flex flex-wrap items-center justify-between gap-4">
            <nav
                v-if="categories.length"
                aria-label="Product categories"
                class="flex flex-wrap gap-2"
            >
                <Link
                    :href="filteredUrl('/products')"
                    class="rounded-full border px-4 py-2 text-sm font-medium"
                    :class="
                        !landing
                            ? 'border-[#172c2c] bg-[#172c2c] text-white'
                            : 'border-border bg-card text-muted-foreground'
                    "
                    :aria-current="!landing ? 'page' : undefined"
                    >All products</Link
                >
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="filteredUrl(category.url)"
                    class="rounded-full border px-4 py-2 text-sm font-medium hover:border-[#087f75]"
                    :class="
                        landing?.parent?.name === category.name ||
                        (landing?.kind === 'category' &&
                            landing?.name === category.name)
                            ? 'border-[#172c2c] bg-[#172c2c] text-white'
                            : 'border-border bg-card text-muted-foreground'
                    "
                    >{{ category.name }}</Link
                >
            </nav>
            <p class="text-sm text-muted-foreground" aria-live="polite">
                {{ products.total }}
                {{ products.total === 1 ? 'result' : 'results' }}
            </p>
        </div>
        <div
            v-if="products.data.length"
            class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3"
            :aria-busy="filtering"
        >
            <ProductCard
                v-for="product in products.data"
                :key="product.slug"
                :product="product"
            />
        </div>
        <div
            v-else
            class="mt-8 rounded-2xl border border-dashed border-border bg-[var(--client-canvas)] px-6 py-16 text-center"
        >
            <Search class="mx-auto size-8 text-primary" />
            <h2 class="mt-5 text-2xl font-semibold">No products found</h2>
            <p class="mt-3 text-muted-foreground">
                Try another search or clear the version filters. An empty result
                means no published compatibility range matches; it does not
                confirm that a product is incompatible.
            </p>
            <button
                v-if="hasFilters"
                type="button"
                class="button-secondary mt-6"
                @click="clearFilters"
            >
                Clear filters
            </button>
            <Link v-else href="/contact" class="button-secondary mt-6"
                >Discuss what you need</Link
            >
        </div>
        <nav
            v-if="products.last_page > 1"
            aria-label="Catalog pagination"
            class="mt-10 flex flex-wrap items-center justify-between gap-4 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ products.from }}–{{ products.to }} of
                {{ products.total }}
            </p>
            <div class="flex flex-wrap gap-2">
                <template v-for="link in products.links" :key="link.label"
                    ><Link
                        v-if="link.url"
                        :href="link.url"
                        :aria-current="link.active ? 'page' : undefined"
                        class="rounded-lg border px-4 py-2"
                        :class="
                            link.active
                                ? 'border-[#087f75] bg-[#087f75] text-white'
                                : 'border-border'
                        "
                        >{{ paginationLabel(link.label) }}</Link
                    ><span
                        v-else
                        class="rounded-lg border border-border px-4 py-2 text-muted-foreground"
                        >{{ paginationLabel(link.label) }}</span
                    ></template
                >
            </div>
        </nav>
        <div
            class="mt-16 flex flex-col gap-5 border-t border-border pt-8 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-xl font-semibold">Need something specific?</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Tell us about your WHMCS, WordPress, or custom development
                    requirements.
                </p>
            </div>
            <Link href="/contact" class="button-secondary shrink-0"
                >Discuss Your Project <ArrowRight class="size-4"
            /></Link>
        </div>
    </section>
</template>
