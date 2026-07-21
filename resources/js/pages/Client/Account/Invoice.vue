<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download, RotateCcw, XCircle } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface InvoiceDetail {
    id: number;
    invoice_number: string;
    status: string;
    issued_at: string;
    due_at: string | null;
    notes: string | null;
    currency: string;
    subtotal: string;
    amount: string;
    discount_amount: string;
    setup_fee: string;
    tax_amount: string;
    tax_name: string | null;
    tax_rate: string | null;
    promotion_code: string | null;
    total: string;
    order_number: string;
    billing_cycle: string;
    product: { name: string; slug: string } | null;
    license_key: string | null;
    credit_notes: {
        id: number;
        credit_note_number: string;
        total_amount: string;
        issued_at: string;
        refund_status: string;
    }[];
    refund_requests: {
        id: number;
        request_number: string;
        amount: string;
        status: string;
        reason: string;
        admin_note: string | null;
        submitted_at: string;
        decided_at: string | null;
        credit_note_id: number | null;
    }[];
}

const props = defineProps<{
    invoice: InvoiceDetail;
    billTo: {
        name: string;
        email: string;
        address: string[];
    };
    refundPolicy: {
        can_request: boolean;
        reason: string | null;
        refundable_amount: string;
        window_days: number;
        deadline: string | null;
    };
}>();

const refundForm = useForm({
    amount: props.refundPolicy.refundable_amount,
    reason: '',
    idempotency_key: crypto.randomUUID(),
});
const refundRequestError = computed(() => {
    const errors = refundForm.errors as Record<string, string | undefined>;

    return errors.refund_request;
});
const submitRefundRequest = () =>
    refundForm.post(
        `/client-area/invoice/${props.invoice.id}/refund-requests`,
        {
            preserveScroll: true,
            onSuccess: () => {
                refundForm.reason = '';
                refundForm.idempotency_key = crypto.randomUUID();
            },
        },
    );
