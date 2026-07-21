<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, FileText, Search } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

const props = defineProps(['filters', 'statuses', 'invoices']);

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const applyFilters = () =>
    router.get(
        '/admin/invoices',
        { search: search.value, status: status.value },
        { preserveState: true, replace: true },
    );

const money = (currency: string, amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const statusClass = (value: string) =>
    ({
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        partially_refunded:
            'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        issued: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        void: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
    })[value] ?? 'bg-muted text-muted-foreground';

const paginationLabel = (value: string) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <Head title="Invoices" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Billing</p>
            <h1 class="text-3xl font-semibold tracking-tight">Invoices</h1>
            <p class="mt-1 text-muted-foreground">
                Every invoice across all customers.
            </p>
        </div>

        <form
            class="flex max-w-2xl flex-wrap gap-3"
            @submit.prevent="applyFilters"
        >
            <Input
                v-model="search"
                class="min-w-56 flex-1"
                placeholder="Search invoice #, order #, customer"
            />
            <select
                v-model="status"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All statuses</option>
                <option
                    v-for="option in statuses"
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

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="invoices.data.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No invoices found.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3.5">Invoice</th>
                                <th class="px-5 py-3.5">Customer</th>
                                <th class="px-5 py-3.5">Product</th>
                                <th class="px-5 py-3.5">Amount</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Issued</th>
                                <th class="px-5 py-3.5">Due</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="invoice in invoices.data"
                                :key="invoice.id"
                                class="border-b last:border-b-0 hover:bg-muted/40"
                            >
                                <td
                                    class="px-5 py-4 font-mono text-xs font-semibold"
                                >
                                    {{ invoice.invoice_number }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium">
                                        {{ invoice.order.user.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ invoice.order.user.email }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    {{ invoice.order.product.name }}
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    {{
                                        money(
                                            invoice.order.currency,
                                            Number(invoice.order.amount) +
                                                Number(invoice.order.setup_fee),
                                        )
                                    }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="statusClass(invoice.status)"
                                    >
                                        {{ label(invoice.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ formatDate(invoice.issued_at) }}
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{
                                        invoice.due_at
                                            ? formatDate(invoice.due_at)
                                            : '—'
                                    }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-1.5">
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                :href="`/admin/invoices/${invoice.id}`"
                                            >
                                                <FileText class="size-3.5" />
                                                View
                                            </Link>
                                        </Button>
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <a
                                                :href="`/admin/invoices/${invoice.id}/download`"
                                            >
                                                <Download class="size-3.5" />
                                                PDF
                                            </a>
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
            v-if="invoices.last_page > 1"
            class="flex items-center justify-between gap-4 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ invoices.from }}–{{ invoices.to }} of
                {{ invoices.total }}
            </p>
            <div class="flex gap-2">
                <template v-for="link in invoices.links" :key="link.label">
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
                    <Button v-else size="sm" variant="outline" disabled>
                        {{ paginationLabel(link.label) }}
                    </Button>
                </template>
            </div>
        </div>
    </div>
</template>
