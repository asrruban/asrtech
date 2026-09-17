<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    filters: { from: string; to: string };
    currency: string;
    totals: {
        revenue: number;
        orders: number;
        tax: number;
        average_order: number;
    };
    daily: { day: string; orders: number; revenue: number; tax: number }[];
    gateways: { gateway: string; payments: number; volume: number }[];
    topProducts: { product: string; sales: number; revenue: number }[];
    subscriptions: {
        by_status: Record<string, number>;
        mrr: number;
        new_in_range: number;
        past_due: number;
    };
    exports: string[];
}>();

const from = ref(props.filters.from);
const to = ref(props.filters.to);

const applyRange = () =>
    router.get(
        '/admin/reports',
        { from: from.value, to: to.value },
        { preserveState: true },
    );

const money = (value: number) =>
    `${props.currency} ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const maxRevenue = computed(() =>
    Math.max(1, ...props.daily.map((row) => row.revenue)),
);

const exportUrl = (dataset: string) =>
    `/admin/reports/export/${dataset}?from=${props.filters.from}&to=${props.filters.to}`;

const statusLabel = (status: string) =>
    status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
</script>

<template>
    <Head title="Reports" />

    <div class="space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Reports</h1>
                <p class="text-sm text-muted-foreground">
                    Revenue, gateways, subscriptions, and data exports.
                </p>
            </div>
            <div class="flex items-end gap-3">
                <div class="space-y-1">
                    <Label for="from">From</Label>
                    <Input id="from" v-model="from" type="date" class="w-40" />
                </div>
                <div class="space-y-1">
                    <Label for="to">To</Label>
                    <Input id="to" v-model="to" type="date" class="w-40" />
                </div>
                <Button @click="applyRange">Apply</Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Revenue</CardDescription>
                    <CardTitle class="text-2xl">{{
                        money(props.totals.revenue)
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Paid orders</CardDescription>
                    <CardTitle class="text-2xl">{{
                        props.totals.orders
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Tax collected</CardDescription>
                    <CardTitle class="text-2xl">{{
                        money(props.totals.tax)
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Average order value</CardDescription>
                    <CardTitle class="text-2xl">{{
                        money(props.totals.average_order)
                    }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Daily revenue</CardTitle>
                    <CardDescription
                        >Paid orders per day in the selected
                        range.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.daily.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No paid orders in this range.
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="row in props.daily"
                            :key="row.day"
                            class="flex items-center gap-3 text-sm"
                        >
                            <span
                                class="w-24 shrink-0 font-mono text-xs text-muted-foreground"
                                >{{ row.day }}</span
                            >
                            <div
                                class="h-4 flex-1 overflow-hidden rounded bg-muted"
                            >
                                <div
                                    class="h-full rounded bg-primary"
                                    :style="{
                                        width: `${(row.revenue / maxRevenue) * 100}%`,
                                    }"
                                />
                            </div>
                            <span
                                class="w-28 shrink-0 text-right font-medium"
                                >{{ money(row.revenue) }}</span
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Gateway volume</CardTitle>
                        <CardDescription
                            >Payment transactions by gateway.</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="pb-2 font-medium">Gateway</th>
                                    <th class="pb-2 text-right font-medium">
                                        Payments
                                    </th>
                                    <th class="pb-2 text-right font-medium">
                                        Volume
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in props.gateways"
                                    :key="row.gateway"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 font-medium capitalize">
                                        {{ row.gateway }}
                                    </td>
                                    <td class="py-2 text-right">
                                        {{ row.payments }}
                                    </td>
                                    <td class="py-2 text-right">
                                        {{ money(row.volume) }}
                                    </td>
                                </tr>
                                <tr v-if="props.gateways.length === 0">
                                    <td
                                        colspan="3"
                                        class="py-6 text-center text-muted-foreground"
                                    >
                                        No transactions.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Subscriptions</CardTitle>
                        <CardDescription>
                            MRR {{ money(props.subscriptions.mrr) }} ·
                            {{ props.subscriptions.new_in_range }} new in range
                            · {{ props.subscriptions.past_due }} past due
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(count, status) in props.subscriptions
                                    .by_status"
                                :key="status"
                                class="rounded-full border px-3 py-1 text-xs font-medium"
                                :class="
                                    status === 'past_due'
                                        ? 'border-amber-500/40 bg-amber-500/10 text-amber-600'
                                        : ''
                                "
                            >
                                {{ statusLabel(status) }}: {{ count }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Top products</CardTitle>
                    <CardDescription
                        >By revenue in the selected range.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-xs text-muted-foreground"
                            >
                                <th class="pb-2 font-medium">Product</th>
                                <th class="pb-2 text-right font-medium">
                                    Sales
                                </th>
                                <th class="pb-2 text-right font-medium">
                                    Revenue
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in props.topProducts"
                                :key="row.product"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 font-medium">
                                    {{ row.product }}
                                </td>
                                <td class="py-2 text-right">{{ row.sales }}</td>
                                <td class="py-2 text-right">
                                    {{ money(row.revenue) }}
                                </td>
                            </tr>
                            <tr v-if="props.topProducts.length === 0">
                                <td
                                    colspan="3"
                                    class="py-6 text-center text-muted-foreground"
                                >
                                    No sales.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Data exports</CardTitle>
                    <CardDescription
                        >Download CSV for the selected date
                        range.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <a
                            v-for="dataset in props.exports"
                            :key="dataset"
                            :href="exportUrl(dataset)"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-md border text-sm font-semibold capitalize transition hover:bg-muted"
                        >
                            <Download class="size-4" />
                            {{ dataset }}.csv
                        </a>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
