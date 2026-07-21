<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, BadgeCheck, Ban, Download, Mail, Undo2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
    invoice: Record<string, any>;
    transactions: Record<string, any>[];
    paymentMethods: Record<string, any>[];
    refundSettings: {
        refundable_amount: string;
        automatic: boolean;
    };
}>();

const page = usePage();
const actionError = computed(
    () => (page.props.errors as Record<string, string>)?.invoice,
);
const refundError = computed(
    () =>
        (page.props.errors as Record<string, string>)?.refund ??
        (page.props.errors as Record<string, string>)?.record_only,
);

const order = computed(() => props.invoice.order);
const total = computed(
    () =>
        Number(order.value.amount) +
        Number(order.value.setup_fee) +
        Number(order.value.tax_amount || 0),
);
const balance = computed(() =>
    ['paid', 'partially_refunded', 'refunded'].includes(props.invoice.status)
        ? 0
        : total.value,
);

const tabs = [
    { value: 'summary', label: 'Summary' },
    { value: 'payment', label: 'Add Payment' },
    { value: 'refund', label: 'Refund' },
    { value: 'notes', label: 'Notes' },
];
const activeTab = ref('summary');

const paymentForm = useForm({
    gateway: 'manual',
    reference: '',
});

const submitPayment = () =>
    paymentForm.post(`/admin/invoices/${props.invoice.id}/add-payment`, {
        preserveScroll: true,
        onSuccess: () => (activeTab.value = 'summary'),
    });

const notesForm = useForm({
    notes: props.invoice.notes ?? '',
});

const refundForm = useForm({
    amount: props.refundSettings.refundable_amount,
    reason: '',
    idempotency_key: crypto.randomUUID(),
    record_only: !props.refundSettings.automatic,
    revoke_access: false,
});

const submitRefund = () => {
    if (
        !confirm(
            `Refund ${money(refundForm.amount)} for invoice ${props.invoice.invoice_number}?`,
        )
    ) {
        return;
    }

    refundForm.post(`/admin/invoices/${props.invoice.id}/refund`, {
        preserveScroll: true,
        onSuccess: () => {
            refundForm.idempotency_key = crypto.randomUUID();
            refundForm.reason = '';
            refundForm.amount = props.refundSettings.refundable_amount;
        },
    });
};

const submitNotes = () =>
    notesForm.patch(`/admin/invoices/${props.invoice.id}/notes`, {
        preserveScroll: true,
    });

const acting = ref(false);

const post = (action: string, confirmText?: string) => {
    if (confirmText && !confirm(confirmText)) {
        return;
    }

    router.post(
        `/admin/invoices/${props.invoice.id}/${action}`,
        {},
        {
            preserveScroll: true,
            onStart: () => (acting.value = true),
            onFinish: () => (acting.value = false),
        },
    );
};

const money = (amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: order.value.currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(date));

const formatDateTime = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const statusColor = computed(
    () =>
        ({
            paid: 'text-emerald-600',
            partially_refunded: 'text-blue-600',
            issued: 'text-red-600',
            refunded: 'text-red-600',
            void: 'text-muted-foreground',
        })[props.invoice.status as string] ?? 'text-muted-foreground',
);

