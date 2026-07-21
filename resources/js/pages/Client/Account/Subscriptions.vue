<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CalendarClock,
    CreditCard,
    Package,
    RefreshCw,
    XCircle,
} from '@lucide/vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface SubscriptionItem {
    id: number;
    status: string;
    billing_cycle: string;
    currency: string;
    amount: string;
    gateway: string;
    current_period_start: string | null;
    current_period_end: string | null;
    cancel_at_period_end: boolean;
    canceled_at: string | null;
    last_payment_at: string | null;
    last_payment_failure_at: string | null;
    failed_payments_count: number;
    payment_attention_required: boolean;
    can_update_payment_method: boolean;
    can_cancel: boolean;
    can_resume: boolean;
    details_url: string;
    product: { name: string; featured_image: string | null; url: string };
    license: { id: number; license_key: string; status: string; url: string };
}

const props = defineProps<{ subscriptions: SubscriptionItem[] }>();

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const money = (currency: string, amount: string) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const date = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(new Date(value))
        : '—';

const statusClass = (status: string) =>
    ({
        active: 'bg-emerald-100 text-emerald-700',
        trialing: 'bg-sky-100 text-sky-700',
        past_due: 'bg-amber-100 text-amber-700',
        paused: 'bg-slate-200 text-slate-700',
        canceled: 'bg-red-100 text-red-700',
        incomplete: 'bg-orange-100 text-orange-700',
    })[status] ?? 'bg-muted text-muted-foreground';

const cancel = (subscription: SubscriptionItem) => {
    if (!confirm('Stop automatic renewal at the end of this billing period?')) {
        return;
    }

    router.post(
        `/client-area/subscriptions/${subscription.id}/cancel`,
        {},
        { preserveScroll: true },
    );
};

const resume = (subscription: SubscriptionItem) =>
    router.post(
        `/client-area/subscriptions/${subscription.id}/resume`,
        {},
        { preserveScroll: true },
    );

const updatePaymentMethod = (subscription: SubscriptionItem) =>
    router.post(
        `/client-area/subscriptions/${subscription.id}/billing-portal`,
        {},
        { preserveScroll: true },
    );
</script>

