<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    CreditCard,
    LockKeyhole,
    Package,
} from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface CheckoutItem {
    id: number;
    billing_cycle: string;
    name?: string | null;
    currency: string;
    amount: string | number;
    setup_fee?: string | number | null;
    product: {
        name: string;
        slug: string;
        url: string;
        featured_image?: string | null;
    };
}

interface CartSummary {
    items: CheckoutItem[];
    currency?: string | null;
    subtotal: string;
    setup_fee: string;
    discount_amount: string;
    tax_amount: string;
    total: string;
    promotion?: { code: string; name: string } | null;
    tax?: { name: string; rate: string | number } | null;
    tax_pending: boolean;
}

interface Gateway {
    key: string;
    name: string;
    description?: string | null;
}

const props = defineProps<{
    cart: CartSummary;
    paymentGateways: Gateway[];
}>();

const form = useForm({
    gateway: props.paymentGateways[0]?.key ?? null,
});

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

const submit = () => form.post('/checkout');
</script>

<template>
    <SeoHead
        title="Checkout"
        description="Review and pay for your ASRTech order securely."
        type="website"
    />

    <div class="min-h-[70vh] bg-[#e9edf3] pb-20 dark:bg-slate-950">
        <header class="bg-[linear-gradient(128deg,#0874df,#064296)] text-white">
            <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                <p
                    class="text-xs font-extrabold tracking-[0.18em] text-[#b7ec37] uppercase"
                >
                    Secure checkout
                </p>
                <h1 class="mt-2 text-3xl font-extrabold">Review and pay</h1>
                <p class="mt-2 text-sm text-blue-100/75">
                    Confirm your products and choose an available payment
                    method.
                </p>
            </div>
        </header>

        <main
            class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:px-8"
        >
            <div class="space-y-6">
                <section
                    class="rounded-sm bg-white p-6 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-white/10"
                >
                    <h2
                        class="text-lg font-extrabold text-slate-900 dark:text-white"
                    >
                        Order items
                    </h2>
                    <div class="mt-5 divide-y dark:divide-white/10">
                        <div
                            v-for="item in cart.items"
                            :key="item.id"
                            class="flex items-center gap-4 py-4 first:pt-0 last:pb-0"
                        >
                            <span
                                class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-slate-100 p-1.5 dark:bg-slate-800"
                            >
                                <img
                                    v-if="item.product.featured_image"
                                    :src="item.product.featured_image"
                                    :alt="item.product.name"
                                    class="max-h-full max-w-full object-contain"
                                />
                                <Package v-else class="size-6 text-slate-400" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate font-extrabold text-slate-900 dark:text-white"
                                >
                                    {{ item.product.name }}
                                </p>
                                <p
                                    class="mt-1 text-xs font-semibold text-slate-400"
                                >
                                    {{ item.name || label(item.billing_cycle) }}
                                </p>
                            </div>
                            <p class="font-extrabold text-[#f5842a]">
                                {{ money(item.amount) }}
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-sm bg-white p-6 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-white/10"
                >
                    <div class="flex items-center gap-3">
                        <CreditCard class="size-5 text-blue-600" />
                        <h2
                            class="text-lg font-extrabold text-slate-900 dark:text-white"
                        >
                            Payment method
                        </h2>
                    </div>
                    <div class="mt-5 grid gap-3">
                        <label
                            v-for="gateway in paymentGateways"
                            :key="gateway.key"
                            class="flex cursor-pointer items-start gap-3 rounded-sm border p-4 transition"
                            :class="
                                form.gateway === gateway.key
                                    ? 'border-blue-500 bg-blue-50/70 dark:bg-blue-500/10'
                                    : 'border-slate-200 hover:border-slate-300 dark:border-white/10'
                            "
                        >
                            <input
                                v-model="form.gateway"
                                type="radio"
                                name="gateway"
                                :value="gateway.key"
                                class="mt-1 size-4 accent-blue-600"
                            />
                            <span>
                                <span
                                    class="block font-bold text-slate-800 dark:text-white"
                                    >{{ gateway.name }}</span
                                >
                                <span
                                    v-if="gateway.description"
                                    class="mt-1 block text-xs leading-5 text-slate-500"
                                    >{{ gateway.description }}</span
                                >
                            </span>
                        </label>
                    </div>
                    <p
                        v-if="form.errors.gateway"
                        class="mt-3 text-sm text-red-600"
                    >
                        {{ form.errors.gateway }}
                    </p>
                </section>

                <Link
                    href="/cart"
                    class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-blue-600"
                >
                    <ArrowLeft class="size-4" /> Return to cart
                </Link>
            </div>

            <aside
                class="self-start rounded-sm bg-white p-6 shadow-[0_18px_55px_rgba(40,55,82,0.1)] ring-1 ring-slate-200/70 lg:sticky lg:top-24 dark:bg-slate-900 dark:ring-white/10"
            >
                <h2
                    class="text-lg font-extrabold text-slate-900 dark:text-white"
                >
                    Payment summary
                </h2>
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
                        <dt>Promotion ({{ cart.promotion?.code }})</dt>
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
                        class="flex justify-between border-t pt-4 text-lg font-extrabold text-slate-900 dark:border-white/10 dark:text-white"
                    >
                        <dt>Total</dt>
                        <dd>{{ money(cart.total) }}</dd>
                    </div>
                </dl>

                <button
                    type="button"
                    :disabled="form.processing || paymentGateways.length === 0"
                    class="mt-6 flex h-12 w-full items-center justify-center gap-2 rounded-sm bg-[#58c957] px-5 text-sm font-bold text-white shadow-lg shadow-[#58c957]/20 transition hover:bg-[#45b944] disabled:cursor-not-allowed disabled:opacity-50"
                    @click="submit"
                >
                    <LockKeyhole class="size-4" />
                    {{
                        form.processing
                            ? 'Processing…'
                            : `Pay ${money(cart.total)}`
                    }}
                </button>

                <ul class="mt-5 space-y-2.5 text-xs leading-5 text-slate-500">
                    <li class="flex items-start gap-2">
                        <CheckCircle2
                            class="mt-0.5 size-4 shrink-0 text-[#58c957]"
                        />
                        Encrypted payment processing
                    </li>
                    <li class="flex items-start gap-2">
                        <CheckCircle2
                            class="mt-0.5 size-4 shrink-0 text-[#58c957]"
                        />
                        Automatic license provisioning
                    </li>
                    <li class="flex items-start gap-2">
                        <CheckCircle2
                            class="mt-0.5 size-4 shrink-0 text-[#58c957]"
                        />
                        Invoice available in your client area
                    </li>
                </ul>
            </aside>
        </main>
    </div>
</template>