const statusText = computed(
    () =>
        ({
            issued: 'UNPAID',
            paid: 'PAID',
            partially_refunded: 'PARTIALLY REFUNDED',
            refunded: 'REFUNDED',
            void: 'VOID',
        })[props.invoice.status as string] ?? props.invoice.status,
);
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link
                    :href="`/admin/users/${order.user.id}/invoices`"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-primary"
                >
                    <ArrowLeft class="size-3.5" /> Back to
                    {{ order.user.name }}'s invoices
                </Link>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Invoice
                    <span class="font-mono">{{ invoice.invoice_number }}</span>
                </h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button
                    variant="outline"
                    :disabled="acting"
                    @click="post('send')"
                >
                    <Mail class="size-4" /> Send email
                </Button>
                <Button as-child variant="outline">
                    <a :href="`/admin/invoices/${invoice.id}/download`">
                        <Download class="size-4" /> Download
                    </a>
                </Button>
                <Button
                    v-if="invoice.status === 'issued'"
                    variant="destructive"
                    :disabled="acting"
                    @click="
                        post('void', `Void invoice ${invoice.invoice_number}?`)
                    "
                >
                    <Ban class="size-4" /> Void
                </Button>
            </div>
        </div>

        <InputError :message="actionError" />

        <div class="border-b">
            <nav class="-mb-px flex flex-wrap gap-1" role="tablist">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.value"
                    class="border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors"
                    :class="
                        activeTab === tab.value
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = tab.value"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <!-- Summary tab -->
        <div v-show="activeTab === 'summary'" class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardContent class="p-0">
                    <dl class="divide-y text-sm">
                        <div
                            class="grid grid-cols-[140px_minmax(0,1fr)] gap-4 px-5 py-3"
                        >
                            <dt class="text-muted-foreground">Client name</dt>
                            <dd class="font-semibold">
                                {{ order.user.name }}
                                <Link
                                    :href="`/admin/users/${order.user.id}/invoices`"
                                    class="ml-1 font-normal text-primary hover:underline"
                                >
                                    ( View invoices )
                                </Link>
                            </dd>
                        </div>
                        <div
                            class="grid grid-cols-[140px_minmax(0,1fr)] gap-4 px-5 py-3"
                        >
                            <dt class="text-muted-foreground">Invoice date</dt>
                            <dd class="font-semibold">
                                {{ formatDate(invoice.issued_at) }}
                            </dd>
                        </div>
                        <div
                            class="grid grid-cols-[140px_minmax(0,1fr)] gap-4 px-5 py-3"
                        >
                            <dt class="text-muted-foreground">Due date</dt>
                            <dd class="font-semibold">
                                {{
                                    invoice.due_at
                                        ? formatDate(invoice.due_at)
                                        : '—'
                                }}
                            </dd>
                        </div>
                        <div
                            class="grid grid-cols-[140px_minmax(0,1fr)] gap-4 px-5 py-3"
                        >
                            <dt class="text-muted-foreground">
                                Invoice amount
                            </dt>
                            <dd class="font-semibold">{{ money(total) }}</dd>
                        </div>
                        <div
                            v-if="Number(order.discount_amount) > 0"
                            class="grid grid-cols-[140px_minmax(0,1fr)] gap-4 px-5 py-3"
                        >
                            <dt class="text-muted-foreground">
                                Promotion {{ order.promotion_code }}
                            </dt>
                            <dd class="font-semibold text-emerald-600">
                                -{{ money(order.discount_amount) }}
                            </dd>
                        </div>
                        <div
                            v-if="Number(order.tax_amount) > 0"
                            class="grid grid-cols-[140px_minmax(0,1fr)] gap-4 px-5 py-3"
                        >
                            <dt class="text-muted-foreground">
                                {{ order.tax_name || 'Tax' }}
                            </dt>
                            <dd class="font-semibold">
                                {{ money(order.tax_amount) }}
                            </dd>
                        </div>
                        <div
                            class="grid grid-cols-[140px_minmax(0,1fr)] gap-4 px-5 py-3"
                        >
                            <dt class="text-muted-foreground">Balance</dt>
                            <dd
                                class="font-bold"
                                :class="
                                    balance > 0
                                        ? 'text-red-600'
                                        : 'text-emerald-600'
                                "
                            >
                                {{ money(balance) }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="flex flex-col items-center gap-4 p-6">
                    <p
                        class="text-2xl font-extrabold tracking-wide"
                        :class="statusColor"
                    >
                        {{ statusText }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Payment method:
                        <strong>{{
                            order.payment_method
                                ? label(order.payment_method)
                                : 'Not set'
                        }}</strong>
                        <template v-if="paymentMethods.length">
                            —
                            <span class="capitalize">
                                {{ paymentMethods[0].card_brand }}-{{
                                    paymentMethods[0].card_last_four
                                }}
                            </span>
                        </template>
                    </p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <Button
                            v-if="invoice.status === 'issued'"
                            :disabled="acting"
                            @click="activeTab = 'payment'"
                        >
                            <BadgeCheck class="size-4" /> Add payment
                        </Button>
                        <Button
                            v-if="
                                ['paid', 'partially_refunded'].includes(
                                    invoice.status,
                                )
                            "
                            variant="destructive"
                            :disabled="acting"
                            @click="activeTab = 'refund'"
                        >
                            <Undo2 class="size-4" /> Refund
                        </Button>
                        <Button
                            variant="outline"
                            :disabled="acting"
                            @click="post('send')"
                        >
                            <Mail class="size-4" /> Send email
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Add Payment tab -->
        <Card v-show="activeTab === 'payment'" class="max-w-xl">
            <CardHeader>
                <CardTitle>Add payment</CardTitle>
                <CardDescription>
                    Records a payment of {{ money(total) }}, marks the invoice
                    paid, and provisions the license.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-5" @submit.prevent="submitPayment">
                    <div class="space-y-2">
                        <Label>Payment method</Label>
                        <select
                            v-model="paymentForm.gateway"
                            class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option value="manual">Manual / Offline</option>
                            <option value="bank_transfer">Bank transfer</option>
                            <option value="bkash">bKash</option>
                            <option value="stripe">Stripe</option>
                            <option value="paypal">PayPal</option>
                        </select>
                        <InputError :message="paymentForm.errors.gateway" />
                    </div>
                    <div class="space-y-2">
                        <Label>Transaction ID / reference</Label>
                        <Input
                            v-model="paymentForm.reference"
                            placeholder="TXN123456 (optional)"
                        />
                        <InputError :message="paymentForm.errors.reference" />
                    </div>
                    <Button
                        type="submit"
                        :disabled="
                            paymentForm.processing ||
                            invoice.status !== 'issued'
                        "
                    >
                        <BadgeCheck class="size-4" />
                        {{
                            paymentForm.processing
                                ? 'Recording…'
                                : `Record payment of ${money(total)}`
                        }}
                    </Button>
                </form>
            </CardContent>
        </Card>

        <!-- Refund tab -->
        <Card v-show="activeTab === 'refund'" class="max-w-xl">
            <CardHeader>
                <CardTitle>Refund</CardTitle>
                <CardDescription>
                    Issue a partial or full refund. Accepted refunds create a
                    numbered credit note with a proportional tax adjustment.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    v-if="Number(refundSettings.refundable_amount) > 0"
                    class="space-y-5"
                    @submit.prevent="submitRefund"
                >
                    <InputError :message="refundError" />
                    <div class="space-y-2">
                        <Label>Refund amount</Label>
                        <Input
                            v-model="refundForm.amount"
                            type="number"
                            min="0.01"
                            :max="refundSettings.refundable_amount"
                            step="0.01"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            Refundable balance:
                            {{ money(refundSettings.refundable_amount) }}
                        </p>
                        <InputError :message="refundForm.errors.amount" />
                    </div>
                    <div class="space-y-2">
                        <Label>Reason</Label>
                        <textarea
                            v-model="refundForm.reason"
                            rows="3"
                            required
                            maxlength="2000"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                            placeholder="Why is this refund being issued?"
                        />
                        <InputError :message="refundForm.errors.reason" />
                    </div>
                    <label
                        v-if="!refundSettings.automatic"
                        class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                    >
                        <input
                            v-model="refundForm.record_only"
                            type="checkbox"
                            class="mt-0.5 size-4"
                        />
                        <span
                            >I already refunded this payment externally. Record
                            the refund and issue its credit note.</span
                        >
                    </label>
                    <label class="flex items-start gap-2 text-sm">
                        <input
                            v-model="refundForm.revoke_access"
                            type="checkbox"
                            class="mt-0.5 size-4"
                        />
                        <span
                            >Terminate product licenses if this completes a full
                            refund.</span
                        >
                    </label>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="
                            refundForm.processing ||
                            (!refundSettings.automatic &&
                                !refundForm.record_only)
                        "
                    >
                        <Undo2 class="size-4" />
                        {{
                            refundForm.processing
                                ? 'Processing…'
                                : `Refund ${money(refundForm.amount)}`
                        }}
                    </Button>
                </form>
                <p v-else class="text-sm text-muted-foreground">
                    This invoice has no refundable balance.
                </p>

                <div v-if="invoice.refunds?.length" class="mt-6 border-t pt-5">
                    <h3 class="text-sm font-semibold">Refund history</h3>
                    <div class="mt-3 space-y-3">
                        <div
                            v-for="refund in invoice.refunds"
                            :key="refund.id"
                            class="rounded-md border p-3 text-sm"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="font-mono font-semibold">{{
                                    refund.refund_number
                                }}</span>
                                <span class="font-bold">{{
                                    money(refund.amount)
                                }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ label(refund.status) }} ·
                                {{ refund.reason }}
                            </p>
                            <a
                                v-if="refund.credit_note"
                                :href="`/admin/credit-notes/${refund.credit_note.id}/download`"
                                class="mt-2 inline-flex text-xs font-semibold text-primary hover:underline"
                            >
                                Download
                                {{ refund.credit_note.credit_note_number }}
                            </a>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Notes tab -->
        <Card v-show="activeTab === 'notes'" class="max-w-xl">
            <CardHeader>
                <CardTitle>Notes</CardTitle>
                <CardDescription>
                    Shown on the printed and emailed invoice.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-3" @submit.prevent="submitNotes">
                    <textarea
                        v-model="notesForm.notes"
                        rows="4"
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        placeholder="Notes for this invoice…"
                    />
                    <InputError :message="notesForm.errors.notes" />
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="notesForm.processing"
                    >
                        {{ notesForm.processing ? 'Saving…' : 'Save notes' }}
                    </Button>
                </form>
            </CardContent>
        </Card>

        <!-- Invoice items -->
        <div>
            <h2 class="mb-3 text-lg font-semibold">Invoice items</h2>
            <Card>
                <CardContent class="p-0">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3">Description</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b">
                                <td class="px-5 py-4">
                                    <p class="font-semibold">
                                        {{ order.product.name }}
                                        <span
                                            class="font-normal text-muted-foreground"
                                        >
                                            ({{
                                                order.product_price?.name ||
                                                label(order.billing_cycle)
                                            }})
                                        </span>
                                    </p>
                                    <p
                                        class="mt-1 font-mono text-xs text-muted-foreground"
                                    >
                                        Order {{ order.order_number }}
                                    </p>
                                    <p
                                        v-if="order.license"
                                        class="mt-0.5 font-mono text-xs text-muted-foreground"
                                    >
                                        License:
                                        {{ order.license.license_key }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-right font-semibold">
                                    {{ money(order.amount) }}
                                </td>
                            </tr>
                            <tr
                                v-if="Number(order.setup_fee) > 0"
                                class="border-b"
                            >
                                <td class="px-5 py-4">Setup fee</td>
                                <td class="px-5 py-4 text-right font-semibold">
                                    {{ money(order.setup_fee) }}
                                </td>
                            </tr>
                            <tr class="bg-muted/40">
                                <td
                                    class="px-5 py-3 text-right text-xs font-bold tracking-wide text-muted-foreground uppercase"
                                >
                                    Sub total
                                </td>
                                <td class="px-5 py-3 text-right font-semibold">
                                    {{ money(total) }}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="px-5 py-3 text-right text-xs font-bold tracking-wide uppercase"
                                >
                                    Invoice amount
                                </td>
                                <td
                                    class="px-5 py-3 text-right text-base font-bold"
                                >
                                    {{ money(total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>

        <!-- Ledger -->
        <div>
            <h2 class="mb-3 text-lg font-semibold">Ledger</h2>
            <Card>
                <CardContent class="p-0">
                    <div
                        v-if="transactions.length === 0"
                        class="p-6 text-sm text-muted-foreground"
                    >
                        No records found.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[820px] text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    <th class="px-5 py-3">Date</th>
                                    <th class="px-5 py-3">Type</th>
                                    <th class="px-5 py-3">Description</th>
                                    <th class="px-5 py-3">Payment method</th>
                                    <th class="px-5 py-3">Reference</th>
                                    <th class="px-5 py-3 text-right">Amount</th>
                                    <th class="px-5 py-3 text-right">Fees</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="transaction in transactions"
                                    :key="transaction.id"
                                    class="border-b last:border-b-0"
                                >
                                    <td class="px-5 py-3 text-muted-foreground">
                                        {{
                                            formatDateTime(
                                                transaction.created_at,
                                            )
                                        }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-bold"
                                            :class="
                                                transaction.type === 'payment'
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300'
                                            "
                                        >
                                            {{ label(transaction.type) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        {{ transaction.description ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        {{ label(transaction.gateway) }}
                                    </td>
                                    <td
                                        class="px-5 py-3 font-mono text-xs text-muted-foreground"
                                    >
                                        {{ transaction.reference ?? '—' }}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right font-semibold"
                                    >
                                        {{ money(transaction.amount) }}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right text-muted-foreground"
                                    >
                                        {{ money(transaction.fees) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Transaction history -->
        <div>
            <h2 class="mb-3 text-lg font-semibold">Transaction history</h2>
            <Card>
                <CardContent class="p-0">
                    <div
                        v-if="transactions.length === 0"
                        class="p-6 text-sm text-muted-foreground"
                    >
                        No records found.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    <th class="px-5 py-3">Date</th>
                                    <th class="px-5 py-3">Payment method</th>
                                    <th class="px-5 py-3">Transaction ID</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="transaction in transactions"
                                    :key="transaction.id"
                                    class="border-b last:border-b-0"
                                >
                                    <td class="px-5 py-3 text-muted-foreground">
                                        {{
                                            formatDateTime(
                                                transaction.created_at,
                                            )
                                        }}
                                    </td>
                                    <td class="px-5 py-3">
                                        {{ label(transaction.gateway) }}
                                    </td>
                                    <td
                                        class="px-5 py-3 font-mono text-xs text-muted-foreground"
                                    >
                                        {{ transaction.reference ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-bold"
                                            :class="
                                                transaction.type === 'payment'
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300'
                                            "
                                        >
                                            {{
                                                transaction.type === 'payment'
                                                    ? 'Completed'
                                                    : 'Refunded'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        {{ transaction.description ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
