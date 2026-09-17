<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, LockKeyhole, Package } from '@lucide/vue';
import OrderTotals from '@/modules/client/components/OrderTotals.vue';
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
    checkoutUrl?: string;
    backUrl?: string;
    backLabel?: string;
}>();

const form = useForm({
    gateway: props.paymentGateways[0]?.key ?? null,
    checkout: '',
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

const submit = () => form.post(props.checkoutUrl ?? '/checkout');
</script>

<template>
    <SeoHead
        title="Checkout"
        description="Review your ASR Tech order and choose an available payment method."
        type="website"
        :seo="{ robots: 'noindex,follow' }"
    />
    <div class="min-h-[70vh] bg-[var(--client-canvas)] pb-16">
        <header class="border-b border-border bg-card">
            <div class="site-container py-12 sm:py-16">
                <p class="section-kicker">Checkout</p>
                <div
                    class="mt-4 flex flex-wrap items-end justify-between gap-6"
                >
                    <div>
                        <h1 class="display-title">One last check.</h1>
                        <p class="body-copy mt-4">
                            Confirm your order and choose how to pay.
                        </p>
                    </div>
                    <ol
                        aria-label="Checkout progress"
                        class="flex items-center gap-4 text-sm"
                    >
                        <li>
                            <Link
                                :href="backUrl ?? '/cart'"
                                class="flex items-center gap-2 text-muted-foreground"
                                ><span
                                    class="grid size-7 place-items-center rounded-full border border-border"
                                    >1</span
                                >
                                {{ backLabel ?? 'Cart' }}</Link
                            >
                        </li>
                        <li
                            aria-hidden="true"
                            class="h-px w-8 bg-[#cbdcd5]"
                        ></li>
                        <li
                            class="flex items-center gap-2 font-semibold text-primary"
                            aria-current="step"
                        >
                            <span
                                class="grid size-7 place-items-center rounded-full bg-accent"
                                >2</span
                            >
                            Checkout
                        </li>
                    </ol>
                </div>
            </div>
        </header>
        <form
            class="site-container grid gap-8 py-8 sm:py-12 lg:grid-cols-[minmax(0,1fr)_360px]"
            :aria-busy="form.processing"
            @submit.prevent="submit"
        >
            <div class="space-y-7">
                <section class="surface-card p-6 sm:p-7">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-xl font-semibold">Your order</h2>
                        <Link
                            :href="backUrl ?? '/cart'"
                            class="text-sm font-medium text-primary underline underline-offset-4"
                            >{{
                                backLabel ? 'Review request' : 'Edit cart'
                            }}</Link
                        >
                    </div>
                    <div class="mt-3 divide-y divide-border">
                        <article
                            v-for="item in cart.items"
                            :key="item.id"
                            class="flex flex-wrap items-center gap-4 py-5 last:pb-0"
                        >
                            <span
                                class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted p-2"
                                ><img
                                    v-if="item.product.featured_image"
                                    :src="item.product.featured_image"
                                    :alt="item.product.name"
                                    class="max-h-full max-w-full object-contain" /><Package
                                    v-else
                                    class="size-6 text-primary"
                            /></span>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold">
                                    <Link
                                        :href="item.product.url"
                                        class="hover:text-primary"
                                        >{{ item.product.name }}</Link
                                    >
                                </h3>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ item.name || label(item.billing_cycle) }}
                                </p>
                            </div>
                            <p class="font-semibold">
                                {{ money(item.amount) }}
                            </p>
                        </article>
                    </div>
                </section>
                <fieldset class="surface-card p-6 sm:p-7">
                    <legend class="sr-only">Payment method</legend>
                    <div class="flex items-center gap-3">
                        <CreditCard class="size-5 text-primary" />
                        <h2 class="text-xl font-semibold">Payment method</h2>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Select one of the available options below.
                    </p>
                    <div v-if="paymentGateways.length" class="mt-6 grid gap-3">
                        <label
                            v-for="gateway in paymentGateways"
                            :key="gateway.key"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-colors focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[#087f75]"
                            :class="
                                form.gateway === gateway.key
                                    ? 'border-[#087f75] bg-accent'
                                    : 'border-border hover:border-[#89b4aa]'
                            "
                            ><input
                                v-model="form.gateway"
                                type="radio"
                                name="gateway"
                                :value="gateway.key"
                                class="mt-1 size-4 shrink-0 accent-[#087f75]"
                                :aria-invalid="Boolean(form.errors.gateway)"
                                :aria-describedby="
                                    form.errors.gateway
                                        ? 'gateway-error'
                                        : undefined
                                "
                            /><span
                                ><span class="block text-sm font-semibold">{{
                                    gateway.name
                                }}</span
                                ><span
                                    v-if="gateway.description"
                                    class="mt-1 block text-sm leading-6 text-muted-foreground"
                                    >{{ gateway.description }}</span
                                ></span
                            ></label
                        >
                    </div>
                    <div
                        v-else
                        role="status"
                        class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900"
                    >
                        Online payment is not available right now. Your cart is
                        saved.
                        <Link
                            href="/contact"
                            class="font-semibold underline underline-offset-4"
                            >Contact ASR Tech</Link
                        >
                        for help with your order.
                    </div>
                    <p
                        v-if="form.errors.checkout"
                        role="alert"
                        class="mt-4 text-sm text-red-700"
                    >
                        {{ form.errors.checkout }}
                    </p>
                    <p
                        v-if="form.errors.gateway"
                        id="gateway-error"
                        role="alert"
                        class="mt-4 text-sm text-red-700"
                    >
                        {{ form.errors.gateway }}
                    </p>
                </fieldset>
                <Link
                    :href="backUrl ?? '/cart'"
                    class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-muted-foreground hover:text-primary"
                    ><ArrowLeft class="size-4" /> Return to
                    {{ backLabel ? 'request' : 'cart' }}</Link
                >
            </div>
            <aside
                class="surface-card self-start p-6 sm:p-7 lg:sticky lg:top-28"
            >
                <h2 class="text-xl font-semibold">Payment summary</h2>
                <OrderTotals :summary="cart" class="mt-7" /><button
                    type="submit"
                    :disabled="form.processing || paymentGateways.length === 0"
                    class="button-primary mt-7 w-full"
                >
                    <LockKeyhole class="size-4" />{{
                        form.processing
                            ? 'Processing…'
                            : `Pay ${money(cart.total)}`
                    }}
                </button>
                <p class="mt-4 text-sm leading-6 text-muted-foreground">
                    Continue with your selected payment method. You can find
                    your order and invoice in your client area.
                </p>
                <p
                    class="mt-4 border-t border-border pt-4 text-xs leading-5 text-muted-foreground"
                >
                    Need help before paying?
                    <Link
                        href="/contact"
                        class="font-semibold text-primary underline underline-offset-4"
                        >Contact ASR Tech.</Link
                    >
                </p>
            </aside>
        </form>
    </div>
</template>