const cancelRefundRequest = (id: number) => {
    if (confirm('Cancel this pending refund request?')) {
        router.delete(`/client-area/refund-requests/${id}`, {
            preserveScroll: true,
        });
    }
};

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

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const money = (amount: string) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.invoice.currency,
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
    <SeoHead
        :title="`Invoice ${props.invoice.invoice_number}`"
        description="Invoice details."
    />

    <ClientAreaHero :title="`Invoice ${props.invoice.invoice_number}`" />

    <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Link
                href="/client-area/invoices"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Back to invoices
            </Link>
            <a
                :href="`/client-area/invoice/${props.invoice.id}/download`"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4fb250] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#3f9f40]"
            >
                <Download class="size-4" /> Download PDF
            </a>
        </div>

        <div class="mt-6 rounded-2xl border bg-card shadow-sm">
            <div
                class="flex flex-wrap items-start justify-between gap-4 border-b p-6"
            >
                <div>
                    <p class="font-mono text-lg font-bold">
                        {{ props.invoice.invoice_number }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Issued {{ formatDate(props.invoice.issued_at) }}
                        <template v-if="props.invoice.due_at">
                            · Due {{ formatDate(props.invoice.due_at) }}
                        </template>
                    </p>
                </div>
                <span
                    class="rounded-full px-3 py-1 text-sm font-bold"
                    :class="statusClass(props.invoice.status)"
                >
                    {{ statusLabel(props.invoice.status) }}
                </span>
            </div>

            <div class="grid gap-8 p-6 sm:grid-cols-2">
                <div>
                    <p
                        class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        Invoiced To
                    </p>
                    <p class="mt-2 font-semibold">{{ props.billTo.name }}</p>
                    <p
                        v-for="line in props.billTo.address"
                        :key="line"
                        class="text-sm text-muted-foreground"
                    >
                        {{ line }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ props.billTo.email }}
                    </p>
                </div>
                <div class="sm:text-right">
                    <p
                        class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        Order
                    </p>
                    <p class="mt-2 font-mono text-sm font-semibold">
                        {{ props.invoice.order_number }}
                    </p>
                    <p
                        v-if="props.invoice.license_key"
                        class="mt-1 font-mono text-xs text-muted-foreground"
                    >
                        {{ props.invoice.license_key }}
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto border-t">
                <table class="w-full min-w-[480px] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b text-xs font-bold tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-6 py-3.5">Description</th>
                            <th class="px-6 py-3.5 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b">
                            <td class="px-6 py-4">
                                <p class="font-medium">
                                    {{
                                        props.invoice.product?.name ?? 'Product'
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ label(props.invoice.billing_cycle) }}
                                    license
                                </p>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold">
                                {{ money(props.invoice.subtotal) }}
                            </td>
                        </tr>
                        <tr
                            v-if="Number(props.invoice.discount_amount) > 0"
                            class="border-b text-emerald-600"
                        >
                            <td class="px-6 py-4 font-medium">
                                Promotion
                                <span v-if="props.invoice.promotion_code">
                                    ({{ props.invoice.promotion_code }})
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold">
                                -{{ money(props.invoice.discount_amount) }}
                            </td>
                        </tr>
                        <tr
                            v-if="Number(props.invoice.setup_fee) > 0"
                            class="border-b"
                        >
                            <td class="px-6 py-4 font-medium">Setup Fee</td>
                            <td class="px-6 py-4 text-right font-semibold">
                                {{ money(props.invoice.setup_fee) }}
                            </td>
                        </tr>
                        <tr
                            v-if="Number(props.invoice.tax_amount) > 0"
                            class="border-b"
                        >
                            <td class="px-6 py-4 font-medium">
                                {{ props.invoice.tax_name || 'Tax' }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold">
                                {{ money(props.invoice.tax_amount) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-right font-bold">
                                Total
                            </td>
                            <td
                                class="px-6 py-4 text-right text-lg font-bold"
                                :class="
                                    props.invoice.status === 'issued'
                                        ? 'text-red-500'
                                        : ''
                                "
                            >
                                {{ money(props.invoice.total) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.invoice.notes" class="border-t p-6">
                <p
                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    Notes
                </p>
                <p class="mt-2 text-sm whitespace-pre-wrap">
                    {{ props.invoice.notes }}
                </p>
            </div>
            <div v-if="props.invoice.credit_notes.length" class="border-t p-6">
                <p
                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    Credit notes
                </p>
                <div class="mt-3 space-y-2">
                    <Link
                        v-for="credit in props.invoice.credit_notes"
                        :key="credit.id"
                        :href="`/client-area/credit-notes/${credit.id}`"
                        class="flex items-center justify-between rounded-lg border px-4 py-3 text-sm transition hover:border-[#4fb250]"
                    >
                        <span class="font-mono font-semibold">{{
                            credit.credit_note_number
                        }}</span>
                        <span class="font-bold text-emerald-600"
                            >-{{ money(credit.total_amount) }}</span
                        >
                    </Link>
                </div>
            </div>
        </div>

        <div
            v-if="
                props.invoice.refund_requests.length ||
                props.refundPolicy.can_request ||
                props.refundPolicy.reason
            "
            class="mt-6 grid gap-6 lg:grid-cols-2"
        >
            <div
                v-if="props.refundPolicy.can_request"
                class="rounded-2xl border bg-card p-6 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <span class="rounded-xl bg-[#4fb250]/10 p-2 text-[#357e37]">
                        <RotateCcw class="size-5" />
                    </span>
                    <div>
                        <h2 class="font-bold">Request a refund</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Submit within
                            {{ props.refundPolicy.window_days }} days of
                            payment. A billing administrator reviews every
                            request.
                        </p>
                    </div>
                </div>
                <form
                    class="mt-5 space-y-4"
                    @submit.prevent="submitRefundRequest"
                >
                    <div>
                        <label class="text-sm font-semibold">Amount</label>
                        <input
                            v-model="refundForm.amount"
                            type="number"
                            min="0.01"
                            :max="props.refundPolicy.refundable_amount"
                            step="0.01"
                            required
                            class="mt-1.5 h-10 w-full rounded-lg border bg-transparent px-3 text-sm"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Up to
                            {{ money(props.refundPolicy.refundable_amount) }}
                            refundable.
                        </p>
                        <InputError :message="refundForm.errors.amount" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">Reason</label>
                        <textarea
                            v-model="refundForm.reason"
                            rows="4"
                            minlength="10"
                            maxlength="2000"
                            required
                            class="mt-1.5 w-full rounded-lg border bg-transparent px-3 py-2 text-sm"
                            placeholder="Tell our billing team why you are requesting a refund."
                        />
                        <InputError :message="refundForm.errors.reason" />
                        <InputError :message="refundRequestError" />
                    </div>
                    <button
                        type="submit"
                        :disabled="refundForm.processing"
                        class="inline-flex h-10 items-center gap-2 rounded-lg bg-[#4fb250] px-5 text-sm font-bold text-white transition hover:bg-[#3f9f40] disabled:opacity-50"
                    >
                        <RotateCcw class="size-4" />
                        {{
                            refundForm.processing
                                ? 'Submitting…'
                                : 'Submit refund request'
                        }}
                    </button>
                </form>
            </div>

            <div
                v-else-if="props.refundPolicy.reason"
                class="rounded-2xl border bg-muted/30 p-6"
            >
                <h2 class="font-bold">Refund request</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ props.refundPolicy.reason }}
                </p>
            </div>

            <div
                v-if="props.invoice.refund_requests.length"
                class="rounded-2xl border bg-card p-6 shadow-sm"
            >
                <h2 class="font-bold">Request history</h2>
                <div class="mt-4 space-y-3">
                    <div
                        v-for="request in props.invoice.refund_requests"
                        :key="request.id"
                        class="rounded-xl border p-4"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <div>
                                <p class="font-mono text-sm font-bold">
                                    {{ request.request_number }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(request.submitted_at) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold">
                                    {{ money(request.amount) }}
                                </p>
                                <span
                                    class="text-xs font-bold"
                                    :class="
                                        request.status === 'approved'
                                            ? 'text-emerald-600'
                                            : request.status === 'rejected'
                                              ? 'text-red-600'
                                              : 'text-amber-600'
                                    "
                                    >{{ label(request.status) }}</span
                                >
                            </div>
                        </div>
                        <p class="mt-3 text-sm">{{ request.reason }}</p>
                        <p
                            v-if="request.admin_note"
                            class="mt-3 rounded-lg bg-muted p-3 text-sm"
                        >
                            <strong>Billing team:</strong>
                            {{ request.admin_note }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <button
                                v-if="request.status === 'pending'"
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:underline"
                                @click="cancelRefundRequest(request.id)"
                            >
                                <XCircle class="size-3.5" /> Cancel request
                            </button>
                            <Link
                                v-if="request.credit_note_id"
                                :href="`/client-area/credit-notes/${request.credit_note_id}`"
                                class="text-xs font-bold text-[#357e37] hover:underline"
                                >View credit note</Link
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p
            v-if="props.invoice.status === 'issued'"
            class="mt-4 text-center text-sm text-muted-foreground"
        >
            Online payment is coming soon — to settle this invoice, contact us
            or reply to the invoice email.
        </p>
    </section>
</template>
