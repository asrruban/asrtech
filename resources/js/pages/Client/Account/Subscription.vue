<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    CalendarClock,
    CheckCircle2,
    CreditCard,
    FileText,
    History,
    Package,
    RefreshCw,
    XCircle,
} from '@lucide/vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface SubscriptionDetail {
    id: number;
    status: string;
    billing_cycle: string;
    currency: string;
    amount: string;
    gateway: string;
    current_period_start: string | null;
    current_period_end: string | null;
    cancel_at_period_end: boolean;
    last_payment_at: string | null;
    payment_attention_required: boolean;
    can_update_payment_method: boolean;
    can_cancel: boolean;
    can_resume: boolean;
    can_extend: boolean;
    grace_period_ends_at: string | null;
    product: { name: string; featured_image: string | null; url: string };
    license: { license_key: string; status: string; url: string };
    initial_order: {
        order_number: string;
        paid_at: string | null;
        invoice: { invoice_number: string; url: string } | null;
    };
}

interface TimelineEvent {
    id: number;
    type: string;
    occurred_at: string;
}

interface Renewal {
    id: number;
    order_number: string;
    amount: string;
    currency: string;
    paid_at: string | null;
    invoice: { invoice_number: string; status: string; url: string } | null;
}

const props = defineProps<{
    subscription: SubscriptionDetail;
    events: TimelineEvent[];
    renewals: Renewal[];
}>();

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

