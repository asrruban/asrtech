<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Code2, Package } from '@lucide/vue';
const props = defineProps(['product']);
const price = props.product.prices.find((item) => item.enabled);
const money = (currency, amount) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));
const label = (value) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
</script>

<template>
    <article
        class="group overflow-hidden rounded-2xl border bg-card shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
    >
        <div
            class="flex aspect-[16/9] items-center justify-center overflow-hidden bg-gradient-to-br from-slate-900 to-slate-700"
        >
            <img
                v-if="product.featured_image"
                :src="product.featured_image"
                :alt="product.name"
                class="size-full object-cover transition duration-500 group-hover:scale-105"
            />
            <Code2
                v-else-if="product.type === 'web_development'"
                class="size-14 text-white/70"
            />
            <Package v-else class="size-14 text-white/70" />
        </div>
        <div class="space-y-4 p-6">
            <div
                class="flex items-center justify-between gap-3 text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                <span>{{ product.category.name }}</span>
                <span>{{ product.type_name || label(product.type) }}</span>
            </div>
            <div>
                <h2 class="text-xl font-semibold tracking-tight">
                    {{ product.name }}
                </h2>
                <p
                    class="mt-2 line-clamp-2 text-sm leading-6 text-muted-foreground"
                >
                    {{
                        product.short_description ||
                        'Professional ASRTech digital product and support.'
                    }}
                </p>
            </div>
            <div class="flex items-end justify-between gap-4 border-t pt-4">
                <div>
                    <p class="text-xs text-muted-foreground">
                        {{ price ? 'Starting at' : 'Custom quote' }}
                    </p>
                    <p v-if="price" class="font-semibold">
                        {{
                            money(
                                price.currency,
                                price.sale_price || price.price,
                            )
                        }}
                        <span class="text-xs font-normal text-muted-foreground"
                            >/ {{ label(price.billing_cycle) }}</span
                        >
                    </p>
                </div>
                <Link
                    :href="product.url"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-primary"
                >
                    Details
                    <ArrowRight
                        class="size-4 transition group-hover:translate-x-1"
                    />
                </Link>
            </div>
        </div>
    </article>
</template>
