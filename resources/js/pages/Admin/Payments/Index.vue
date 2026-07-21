<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    Banknote,
    CheckCircle2,
    CopyCheck,
    Eye,
    Search,
    Webhook,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    filters: { status: string; gateway: string; search: string };
    statusOptions: string[];
    gatewayOptions: { value: string; label: string }[];
    stats: Record<string, any>;
    events: Record<string, any>;
    transactions: Record<string, any>[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const gateway = ref(props.filters.gateway ?? '');

const applyFilters = () =>
    router.get(
        '/admin/payments',
        {
            search: search.value,
            status: status.value,
            gateway: gateway.value,
        },
        { preserveState: true, replace: true },
    );

const label = (value: string | null) =>
    value
        ? value
              .split(/[._-]/)
              .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
              .join(' ')
        : 'Unknown event';

const money = (currency: string, amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const dateTime = (value: string) =>
    new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

const statusClass = (value: string) =>
    ({
        processed:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        processing:
            'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        pending:
            'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    })[value] ?? 'bg-muted text-muted-foreground';

const paginationLabel = (value: string) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <Head title="Payment reliability" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <section
            class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-2xl shadow-slate-200 sm:px-8 lg:px-10 dark:shadow-none"
        >
            <div
                class="pointer-events-none absolute -top-28 -right-16 size-80 rounded-full bg-emerald-400/15 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-32 left-1/3 size-72 rounded-full bg-blue-600/20 blur-3xl"
            />
            <div
                class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
            >
                <div>
                    <div
                        class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-emerald-200"
                    >
                        <Activity class="size-3.5" />
                        Gateway operations
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        Payment reliability center
                    </h1>
                    <p
                        class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base"
                    >
                        Monitor webhook delivery, duplicate protection,
                        processing failures, payments, and refunds from one
                        operational view.
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4"
                >
                    <p class="text-xs font-medium text-slate-400">
                        24-hour processing rate
                    </p>
                    <p class="mt-1 text-3xl font-bold text-emerald-300">
                        {{ stats.success_rate }}%
                    </p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardContent class="flex items-start justify-between p-5">
                    <div>
                        <p class="text-sm text-muted-foreground">
                            Webhooks · 24h
                        </p>
                        <p class="mt-2 text-3xl font-bold">
                            {{ stats.events_24h }}
                        </p>
                        <p class="mt-1 text-xs text-emerald-600">
                            {{ stats.processed_24h }} processed
                        </p>
                    </div>
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300"
                    >
                        <Webhook class="size-5" />
                    </span>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex items-start justify-between p-5">
                    <div>
                        <p class="text-sm text-muted-foreground">
                            Failures · 24h
                        </p>
                        <p class="mt-2 text-3xl font-bold">
                            {{ stats.failed_24h }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Needs investigation
                        </p>
                    </div>
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300"
                    >
                        <AlertTriangle class="size-5" />
                    </span>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex items-start justify-between p-5">
                    <div>
                        <p class="text-sm text-muted-foreground">
                            Duplicates blocked
                        </p>
                        <p class="mt-2 text-3xl font-bold">
                            {{ stats.duplicates_24h }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Safe retries in 24h
                        </p>
                    </div>
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300"
                    >
                        <CopyCheck class="size-5" />
                    </span>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex items-start justify-between p-5">
                    <div>
                        <p class="text-sm text-muted-foreground">
                            Payments · 30d
                        </p>
                        <p class="mt-2 text-2xl font-bold">
                            {{ money(stats.currency, stats.payments_30d) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ money(stats.currency, stats.refunds_30d) }}
                            refunded
                        </p>
                    </div>
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        <Banknote class="size-5" />
                    </span>
                </CardContent>
            </Card>
        </section>

        <Card>
            <CardContent class="p-5">
                <form
                    class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_190px_190px_auto]"
                    @submit.prevent="applyFilters"
                >
                    <Input
                        v-model="search"
                        placeholder="Event ID or event type"
                    />
                    <select
                        v-model="gateway"
                        class="h-9 rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option value="">All gateways</option>
                        <option
                            v-for="option in gatewayOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <select
                        v-model="status"
                        class="h-9 rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option value="">All statuses</option>
                        <option
                            v-for="option in statusOptions"
                            :key="option"
                            :value="option"
                        >
                            {{ label(option) }}
                        </option>
                    </select>
                    <Button type="submit" variant="outline">
                        <Search class="size-4" /> Filter
                    </Button>
                </form>
            </CardContent>
        </Card>

        <section
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 px-5 py-5 sm:px-6 dark:border-white/10"
            >
                <div>
                    <h2 class="font-semibold">Webhook events</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        One durable record per gateway event ID.
                    </p>
                </div>
                <span
                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold dark:bg-white/10"
                >
                    {{ events.total }} events
                </span>
            </div>

            <div
                v-if="events.data.length === 0"
                class="flex min-h-60 flex-col items-center justify-center p-10 text-center"
            >
                <CheckCircle2 class="size-9 text-emerald-500" />
                <p class="mt-3 font-semibold">No matching webhook events</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    New gateway callbacks will appear here automatically.
                </p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-5 py-3.5">Gateway / event</th>
                            <th class="px-5 py-3.5">Provider event ID</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Attempts</th>
                            <th class="px-5 py-3.5">Duplicates</th>
                            <th class="px-5 py-3.5">Received</th>
                            <th class="px-5 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="event in events.data"
                            :key="event.id"
                            class="border-b last:border-b-0 hover:bg-muted/40"
                        >
                            <td class="px-5 py-4">
                                <p class="font-semibold">
                                    {{ label(event.gateway) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ label(event.event_type) }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <code
                                    class="block max-w-64 truncate text-xs"
                                    :title="event.external_id"
                                >
                                    {{ event.external_id }}
                                </code>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold"
                                    :class="statusClass(event.status)"
                                >
                                    {{ label(event.status) }}
                                </span>
                                <p
                                    v-if="event.response_code"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    HTTP {{ event.response_code }}
                                </p>
                            </td>
                            <td class="px-5 py-4 font-semibold">
                                {{ event.attempts }}
                            </td>
                            <td class="px-5 py-4 font-semibold">
                                {{ event.duplicate_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ dateTime(event.created_at) }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <Button as-child size="sm" variant="ghost">
                                    <Link
                                        :href="
                                            '/admin/payments/webhooks/' +
                                            event.id
                                        "
                                    >
                                        <Eye class="size-4" /> Inspect
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="events.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-4 border-t px-5 py-4 text-sm"
            >
                <p class="text-muted-foreground">
                    Showing {{ events.from }}–{{ events.to }} of
                    {{ events.total }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <template v-for="link in events.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            as-child
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                        >
                            <Link :href="link.url">
                                {{ paginationLabel(link.label) || 'Page' }}
                            </Link>
                        </Button>
                    </template>
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900"
        >
            <div
                class="border-b border-slate-100 px-5 py-5 sm:px-6 dark:border-white/10"
            >
                <h2 class="font-semibold">Recent transactions</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Payments and refunds already recorded in the financial
                    ledger.
                </p>
            </div>
            <div
                v-if="transactions.length === 0"
                class="p-10 text-center text-sm text-muted-foreground"
            >
                No transactions have been recorded yet.
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-5 py-3.5">Order / customer</th>
                            <th class="px-5 py-3.5">Product</th>
                            <th class="px-5 py-3.5">Gateway</th>
                            <th class="px-5 py-3.5">Reference</th>
                            <th class="px-5 py-3.5">Type</th>
                            <th class="px-5 py-3.5 text-right">Amount</th>
                            <th class="px-5 py-3.5">Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="transaction in transactions"
                            :key="transaction.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-5 py-4">
                                <Link
                                    :href="
                                        '/admin/users/' +
                                        transaction.order.user.id +
                                        '/orders'
                                    "
                                    class="font-semibold hover:underline"
                                >
                                    {{ transaction.order.order_number }}
                                </Link>
                                <p class="text-xs text-muted-foreground">
                                    {{ transaction.order.user.name }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                {{ transaction.order.product.name }}
                            </td>
                            <td class="px-5 py-4">
                                {{ label(transaction.gateway) }}
                            </td>
                            <td class="px-5 py-4">
                                <code
                                    class="block max-w-48 truncate text-xs text-muted-foreground"
                                >
                                    {{ transaction.reference ?? '—' }}
                                </code>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold"
                                    :class="
                                        transaction.type === 'refund'
                                            ? 'bg-red-100 text-red-700'
                                            : 'bg-emerald-100 text-emerald-700'
                                    "
                                >
                                    {{ label(transaction.type) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-semibold">
                                {{
                                    money(
                                        transaction.order.currency,
                                        transaction.amount,
                                    )
                                }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ dateTime(transaction.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
