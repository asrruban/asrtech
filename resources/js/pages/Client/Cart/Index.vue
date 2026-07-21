<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Package,
    ShieldCheck,
    ShoppingCart,
    Tag,
    Trash2,
    X,
} from '@lucide/vue';
import { computed } from 'vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface CartItem {
    id: number;
    billing_cycle: string;
    name?: string | null;
    description?: string | null;
    currency: string;
    price: string | number;
    sale_price?: string | number | null;
    setup_fee?: string | number | null;
    amount: string | number;
    product: {
        name: string;
        slug: string;
        url: string;
        featured_image?: string | null;
    };
}

interface CartSummary {
    items: CartItem[];
    currency?: string | null;
    subtotal: string;
    setup_fee: string;
    discount_amount: string;
    tax_amount: string;
    total: string;
    promotion?: { code: string; name: string } | null;
    promotion_error?: string | null;
    tax?: { name: string; rate: string | number } | null;
    tax_pending: boolean;
}

const props = defineProps<{ cart: CartSummary }>();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const promotionForm = useForm({ code: '' });

const money = (amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.cart.currency || 'USD',
        maximumFractionDigits: 2,
    }).format(Number(amount));

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const removeItem = (item: CartItem) => {
    router.delete(`/cart/items/${item.id}`, { preserveScroll: true });
};

const applyPromotion = () =>
    promotionForm.post('/cart/promotion', {
        preserveScroll: true,
        onSuccess: () => promotionForm.reset(),
    });

const removePromotion = () =>
    router.delete('/cart/promotion', { preserveScroll: true });
</script>

