<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { PackageCheck, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
const props = defineProps(['filters', 'products']);
const search = ref(props.filters.search);
const runSearch = () =>
    router.get(
        '/admin/products',
        { search: search.value },
        { preserveState: true, replace: true },
    );
const removeProduct = (product) => {
    if (confirm(`Delete product “${product.name}”?`)) {
        router.delete(`/admin/products/${product.id}`);
    }
};
const titleCase = (value) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
const money = (currency, amount) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(Number(amount));
const paginationLabel = (value) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <Head title="Products" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"
        >
            <div>
                <p class="text-sm font-medium text-primary">Catalog</p>
                <h1 class="text-3xl font-semibold tracking-tight">Products</h1>
                <p class="mt-1 text-muted-foreground">
                    Manage all product types, including licenses.
                </p>
            </div>
            <Button as-child
                ><Link href="/admin/products/create"
                    ><Plus class="size-4" /> Add product</Link
                ></Button
            >
        </div>

        <form class="flex max-w-lg gap-2" @submit.prevent="runSearch">
            <Input v-model="search" placeholder="Search name or SKU" />
            <Button type="submit" variant="outline"
                ><Search class="size-4" /> Search</Button
            >
        </form>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="products.data.length === 0"
                    class="p-12 text-center text-sm text-muted-foreground"
                >
                    No products found. Create your first product to begin.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="px-5 py-3 font-medium">Product</th>
                                <th class="px-5 py-3 font-medium">
                                    Category / Subcategory
                                </th>
                                <th class="px-5 py-3 font-medium">Type</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Pricing
                                </th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted"
                                        >
                                            <img
                                                v-if="product.featured_image"
                                                :src="product.featured_image"
                                                :alt="product.name"
                                                class="size-full object-cover"
                                            />
                                            <span v-else class="text-xs"
                                                >—</span
                                            >
                                        </div>
                                        <div>
                                            <p class="font-medium">
                                                {{ product.name }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ product.sku || 'No SKU' }} ·
                                                {{
                                                    product.status
                                                        ? 'Active'
                                                        : 'Inactive'
                                                }}
                                            </p>
                                            <p
                                                class="mt-1 font-mono text-[11px] text-muted-foreground"
                                            >
                                                {{
                                                    `/products/${product.product_type?.slug || 'type'}/${product.slug}`
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ product.category.name
                                    }}<span v-if="product.group">
                                        / {{ product.group.name }}</span
                                    >
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            product.type === 'license'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{
                                            product.product_type?.name ||
                                            titleCase(product.type)
                                        }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="space-y-1">
                                        <p
                                            v-for="price in product.prices.filter(
                                                (item) => item.enabled,
                                            )"
                                            :key="price.billing_cycle"
                                            class="text-xs whitespace-nowrap"
                                        >
                                            <span class="font-medium">{{
                                                money(
                                                    price.currency,
                                                    price.sale_price ||
                                                        price.price,
                                                )
                                            }}</span>
                                            <span class="text-muted-foreground">
                                                /
                                                {{
                                                    titleCase(
                                                        price.billing_cycle,
                                                    )
                                                }}
                                            </span>
                                        </p>
                                        <p
                                            v-if="product.prices.length === 0"
                                            class="text-xs text-muted-foreground"
                                        >
                                            Not priced
                                        </p>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                :href="`/admin/products/${product.id}/releases`"
                                            >
                                                <PackageCheck class="size-4" />
                                                Releases
                                            </Link>
                                        </Button>
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="outline"
                                            ><Link
                                                :href="`/admin/products/${product.id}/edit`"
                                                ><Pencil class="size-4" />
                                                Edit</Link
                                            ></Button
                                        >
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="removeProduct(product)"
                                            ><Trash2 class="size-4"
                                        /></Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div
            v-if="products.last_page > 1"
            class="flex items-center justify-between text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ products.from }}–{{ products.to }} of
                {{ products.total }}
            </p>
            <div class="flex gap-2">
                <Button
                    v-for="link in products.links"
                    :key="link.label"
                    as-child
                    size="sm"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                >
                    <Link v-if="link.url" :href="link.url">
                        {{ paginationLabel(link.label) }}
                    </Link>
                    <span v-else>{{ paginationLabel(link.label) }}</span>
                </Button>
            </div>
        </div>
    </div>
</template>