<template>
    <SeoHead
        title="Subscriptions"
        description="Manage recurring product subscriptions and renewal dates."
    />

    <ClientAreaHero
        title="Subscriptions"
        subtitle="Review recurring charges, renewal dates, and cancellation settings."
    />

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2.5">
            <RefreshCw class="size-5 text-[#4fb250]" />
            <h2 class="text-xl font-bold tracking-tight sm:text-2xl">
                Recurring services
            </h2>
        </div>

        <div v-if="props.subscriptions.length" class="mt-6 space-y-5">
            <article
                v-for="subscription in props.subscriptions"
                :key="subscription.id"
                class="overflow-hidden rounded-2xl bg-card shadow-lg"
            >
                <div
                    class="flex flex-col justify-between gap-6 p-6 sm:flex-row sm:items-start sm:p-8"
                >
                    <div class="flex min-w-0 gap-4">
                        <img
                            v-if="subscription.product.featured_image"
                            :src="subscription.product.featured_image"
                            alt=""
                            class="size-16 shrink-0 rounded-xl object-cover"
                        />
                        <span
                            v-else
                            class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Package class="size-7" />
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-bold tracking-tight">
                                    {{ subscription.product.name }}
                                </h3>
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold"
                                    :class="statusClass(subscription.status)"
                                >
                                    {{ label(subscription.status) }}
                                </span>
                            </div>
                            <p
                                class="mt-1 font-mono text-xs text-muted-foreground"
                            >
                                {{ subscription.license.license_key }}
                            </p>
                            <p
                                v-if="subscription.cancel_at_period_end"
                                class="mt-3 text-sm font-semibold text-amber-600"
                            >
                                Automatic renewal is off. Access continues until
                                {{ date(subscription.current_period_end) }}.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="subscription.details_url"
                            class="inline-flex items-center rounded-md border px-4 py-2 text-sm font-semibold hover:bg-muted"
                        >
                            View details
                        </Link>
                        <Link
                            :href="subscription.license.url"
                            class="inline-flex items-center rounded-md border px-4 py-2 text-sm font-semibold hover:bg-muted"
                        >
                            Manage product
                        </Link>
                        <button
                            v-if="
                                subscription.can_update_payment_method &&
                                !subscription.payment_attention_required
                            "
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold hover:bg-muted"
                            @click="updatePaymentMethod(subscription)"
                        >
                            <CreditCard class="size-4" /> Payment method
                        </button>
                        <button
                            v-if="subscription.can_cancel"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                            @click="cancel(subscription)"
                        >
                            <XCircle class="size-4" /> Cancel renewal
                        </button>
                        <button
                            v-if="subscription.can_resume"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md bg-[#5cb85c] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4cae4c]"
                            @click="resume(subscription)"
                        >
                            <RefreshCw class="size-4" /> Resume renewal
                        </button>
                    </div>
                </div>

                <div
                    v-if="subscription.payment_attention_required"
                    class="mx-6 mb-6 flex flex-col gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-950 sm:mx-8 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex gap-3">
                        <AlertTriangle
                            class="mt-0.5 size-5 shrink-0 text-amber-600"
                        />
                        <div>
                            <p class="font-bold">Payment needs attention</p>
                            <p class="mt-0.5 text-sm leading-6 text-amber-800">
                                The latest renewal payment failed<span
                                    v-if="subscription.last_payment_failure_at"
                                >
                                    on
                                    {{
                                        date(
                                            subscription.last_payment_failure_at,
                                        )
                                    }}</span
                                >. Update your payment method to avoid service
                                interruption.
                            </p>
                        </div>
                    </div>
                    <button
                        v-if="subscription.can_update_payment_method"
                        type="button"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md bg-amber-600 px-4 py-2 text-sm font-bold text-white hover:bg-amber-700"
                        @click="updatePaymentMethod(subscription)"
                    >
                        <CreditCard class="size-4" /> Update payment method
                    </button>
                </div>

                <dl class="grid gap-px bg-border sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-card p-5">
                        <dt
                            class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground"
                        >
                            <CreditCard class="size-3.5" /> Recurring charge
                        </dt>
                        <dd class="mt-1 text-sm font-bold">
                            {{
                                money(
                                    subscription.currency,
                                    subscription.amount,
                                )
                            }}
                            /
                            {{ label(subscription.billing_cycle) }}
                        </dd>
                    </div>
                    <div class="bg-card p-5">
                        <dt class="text-xs font-semibold text-muted-foreground">
                            Next renewal
                        </dt>
                        <dd class="mt-1 text-sm font-bold">
                            {{ date(subscription.current_period_end) }}
                        </dd>
                    </div>
                    <div class="bg-card p-5">
                        <dt class="text-xs font-semibold text-muted-foreground">
                            Last payment
                        </dt>
                        <dd class="mt-1 text-sm font-bold">
                            {{ date(subscription.last_payment_at) }}
                        </dd>
                    </div>
                    <div class="bg-card p-5">
                        <dt class="text-xs font-semibold text-muted-foreground">
                            Payment gateway
                        </dt>
                        <dd class="mt-1 text-sm font-bold">
                            {{ label(subscription.gateway) }}
                        </dd>
                    </div>
                </dl>
            </article>
        </div>

        <div
            v-else
            class="mt-6 rounded-2xl border border-dashed p-12 text-center"
        >
            <CalendarClock class="mx-auto size-10 text-muted-foreground/50" />
            <p class="mt-4 font-semibold">No recurring subscriptions</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Monthly and yearly purchases will appear here.
            </p>
        </div>
    </section>
</template>