const date = (value: string | null, includeTime = false) =>
    value
        ? new Intl.DateTimeFormat('en', {
              dateStyle: 'medium',
              ...(includeTime ? { timeStyle: 'short' as const } : {}),
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

const eventLabel = (type: string) =>
    ({
        'checkout.session.completed': 'Subscription activated',
        'invoice.paid': 'Renewal payment received',
        'invoice.payment_failed': 'Renewal payment failed',
        'customer.subscription.updated': 'Subscription updated',
        'subscription.cancellation_scheduled': 'Cancellation scheduled',
        'subscription.renewal_resumed': 'Automatic renewal restored',
        'subscription.renewal_reminder_sent': 'Renewal reminder sent',
        'subscription.ended': 'Subscription ended',
        'subscription.grace_expired': 'Payment grace period ended',
    })[type] ?? label(type.split('.').at(-1) ?? type);

const eventTone = (type: string) => {
    if (type.includes('failed') || type.includes('expired')) {
        return 'bg-red-100 text-red-600';
    }

    if (
        type.includes('paid') ||
        type.includes('activated') ||
        type.includes('resumed')
    ) {
        return 'bg-emerald-100 text-emerald-600';
    }

    if (type.includes('cancel') || type.includes('ended')) {
        return 'bg-amber-100 text-amber-600';
    }

    return 'bg-sky-100 text-sky-600';
};

const cancel = () => {
    if (!confirm('Stop automatic renewal at the end of this billing period?')) {
        return;
    }

    router.post(`/client-area/subscriptions/${props.subscription.id}/cancel`);
};

const resume = () =>
    router.post(`/client-area/subscriptions/${props.subscription.id}/resume`);

const updatePaymentMethod = () =>
    router.post(
        `/client-area/subscriptions/${props.subscription.id}/billing-portal`,
    );
</script>

<template>
    <SeoHead
        :title="`${props.subscription.product.name} subscription`"
        description="Subscription status, billing history, and lifecycle activity."
    />

    <ClientAreaHero
        :title="props.subscription.product.name"
        subtitle="Subscription details, renewal history, and service activity."
    />

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <Link
            href="/client-area/subscriptions"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to subscriptions
        </Link>

        <div class="mt-6 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div
                class="flex flex-col justify-between gap-6 p-6 sm:flex-row sm:items-start sm:p-8"
            >
                <div class="flex min-w-0 gap-4">
                    <img
                        v-if="props.subscription.product.featured_image"
                        :src="props.subscription.product.featured_image"
                        alt=""
                        class="size-16 rounded-xl object-cover"
                    />
                    <span
                        v-else
                        class="flex size-16 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <Package class="size-7" />
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-bold">
                                {{ props.subscription.product.name }}
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-bold"
                                :class="statusClass(props.subscription.status)"
                            >
                                {{ label(props.subscription.status) }}
                            </span>
                        </div>
                        <p class="mt-1 font-mono text-xs text-muted-foreground">
                            {{ props.subscription.license.license_key }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="props.subscription.can_update_payment_method"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold hover:bg-muted"
                        @click="updatePaymentMethod"
                    >
                        <CreditCard class="size-4" /> Payment method
                    </button>
                    <button
                        v-if="props.subscription.can_cancel"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                        @click="cancel"
                    >
                        <XCircle class="size-4" /> Cancel renewal
                    </button>
                    <button
                        v-if="props.subscription.can_resume"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-[#5cb85c] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4cae4c]"
                        @click="resume"
                    >
                        <RefreshCw class="size-4" /> Resume renewal
                    </button>
                    <Link
                        v-if="props.subscription.can_extend"
                        :href="`/client-area/subscriptions/${props.subscription.id}/extend`"
                        class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm"
                    >
                        <CreditCard class="size-4" /> Extend to Billing Cycle
                    </Link>
                </div>
            </div>

            <div
                v-if="props.subscription.gateway === 'free_trial' && props.subscription.status === 'trialing'"
                class="border-t border-blue-200 bg-blue-50 px-6 py-4 text-blue-950 sm:px-8"
            >
                <div class="flex gap-3">
                    <CalendarClock
                        class="mt-0.5 size-5 shrink-0 text-blue-600"
                    />
                    <div>
                        <p class="font-bold">7 Days Free Trial Active</p>
                        <p class="mt-0.5 text-sm text-blue-800">
                            This trial period ends on {{ date(props.subscription.current_period_end) }}. Extend to a regular billing cycle to continue access.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="props.subscription.payment_attention_required"
                class="border-t border-amber-200 bg-amber-50 px-6 py-4 text-amber-950 sm:px-8"
            >
                <div class="flex gap-3">
                    <AlertTriangle
                        class="mt-0.5 size-5 shrink-0 text-amber-600"
                    />
                    <div>
                        <p class="font-bold">Payment needs attention</p>
                        <p class="mt-0.5 text-sm text-amber-800">
                            Update your payment method to recover automatic
                            renewal.
                            <template
                                v-if="props.subscription.grace_period_ends_at"
                            >
                                License access remains available through
                                {{
                                    date(
                                        props.subscription.grace_period_ends_at,
                                    )
                                }}.
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            <dl class="grid gap-px bg-border sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-card p-5">
                    <dt class="text-xs font-semibold text-muted-foreground">
                        Recurring charge
                    </dt>
                    <dd class="mt-1 font-bold">
                        {{
                            money(
                                props.subscription.currency,
                                props.subscription.amount,
                            )
                        }}
                        / {{ label(props.subscription.billing_cycle) }}
                    </dd>
                </div>
                <div class="bg-card p-5">
                    <dt class="text-xs font-semibold text-muted-foreground">
                        Next renewal
                    </dt>
                    <dd class="mt-1 font-bold">
                        {{ date(props.subscription.current_period_end) }}
                    </dd>
                </div>
                <div class="bg-card p-5">
                    <dt class="text-xs font-semibold text-muted-foreground">
                        Last payment
                    </dt>
                    <dd class="mt-1 font-bold">
                        {{ date(props.subscription.last_payment_at) }}
                    </dd>
                </div>
                <div class="bg-card p-5">
                    <dt class="text-xs font-semibold text-muted-foreground">
                        License status
                    </dt>
                    <dd class="mt-1 font-bold">
                        {{ label(props.subscription.license.status) }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_1.15fr]">
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex items-center gap-2">
                    <History class="size-5 text-primary" />
                    <h2 class="text-lg font-bold">Activity timeline</h2>
                </div>
                <div class="mt-6 space-y-0">
                    <div
                        v-for="event in props.events"
                        :key="event.id"
                        class="relative flex gap-4 pb-6 last:pb-0"
                    >
                        <span
                            class="absolute top-8 bottom-0 left-4 w-px bg-border last:hidden"
                        />
                        <span
                            class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full"
                            :class="eventTone(event.type)"
                        >
                            <CheckCircle2 class="size-4" />
                        </span>
                        <div>
                            <p class="text-sm font-bold">
                                {{ eventLabel(event.type) }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ date(event.occurred_at, true) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <span
                            class="flex size-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"
                        >
                            <CheckCircle2 class="size-4" />
                        </span>
                        <div>
                            <p class="text-sm font-bold">
                                Subscription created
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{
                                    date(
                                        props.subscription.initial_order
                                            .paid_at,
                                        true,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border bg-card shadow-sm">
                <div class="flex items-center gap-2 border-b p-6">
                    <FileText class="size-5 text-primary" />
                    <h2 class="text-lg font-bold">Billing history</h2>
                </div>
                <div class="divide-y">
                    <div
                        v-for="renewal in props.renewals"
                        :key="renewal.id"
                        class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"
                    >
                        <div>
                            <p class="font-mono text-sm font-bold">
                                {{ renewal.order_number }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ date(renewal.paid_at) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold">
                                {{ money(renewal.currency, renewal.amount) }}
                            </p>
                            <Link
                                v-if="renewal.invoice"
                                :href="renewal.invoice.url"
                                class="text-xs font-semibold text-primary hover:underline"
                            >
                                {{ renewal.invoice.invoice_number }}
                            </Link>
                        </div>
                    </div>
                    <div
                        v-if="!props.renewals.length"
                        class="px-6 py-10 text-center"
                    >
                        <CalendarClock
                            class="mx-auto size-8 text-muted-foreground/50"
                        />
                        <p class="mt-3 text-sm font-semibold">
                            No renewal payments yet
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            The initial order is
                            {{ props.subscription.initial_order.order_number }}.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
