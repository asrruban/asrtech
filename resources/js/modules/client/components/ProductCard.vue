<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import { computed } from 'vue';
import ProductVisual from '@/modules/client/components/ProductVisual.vue';

interface CatalogPrice {
    currency: string;
    price: string | number;
    sale_price?: string | number | null;
    billing_cycle: string;
    enabled: boolean;
}
interface CatalogProduct {
    name: string;
    url: string;
    type: string;
    type_name?: string;
    short_description?: string | null;
    featured_image?: string | null;
    category: { name: string };
    prices: CatalogPrice[];
}
const props = withDefaults(
    defineProps<{ product: CatalogProduct; headingLevel?: 2 | 3 }>(),
    { headingLevel: 2 },
);
const price = computed(() => props.product.prices.find((item) => item.enabled));
const money = (currency: string, amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));
const label = (value: string) => value.replaceAll('_', ' ');
</script>

<template>
    <article
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-border bg-card transition-colors hover:border-[#89b4aa]"
    >
        <Link
            :href="product.url"
            :aria-label="`View ${product.name}`"
            class="block border-b border-border"
        >
            <ProductVisual
                :name="product.name"
                :type="product.type"
                :image="product.featured_image"
            />
        </Link>
        <div class="flex flex-1 flex-col p-6">
            <p
                class="text-xs font-semibold tracking-wide text-primary uppercase"
            >
                {{ product.category.name }}
            </p>
            <component
                :is="`h${headingLevel}`"
                class="mt-3 text-xl leading-snug font-semibold tracking-tight text-foreground"
            >
                <Link :href="product.url" class="hover:text-primary">{{
                    product.name
                }}</Link>
            </component>
            <p
                v-if="product.short_description"
                class="mt-3 line-clamp-3 text-sm leading-6 text-muted-foreground"
            >
                {{ product.short_description }}
            </p>
            <div class="mt-auto flex items-end justify-between gap-3 pt-7">
                <div>
                    <p class="text-xs text-muted-foreground">
                        {{
                            price ? 'Available from' : 'Ask about availability'
                        }}
                    </p>
                    <p
                        v-if="price"
                        class="mt-1 text-lg font-semibold text-foreground"
                    >
                        {{
                            money(
                                price.currency,
                                price.sale_price ?? price.price,
                            )
                        }}
                        <span class="text-xs font-normal text-muted-foreground"
                            >/ {{ label(price.billing_cycle) }}</span
                        >
                    </p>
                </div>
                <Link
                    :href="product.url"
                    class="flex size-11 shrink-0 items-center justify-center rounded-full border border-border text-primary transition-colors hover:bg-accent"
                    :aria-label="`Explore ${product.name}`"
                >
                    <ArrowUpRight class="size-5" />
                </Link>
            </div>
        </div>
    </article>
</template>
