<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    CalendarClock,
    CheckCircle2,
    CreditCard,
    FileText,
    RefreshCw,
    UserRound,
    XCircle,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    subscription: Record<string, any>;
    events: Record<string, any>[];
    renewals: Record<string, any>[];
}>();

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const money = (currency: string, amount: string) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(
        Number(amount),
    );

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

const cancel = () => {
    if (
        confirm(`Schedule cancellation for ${props.subscription.product.name}?`)
    ) {
        router.post(`/admin/subscriptions/${props.subscription.id}/cancel`);
    }
};

const resume = () =>
    router.post(`/admin/subscriptions/${props.subscription.id}/resume`);
</script>

<template>
    <Head :title="`${subscription.product.name} subscription`" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Link
                    href="/admin/subscriptions"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary"
                >
                    <ArrowLeft class="size-4" /> Back to subscriptions
                </Link>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-semibold tracking-tight">
                        {{ subscription.product.name }}
                    </h1>
                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                        :class="statusClass(subscription.status)"
                    >
                        {{ label(subscription.status) }}
                    </span>
                </div>
                <p class="mt-1 font-mono text-xs text-muted-foreground">
                    {{
                        subscription.gateway_subscription_id ??
                        'Local subscription'
                    }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button
                    v-if="
                        !subscription.cancel_at_period_end &&
                        ['active', 'trialing'].includes(subscription.status)
                    "
                    variant="outline"
                    @click="cancel"
                >
                    <XCircle class="size-4" /> Cancel
                </Button>
                <Button
                    v-if="
                        subscription.cancel_at_period_end &&
                        subscription.status !== 'canceled'
                    "
                    variant="outline"
                    @click="resume"
                >
                    <RefreshCw class="size-4" /> Resume
                </Button>
            </div>
        </div>

        <div
            v-if="subscription.status === 'past_due'"
            class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-950"
        >
            <AlertTriangle class="mt-0.5 size-5 shrink-0 text-amber-600" />
            <div>
                <p class="font-bold">Payment recovery in progress</p>
                <p class="text-sm text-amber-800">
                    License grace access ends
                    {{ date(subscription.grace_period_ends_at, true) }}.
                </p>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardContent class="p-5">
                    <CreditCard class="size-5 text-primary" />
                    <p class="mt-3 text-xs font-semibold text-muted-foreground">
                        Recurring charge
                    </p>
                    <p class="mt-1 text-lg font-bold">
                        {{ money(subscription.currency, subscription.amount) }}
                        / {{ label(subscription.billing_cycle) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-5">
                    <CalendarClock class="size-5 text-primary" />
                    <p class="mt-3 text-xs font-semibold text-muted-foreground">
                        Current period ends
                    </p>
                    <p class="mt-1 text-lg font-bold">
                        {{ date(subscription.current_period_end) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-5">
                    <UserRound class="size-5 text-primary" />
                    <p class="mt-3 text-xs font-semibold text-muted-foreground">
                        Customer
                    </p>
                    <Link
                        :href="`/admin/users/${subscription.user.id}`"
                        class="mt-1 block font-bold hover:underline"
                    >
                        {{ subscription.user.name }}
                    </Link>
                    <p class="text-xs text-muted-foreground">
                        {{ subscription.user.email }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-5">
                    <CheckCircle2 class="size-5 text-primary" />
                    <p class="mt-3 text-xs font-semibold text-muted-foreground">
                        License
                    </p>
                    <Link
                        :href="`/admin/licenses/${subscription.license.id}`"
                        class="mt-1 block font-mono text-sm font-bold text-primary hover:underline"
                    >
                        {{ subscription.license.license_key }}
                    </Link>
                    <p class="text-xs text-muted-foreground">
                        {{ label(subscription.license.status) }} · expires
                        {{ date(subscription.license.expires_at) }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_1fr]">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="size-5" /> Billing history
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div
                        v-if="!renewals.length"
                        class="px-6 pb-8 text-sm text-muted-foreground"
                    >
                        No renewal invoices yet. Initial order:
                        {{ subscription.initial_order.order_number }}.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b text-xs text-muted-foreground uppercase"
                                >
                                    <th class="px-5 py-3">Order</th>
                                    <th class="px-5 py-3">Paid</th>
                                    <th class="px-5 py-3">Reference</th>
                                    <th class="px-5 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="renewal in renewals"
                                    :key="renewal.id"
                                    class="border-b last:border-0"
                                >
                                    <td
                                        class="px-5 py-4 font-mono font-semibold"
                                    >
                                        {{ renewal.order_number }}
                                        <Link
                                            v-if="renewal.invoice"
                                            :href="`/admin/invoices/${renewal.invoice.id}`"
                                            class="block text-xs text-primary hover:underline"
                                        >
                                            {{ renewal.invoice.invoice_number }}
                                        </Link>
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ date(renewal.paid_at) }}
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs">
                                        {{ renewal.payment_reference ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-bold">
                                        {{
                                            money(
                                                renewal.currency,
                                                renewal.amount,
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Lifecycle events</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="events.length" class="space-y-5">
                        <div
                            v-for="event in events"
                            :key="event.id"
                            class="border-l-2 border-primary/30 pl-4"
                        >
                            <p class="text-sm font-bold">
                                {{
                                    label(event.event_type.replaceAll('.', '_'))
                                }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ date(event.processed_at, true) }} ·
                                {{ label(event.gateway) }}
                            </p>
                            <p
                                class="mt-1 font-mono text-[11px] break-all text-muted-foreground"
                            >
                                {{ event.gateway_event_id }}
                            </p>
                            <details
                                v-if="
                                    event.payload &&
                                    Object.keys(event.payload).length
                                "
                                class="mt-2 text-xs"
                            >
                                <summary class="cursor-pointer font-semibold">
                                    Payload
                                </summary>
                                <pre
                                    class="mt-2 overflow-x-auto rounded bg-muted p-3"
                                    >{{
                                        JSON.stringify(event.payload, null, 2)
                                    }}</pre>
                            </details>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        No lifecycle events recorded yet.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
