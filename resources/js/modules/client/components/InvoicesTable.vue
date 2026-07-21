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
        paid: 'bg-[#eff9ef] text-[#357e37] dark:bg-[#4fb250]/10 dark:text-[#84d780]',
        partially_refunded:
            'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
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
    <div class="overflow-x-auto rounded-2xl border bg-card shadow-sm">
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
                            class="hover:text-[#4fb250] hover:underline"
                        >
                            {{ invoice.invoice_number }}
                        </Link>
                    </td>
                    <td class="px-5 py-4 font-medium">
                        <Link
                            v-if="invoice.product"
                            :href="invoice.product.url"
                            class="hover:text-[#4fb250]"
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
                            class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition hover:border-[#4fb250] hover:text-[#4fb250]"
                        >
                            <Download class="size-3.5" /> Download
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
