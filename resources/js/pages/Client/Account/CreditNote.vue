<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Download, ReceiptText } from '@lucide/vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface CreditNote {
    id: number;
    credit_note_number: string;
    currency: string;
    net_amount: string;
    tax_amount: string;
    total_amount: string;
    tax_name?: string | null;
    reason: string;
    issued_at: string;
    refund: { refund_number: string; status: string; gateway: string };
    invoice: {
        id: number;
        invoice_number: string;
        order: { order_number: string; product: { name: string } };
    };
}

const props = defineProps<{ creditNote: CreditNote }>();
const money = (amount: string) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.creditNote.currency,
    }).format(Number(amount));
const date = (value: string) =>
    new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(
        new Date(value),
    );
</script>

<template>
    <SeoHead
        :title="`Credit note ${creditNote.credit_note_number}`"
        description="Refund credit note details."
    />
    <ClientAreaHero :title="`Credit note ${creditNote.credit_note_number}`" />
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link
                :href="`/client-area/invoice/${creditNote.invoice.id}`"
                class="inline-flex items-center gap-2 text-sm font-semibold text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Original invoice
            </Link>
            <a
                :href="`/client-area/credit-notes/${creditNote.id}/download`"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4fb250] px-4 py-2 text-sm font-bold text-white"
            >
                <Download class="size-4" /> Download PDF
            </a>
        </div>

        <article
            class="mt-6 overflow-hidden rounded-2xl border bg-card shadow-sm"
        >
            <header class="flex flex-wrap justify-between gap-4 border-b p-6">
                <div>
                    <div class="flex items-center gap-2">
                        <ReceiptText class="size-5 text-[#4fb250]" />
                        <h2 class="font-mono text-lg font-bold">
                            {{ creditNote.credit_note_number }}
                        </h2>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Issued {{ date(creditNote.issued_at) }} for
                        {{ creditNote.invoice.invoice_number }}
                    </p>
                </div>
                <span
                    class="h-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700"
                >
                    {{ creditNote.refund.status.replaceAll('_', ' ') }}
                </span>
            </header>
            <div class="p-6">
                <p class="font-semibold">
                    {{ creditNote.invoice.order.product.name }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Refund {{ creditNote.refund.refund_number }} ·
                    {{ creditNote.invoice.order.order_number }}
                </p>
                <dl class="mt-6 ml-auto max-w-sm space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt>Net credit</dt>
                        <dd class="font-semibold">
                            {{ money(creditNote.net_amount) }}
                        </dd>
                    </div>
                    <div
                        v-if="Number(creditNote.tax_amount) > 0"
                        class="flex justify-between"
                    >
                        <dt>{{ creditNote.tax_name || 'Tax adjustment' }}</dt>
                        <dd class="font-semibold">
                            {{ money(creditNote.tax_amount) }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t pt-3 text-lg font-bold"
                    >
                        <dt>Total credit</dt>
                        <dd>{{ money(creditNote.total_amount) }}</dd>
                    </div>
                </dl>
                <div class="mt-6 rounded-xl bg-muted/50 p-4 text-sm">
                    <strong>Reason:</strong> {{ creditNote.reason }}
                </div>
            </div>
        </article>
    </section>
</template>
