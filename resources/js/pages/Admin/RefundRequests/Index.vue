<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Search } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    filters: { status: string; search: string };
    statusOptions: string[];
    requests: Record<string, any>;
}>();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'pending');
const filter = () =>
    router.get(
        '/admin/refund-requests',
        { search: search.value, status: status.value },
        { preserveState: true, replace: true },
    );
const label = (value: string) =>
    value
        .split('_')
        .map((part) => part[0].toUpperCase() + part.slice(1))
        .join(' ');
const money = (currency: string, amount: string) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(
        Number(amount),
    );
const date = (value: string) =>
    new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
const statusClass = (value: string) =>
    ({
        pending: 'bg-amber-100 text-amber-800',
        approved: 'bg-emerald-100 text-emerald-800',
        rejected: 'bg-red-100 text-red-800',
        cancelled: 'bg-slate-100 text-slate-700',
    })[value] ?? 'bg-muted text-muted-foreground';
const pageLabel = (value: string) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <Head title="Refund requests" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Billing</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                Refund requests
            </h1>
            <p class="mt-1 text-muted-foreground">
                Review customer requests before money or product access changes.
            </p>
        </div>

        <form class="flex max-w-2xl flex-wrap gap-3" @submit.prevent="filter">
            <Input
                v-model="search"
                class="min-w-56 flex-1"
                placeholder="Request, invoice, customer, email"
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
                    v-if="requests.data.length === 0"
                    class="p-12 text-center text-sm text-muted-foreground"
                >
                    No refund requests found.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[940px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3.5">Request</th>
                                <th class="px-5 py-3.5">Customer</th>
                                <th class="px-5 py-3.5">Invoice / Product</th>
                                <th class="px-5 py-3.5 text-right">Amount</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Submitted</th>
                                <th class="px-5 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in requests.data"
                                :key="item.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-5 py-4 font-mono font-bold">
                                    {{ item.request_number }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">
                                        {{ item.user.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ item.user.email }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-mono text-xs font-semibold">
                                        {{ item.invoice.invoice_number }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ item.invoice.order.product.name }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-right font-bold">
                                    {{ money(item.currency, item.amount) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="statusClass(item.status)"
                                        >{{ label(item.status) }}</span
                                    >
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ date(item.submitted_at) }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <Button as-child size="sm" variant="ghost"
                                        ><Link
                                            :href="`/admin/refund-requests/${item.id}`"
                                            ><Eye class="size-4" /> Review</Link
                                        ></Button
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div
            v-if="requests.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ requests.from }}–{{ requests.to }} of
                {{ requests.total }}
            </p>
            <div class="flex gap-2">
                <template v-for="link in requests.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        as-child
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        ><Link :href="link.url">{{
                            pageLabel(link.label)
                        }}</Link></Button
                    >
                    <Button v-else size="sm" variant="outline" disabled>{{
                        pageLabel(link.label)
                    }}</Button>
                </template>
            </div>
        </div>
    </div>
</template>