<template>
    <SeoHead
        title="Shopping Cart"
        description="Review your selected ASRTech products before checkout."
        type="website"
    />

    <div class="min-h-[70vh] bg-[#e9edf3] pb-20 dark:bg-slate-950">
        <header class="bg-[linear-gradient(128deg,#0874df,#064296)] text-white">
            <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <span
                        class="flex size-12 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15"
                    >
                        <ShoppingCart class="size-6" />
                    </span>
                    <div>
                        <p
                            class="text-xs font-extrabold tracking-[0.18em] text-[#b7ec37] uppercase"
                        >
                            Your selections
                        </p>
                        <h1 class="mt-1 text-3xl font-extrabold">
                            Shopping Cart
                        </h1>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <div
                v-if="cart.items.length"
                class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]"
            >
                <section class="space-y-4" aria-label="Cart items">
                    <article
                        v-for="item in cart.items"
                        :key="item.id"
                        class="grid gap-4 rounded-sm bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:grid-cols-[96px_minmax(0,1fr)_auto] sm:items-center dark:bg-slate-900 dark:ring-white/10"
                    >
                        <Link
                            :href="item.product.url"
                            class="flex h-20 w-24 items-center justify-center overflow-hidden rounded-sm bg-slate-100 p-2 dark:bg-slate-800"
                        >
                            <img
                                v-if="item.product.featured_image"
                                :src="item.product.featured_image"
                                :alt="item.product.name"
                                class="max-h-full max-w-full object-contain"
                            />
                            <Package v-else class="size-8 text-slate-400" />
                        </Link>

                        <div class="min-w-0">
                            <Link
                                :href="item.product.url"
                                class="font-extrabold text-slate-900 hover:text-blue-600 dark:text-white"
                            >
                                {{ item.product.name }}
                            </Link>
                            <p
                                class="mt-1 text-sm font-semibold text-slate-500"
                            >
                                {{ item.name || label(item.billing_cycle) }}
                            </p>
                            <p
                                v-if="item.description"
                                class="mt-2 line-clamp-2 text-xs leading-5 text-slate-400"
                            >
                                {{ item.description }}
                            </p>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 sm:block sm:text-right"
                        >
                            <p class="text-lg font-extrabold text-[#f5842a]">
                                {{ money(item.amount) }}
                            </p>
                            <button
                                type="button"
                                class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 transition hover:text-red-600"
                                :aria-label="`Remove ${item.product.name} from cart`"
                                @click="removeItem(item)"
                            >
                                <Trash2 class="size-3.5" /> Remove
                            </button>
                        </div>
                    </article>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <Link
                            href="/products"
                            class="inline-flex h-10 items-center justify-center rounded-sm border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 hover:border-blue-500 hover:text-blue-600 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200"
                        >
                            Continue shopping
                        </Link>
                        <Link
                            href="/cart"
                            method="delete"
                            as="button"
                            class="inline-flex h-10 items-center justify-center px-3 text-sm font-bold text-slate-400 hover:text-red-600"
                        >
                            Clear cart
                        </Link>
                    </div>
                </section>

                <aside
                    class="self-start rounded-sm bg-white p-6 shadow-[0_18px_55px_rgba(40,55,82,0.1)] ring-1 ring-slate-200/70 lg:sticky lg:top-24 dark:bg-slate-900 dark:ring-white/10"
                >
                    <h2
                        class="text-lg font-extrabold text-slate-900 dark:text-white"
                    >
                        Order summary
                    </h2>
                    <form
                        v-if="!cart.promotion"
                        class="mt-5 flex gap-2"
                        @submit.prevent="applyPromotion"
                    >
                        <div class="min-w-0 flex-1">
                            <label for="promotion-code" class="sr-only"
                                >Promotion code</label
                            >
                            <input
                                id="promotion-code"
                                v-model="promotionForm.code"
                                type="text"
                                required
                                placeholder="Promotion code"
                                class="h-10 w-full rounded-sm border border-slate-300 bg-white px-3 text-sm font-semibold uppercase outline-none focus:border-blue-500 dark:border-white/10 dark:bg-slate-950"
                            />
                        </div>
                        <button
                            :disabled="promotionForm.processing"
                            class="h-10 rounded-sm border border-blue-600 px-3 text-sm font-bold text-blue-600 hover:bg-blue-50 disabled:opacity-50 dark:hover:bg-blue-500/10"
                        >
                            Apply
                        </button>
                    </form>
                    <p
                        v-if="promotionForm.errors.code || cart.promotion_error"
                        class="mt-2 text-xs font-semibold text-red-600"
                    >
                        {{ promotionForm.errors.code || cart.promotion_error }}
                    </p>
                    <div
                        v-if="cart.promotion"
                        class="mt-5 flex items-center justify-between rounded-sm bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        <span class="flex items-center gap-2 font-bold"
                            ><Tag class="size-4" />
                            {{ cart.promotion.code }}</span
                        >
                        <button
                            type="button"
                            aria-label="Remove promotion"
                            @click="removePromotion"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between text-slate-500">
                            <dt>Subtotal</dt>
                            <dd
                                class="font-bold text-slate-700 dark:text-slate-200"
                            >
                                {{ money(cart.subtotal) }}
                            </dd>
                        </div>
                        <div
                            v-if="Number(cart.discount_amount) > 0"
                            class="flex justify-between text-emerald-600"
                        >
                            <dt>Promotion</dt>
                            <dd class="font-bold">
                                -{{ money(cart.discount_amount) }}
                            </dd>
                        </div>
                        <div
                            v-if="Number(cart.setup_fee) > 0"
                            class="flex justify-between text-slate-500"
                        >
                            <dt>Setup fees</dt>
                            <dd
                                class="font-bold text-slate-700 dark:text-slate-200"
                            >
                                {{ money(cart.setup_fee) }}
                            </dd>
                        </div>
                        <div
                            v-if="Number(cart.tax_amount) > 0"
                            class="flex justify-between text-slate-500"
                        >
                            <dt>{{ cart.tax?.name || 'Tax' }}</dt>
                            <dd
                                class="font-bold text-slate-700 dark:text-slate-200"
                            >
                                {{ money(cart.tax_amount) }}
                            </dd>
                        </div>
                        <div
                            v-else-if="cart.tax_pending"
                            class="flex justify-between text-xs text-slate-400"
                        >
                            <dt>Tax</dt>
                            <dd>Calculated after sign in</dd>
                        </div>
                        <div
                            class="flex justify-between border-t pt-4 text-base font-extrabold text-slate-900 dark:border-white/10 dark:text-white"
                        >
                            <dt>Total</dt>
                            <dd>{{ money(cart.total) }}</dd>
                        </div>
                    </dl>

                    <Link
                        href="/checkout"
                        class="mt-6 flex h-12 w-full items-center justify-center gap-2 rounded-sm bg-[#58c957] px-5 text-sm font-bold text-white shadow-lg shadow-[#58c957]/20 transition hover:bg-[#45b944]"
                    >
                        {{
                            user ? 'Proceed to Checkout' : 'Sign in to Checkout'
                        }}
                        <ArrowRight class="size-4" />
                    </Link>

                    <p
                        class="mt-4 flex items-start gap-2 text-xs leading-5 text-slate-400"
                    >
                        <ShieldCheck
                            class="mt-0.5 size-4 shrink-0 text-[#58c957]"
                        />
                        Secure checkout through your selected enabled payment
                        gateway.
                    </p>
                </aside>
            </div>

            <section
                v-else
                class="rounded-sm bg-white px-6 py-16 text-center shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-white/10"
            >
                <ShoppingCart class="mx-auto size-12 text-slate-300" />
                <h2
                    class="mt-5 text-2xl font-extrabold text-slate-900 dark:text-white"
                >
                    Your cart is empty
                </h2>
                <p
                    class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500"
                >
                    Browse the catalog and choose the license plan that fits
                    your project.
                </p>
                <Link
                    href="/products"
                    class="mt-6 inline-flex h-11 items-center justify-center rounded-sm bg-blue-600 px-5 text-sm font-bold text-white hover:bg-blue-700"
                >
                    Browse products
                </Link>
            </section>
        </main>
    </div>
</template>
