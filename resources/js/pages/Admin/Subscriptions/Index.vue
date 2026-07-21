<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Eye, RefreshCw, Search, XCircle } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

const props = defineProps(['filters', 'statusOptions', 'subscriptions']);
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const applyFilters = () =>
    router.get(
        '/admin/subscriptions',
        { search: search.value, status: status.value },
        { preserveState: true, replace: true },
    );

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const money = (currency: string, amount: string) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(
        Number(amount),
    );

const date = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(
              new Date(value),
          )
        : '—';

const statusClass = (value: string) =>
    ({
        active: 'bg-emerald-100 text-emerald-700',
        trialing: 'bg-sky-100 text-sky-700',
        past_due: 'bg-amber-100 text-amber-700',
        paused: 'bg-slate-200 text-slate-700',
        canceled: 'bg-red-100 text-red-700',
        incomplete: 'bg-orange-100 text-orange-700',
    })[value] ?? 'bg-muted text-muted-foreground';

const cancel = (subscription) => {
    if (confirm(`Schedule cancellation for ${subscription.product.name}?`)) {
        router.post(
            `/admin/subscriptions/${subscription.id}/cancel`,
            {},
            { preserveScroll: true },
        );
    }
};

const resume = (subscription) =>
    router.post(
        `/admin/subscriptions/${subscription.id}/resume`,
        {},
        { preserveScroll: true },
    );

const paginationLabel = (value: string) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <Head title="Subscriptions" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Billing</p>
            <h1 class="text-3xl font-semibold tracking-tight">Subscriptions</h1>
            <p class="mt-1 text-muted-foreground">
                Recurring billing status, renewal dates, and gateway references.
            </p>
        </div>

        <form
            class="flex max-w-2xl flex-wrap gap-3"
            @submit.prevent="applyFilters"
        >
            <Input
                v-model="search"
                class="min-w-56 flex-1"
                placeholder="Customer, product, gateway subscription ID"
            />
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
            <Button type="submit" variant="outline"
                ><Search class="size-4" /> Filter</Button
            >
        </form>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="subscriptions.data.length === 0"
                    class="p-12 text-center text-sm text-muted-foreground"
                >
                    No subscriptions found.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[1200px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3.5">Customer</th>
                                <th class="px-5 py-3.5">Product / License</th>
                                <th class="px-5 py-3.5">Recurring</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Next renewal</th>
                                <th class="px-5 py-3.5">Gateway reference</th>
                                <th class="px-5 py-3.5">Renewals</th>
                                <th class="px-5 py-3.5">Payment failures</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="subscription in subscriptions.data"
                                :key="subscription.id"
                                class="border-b last:border-b-0 hover:bg-muted/40"
                            >
                                <td class="px-5 py-4">
                                    <Link
                                        :href="`/admin/users/${subscription.user.id}`"
                                        class="font-semibold hover:underline"
                                    >
                                        {{ subscription.user.name }}
                                    </Link>
                                    <p class="text-xs text-muted-foreground">
                                        {{ subscription.user.email }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">
                                        {{ subscription.product.name }}
                                    </p>
                                    <Link
                                        :href="`/admin/licenses/${subscription.license.id}`"
                                        class="font-mono text-xs text-primary hover:underline"
                                    >
                                        {{ subscription.license.license_key }}
                                    </Link>
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    {{
                                        money(
                                            subscription.currency,
                                            subscription.amount,
                                        )
                                    }}
                                    /
                                    {{ label(subscription.billing_cycle) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="
                                            statusClass(subscription.status)
                                        "
                                    >
                                        {{ label(subscription.status) }}
                                    </span>
                                    <p
                                        v-if="subscription.cancel_at_period_end"
                                        class="mt-1 text-xs font-semibold text-amber-600"
                                    >
                                        Ends this period
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ date(subscription.current_period_end) }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium">
                                        {{ label(subscription.gateway) }}
                                    </p>
                                    <p
                                        class="max-w-48 truncate font-mono text-xs text-muted-foreground"
                                    >
                                        {{
                                            subscription.gateway_subscription_id ??
                                            'Local'
                                        }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    {{ subscription.renewal_orders_count }}
                                </td>
                                <td class="px-5 py-4">
                                    <div
                                        v-if="
                                            subscription.failed_payments_count
                                        "
                                        class="flex items-start gap-2 text-amber-700"
                                    >
                                        <AlertTriangle
                                            class="mt-0.5 size-4 shrink-0"
                                        />
                                        <div>
                                            <p class="font-bold">
                                                {{
                                                    subscription.failed_payments_count
                                                }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    date(
                                                        subscription.last_payment_failure_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="ghost"
                                        >
                                            <Link
                                                :href="`/admin/subscriptions/${subscription.id}`"
                                            >
                                                <Eye class="size-4" /> View
                                            </Link>
                                        </Button>
                                        <Button
                                            v-if="
                                                !subscription.cancel_at_period_end &&
                                                ['active', 'trialing'].includes(
                                                    subscription.status,
                                                )
                                            "
                                            size="sm"
                                            variant="outline"
                                            @click="cancel(subscription)"
                                        >
                                            <XCircle class="size-4" /> Cancel
                                        </Button>
                                        <Button
                                            v-if="
                                                subscription.cancel_at_period_end &&
                                                subscription.status !==
                                                    'canceled'
                                            "
                                            size="sm"
                                            variant="outline"
                                            @click="resume(subscription)"
                                        >
                                            <RefreshCw class="size-4" /> Resume
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div
            v-if="subscriptions.last_page > 1"
            class="flex items-center justify-between gap-4 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ subscriptions.from }}–{{ subscriptions.to }} of
                {{ subscriptions.total }}
            </p>
            <div class="flex gap-2">
                <template v-for="link in subscriptions.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        as-child
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                    >
                        <Link :href="link.url">{{
                            paginationLabel(link.label)
                        }}</Link>
                    </Button>
                    <Button v-else size="sm" variant="outline" disabled>{{
                        paginationLabel(link.label)
                    }}</Button>
                </template>
            </div>
        </div>
    </div>
</template>
