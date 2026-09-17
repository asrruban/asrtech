<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';

export interface InvoiceItem {
    id: number;
    invoice_number: string;
    status: string;
    issued_at: string | null;
    due_at: string | null;
    currency: string;
    total: string;
    order_number: string;
    product: { name: string; slug: string; url: string } | null;
}

defineProps<{
    invoices: InvoiceItem[];
}>();

const statusLabel = (status: string) =>
    ({
        issued: 'Unpaid',
        paid: 'Paid',
        partially_refunded: 'Partially refunded',
        refunded: 'Refunded',
        void: 'Void',
    })[status] ?? status;

const statusClass = (status: string) =>
    ({
        issued: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        paid: 'bg-[var(--client-accent-soft)] text-[var(--client-accent-dark)] dark:bg-[#087f75]/10 dark:text-[#84d780]',
        partially_refunded:
            'bg-blue-50 text-[var(--client-accent-dark)] dark:bg-[#087f75]/10 dark:text-emerald-200',
        refunded: 'bg-slate-100 text-slate-600 dark:bg-white/10',
        void: 'bg-slate-100 text-slate-600 dark:bg-white/10',
    })[status] ?? 'bg-slate-100 text-slate-600';

const money = (currency: string, amount: string) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const formatDate = (date: string | null) =>
    date
        ? new Intl.DateTimeFormat('en', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(new Date(date))
        : '—';
</script>

<template>
    <!-- Mobile: stacked cards -->
    <div class="space-y-3 md:hidden">
        <div
            v-for="invoice in invoices"
            :key="invoice.id"
            class="rounded-2xl border bg-card p-4 shadow-sm"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <Link
                        :href="`/client-area/invoice/${invoice.id}`"
                        class="font-mono text-sm font-bold hover:text-[var(--client-accent)] hover:underline"
                    >
                        {{ invoice.invoice_number }}
                    </Link>
                    <p class="mt-1 truncate text-xs text-muted-foreground">
                        <Link
                            v-if="invoice.product"
                            :href="invoice.product.url"
                            class="hover:text-[var(--client-accent)]"
                        >
                            {{ invoice.product.name }}
                        </Link>
                        <template v-else>—</template>
                    </p>
                </div>
                <span
                    class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold"
                    :class="statusClass(invoice.status)"
                >
                    {{ statusLabel(invoice.status) }}
                </span>
            </div>
            <div class="mt-3 flex items-end justify-between gap-3">
                <div class="text-xs text-muted-foreground">
                    <p>Issued {{ formatDate(invoice.issued_at) }}</p>
                    <p>Due {{ formatDate(invoice.due_at) }}</p>
                </div>
                <p class="text-base font-extrabold">
                    {{ money(invoice.currency, invoice.total) }}
                </p>
            </div>
            <div class="mt-3 flex gap-2 border-t pt-3">
                <Link
                    :href="`/client-area/invoice/${invoice.id}`"
                    class="inline-flex h-10 flex-1 items-center justify-center rounded-lg border text-sm font-semibold transition hover:border-[#087f75] hover:text-[var(--client-accent)]"
                >
                    View
                </Link>
                <a
                    :href="`/client-area/invoice/${invoice.id}/download`"
                    class="inline-flex h-10 flex-1 items-center justify-center gap-1.5 rounded-lg border text-sm font-semibold transition hover:border-[#087f75] hover:text-[var(--client-accent)]"
                >
                    <Download class="size-4" /> PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Desktop: table -->
    <div
        class="hidden overflow-x-auto rounded-2xl border bg-card shadow-sm md:block"
    >
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead>
                <tr
                    class="border-b text-xs font-bold tracking-wide text-muted-foreground uppercase"
                >
                    <th class="px-5 py-3.5">Invoice</th>
                    <th class="px-5 py-3.5">Product</th>
                    <th class="px-5 py-3.5">Total</th>
                    <th class="px-5 py-3.5">Issued</th>
                    <th class="px-5 py-3.5">Due</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5 text-right">PDF</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="invoice in invoices"
                    :key="invoice.id"
                    class="border-b last:border-b-0"
                >
                    <td class="px-5 py-4 font-mono text-xs font-semibold">
                        <Link
                            :href="`/client-area/invoice/${invoice.id}`"
                            class="hover:text-[var(--client-accent)] hover:underline"
                        >
                            {{ invoice.invoice_number }}
                        </Link>
                    </td>
                    <td class="px-5 py-4 font-medium">
                        <Link
                            v-if="invoice.product"
                            :href="invoice.product.url"
                            class="hover:text-[var(--client-accent)]"
                        >
                            {{ invoice.product.name }}
                        </Link>
                        <template v-else>—</template>
                    </td>
                    <td class="px-5 py-4 font-semibold">
                        {{ money(invoice.currency, invoice.total) }}
                    </td>
                    <td class="px-5 py-4 text-muted-foreground">
                        {{ formatDate(invoice.issued_at) }}
                    </td>
                    <td class="px-5 py-4 text-muted-foreground">
                        {{ formatDate(invoice.due_at) }}
                    </td>
                    <td class="px-5 py-4">
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-bold"
                            :class="statusClass(invoice.status)"
                        >
                            {{ statusLabel(invoice.status) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <a
                            :href="`/client-area/invoice/${invoice.id}/download`"
                            class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition hover:border-[#087f75] hover:text-[var(--client-accent)]"
                        >
                            <Download class="size-3.5" /> Download
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
