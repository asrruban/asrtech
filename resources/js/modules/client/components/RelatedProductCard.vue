<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Code2, Package, ShoppingCart } from '@lucide/vue';
import { computed } from 'vue';

interface RelatedPrice {
    billing_cycle: string;
    currency: string;
    price: string | number;
    sale_price?: string | number | null;
    featured?: boolean;
    enabled: boolean;
}

interface RelatedProduct {
    name: string;
    slug: string;
    url: string;
    type: string;
    badge?: string | null;
    short_description?: string | null;
    featured_image?: string | null;
    category: { name: string };
    prices: RelatedPrice[];
}

const props = defineProps<{ product: RelatedProduct }>();

const price = computed(() => {
    const enabled = props.product.prices.filter((item) => item.enabled);

    return enabled.find((item) => item.featured) ?? enabled[0] ?? null;
});

const money = (currency: string, amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const cycleSuffix = (cycle: string) => {
    if (cycle === 'monthly') {
        return '/ mo';
    }

    if (cycle === 'yearly') {
        return '/ yr';
    }

    return 'one-time';
};
</script>

<template>
    <article
        class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-xl dark:bg-slate-900 dark:ring-white/10"
    >
        <Link
            :href="product.url"
            class="relative flex aspect-[16/10] items-center justify-center overflow-hidden bg-[linear-gradient(160deg,#13b8ff_0%,#2b72fb_100%)]"
        >
            <img
                v-if="product.featured_image"
                :src="product.featured_image"
                :alt="product.name"
                class="size-full object-cover transition duration-500 group-hover:scale-105"
            />
            <Code2
                v-else-if="product.type === 'web_development'"
                class="size-12 text-white/80"
            />
            <Package v-else class="size-12 text-white/80" />
            <span
                v-if="product.badge"
                class="absolute top-2.5 left-2.5 rounded-full bg-[#f5842a] px-2.5 py-1 text-[10px] font-extrabold tracking-wide text-white uppercase"
            >
                {{ product.badge }}
            </span>
        </Link>

        <div class="flex flex-1 flex-col p-5">
            <div class="flex items-end gap-2">
                <template v-if="price">
                    <span
                        v-if="price.sale_price"
                        class="pb-0.5 text-xs text-slate-400 line-through"
                    >
                        {{ money(price.currency, price.price) }}
                    </span>
                    <span class="text-lg font-extrabold text-[#f5842a]">
                        {{
                            money(
                                price.currency,
                                price.sale_price || price.price,
                            )
                        }}
                    </span>
                    <span
                        class="pb-0.5 text-[11px] font-semibold text-slate-400"
                    >
                        {{ cycleSuffix(price.billing_cycle) }}
                    </span>
                </template>
                <span v-else class="text-sm font-bold text-slate-500">
                    Custom quote
                </span>
            </div>

            <h3
                class="mt-2 text-[15px] leading-snug font-bold text-slate-800 dark:text-slate-100"
            >
                <Link :href="product.url" class="hover:text-[#2b72fb]">
                    {{ product.name }}
                </Link>
            </h3>
            <p
                class="mt-1.5 line-clamp-2 flex-1 text-xs leading-5 text-slate-500 dark:text-slate-400"
            >
                {{ product.short_description || product.category.name }}
            </p>

            <div class="mt-4 flex gap-2">
                <Link
                    :href="product.url"
                    class="flex h-9 flex-1 items-center justify-center rounded-md bg-[#4fb250] px-3 text-xs font-bold text-white transition hover:bg-[#439c45] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#4fb250]"
                >
                    Order Now
                </Link>
                <Link
                    :href="product.url"
                    class="flex size-9 items-center justify-center rounded-md border border-slate-200 text-slate-500 transition hover:border-[#4fb250] hover:text-[#4fb250] dark:border-white/10 dark:text-slate-300"
                    :aria-label="`View ${product.name}`"
                >
                    <ShoppingCart class="size-4" />
                </Link>
            </div>
        </div>
    </article>
</template>
