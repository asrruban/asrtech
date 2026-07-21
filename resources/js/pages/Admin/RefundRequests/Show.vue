<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, ExternalLink, XCircle } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    refundRequest: Record<string, any>;
    refundableAmount: string;
    automatic: boolean;
}>();
const order = computed(() => props.refundRequest.invoice.order);
const approveForm = useForm({
    record_only: !props.automatic,
    revoke_access: false,
    admin_note: '',
});
const rejectForm = useForm({ admin_note: '' });
const approveActionError = computed(() => {
    const errors = approveForm.errors as Record<string, string | undefined>;

    return errors.refund_request ?? errors.record_only ?? errors.refund;
});
const rejectActionError = computed(() => {
    const errors = rejectForm.errors as Record<string, string | undefined>;

    return errors.refund_request;
});
const approve = () => {
    if (
        confirm(
            `Approve ${props.refundRequest.request_number} and process the refund?`,
        )
    ) {
        approveForm.post(
            `/admin/refund-requests/${props.refundRequest.id}/approve`,
        );
    }
};
const reject = () => {
    if (confirm(`Reject ${props.refundRequest.request_number}?`)) {
        rejectForm.post(
            `/admin/refund-requests/${props.refundRequest.id}/reject`,
        );
    }
};
const money = (amount: string) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.refundRequest.currency,
    }).format(Number(amount));
const date = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '—';
const label = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1).replaceAll('_', ' ');
</script>

<template>
    <Head :title="`Refund request ${refundRequest.request_number}`" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <Link
                href="/admin/refund-requests"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary"
                ><ArrowLeft class="size-4" /> Back to refund requests</Link
            >
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-3xl font-semibold tracking-tight">
                    {{ refundRequest.request_number }}
                </h1>
                <span
                    class="rounded-full bg-muted px-3 py-1 text-xs font-bold"
                    >{{ label(refundRequest.status) }}</span
                >
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                Submitted {{ date(refundRequest.submitted_at) }}
            </p>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_1fr]">
            <div class="space-y-6">
                <Card
                    ><CardHeader
                        ><CardTitle>Customer request</CardTitle></CardHeader
                    ><CardContent class="space-y-5">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <p
                                    class="text-xs font-semibold text-muted-foreground uppercase"
                                >
                                    Customer
                                </p>
                                <Link
                                    :href="`/admin/users/${refundRequest.user.id}`"
                                    class="mt-1 block font-bold text-primary hover:underline"
                                    >{{ refundRequest.user.name }}</Link
                                >
                                <p class="text-xs text-muted-foreground">
                                    {{ refundRequest.user.email }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-semibold text-muted-foreground uppercase"
                                >
                                    Invoice
                                </p>
                                <Link
                                    :href="`/admin/invoices/${refundRequest.invoice.id}`"
                                    class="mt-1 inline-flex items-center gap-1 font-mono font-bold text-primary hover:underline"
                                    >{{ refundRequest.invoice.invoice_number }}
                                    <ExternalLink class="size-3"
                                /></Link>
                                <p class="text-xs text-muted-foreground">
                                    {{ order.product.name }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-semibold text-muted-foreground uppercase"
                                >
                                    Requested
                                </p>
                                <p class="mt-1 text-2xl font-bold">
                                    {{ money(refundRequest.amount) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ money(refundableAmount) }} currently
                                    refundable
                                </p>
                            </div>
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-4">
                            <p
                                class="text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Reason
                            </p>
                            <p class="mt-2 text-sm whitespace-pre-wrap">
                                {{ refundRequest.reason }}
                            </p>
                        </div>
                    </CardContent></Card
                >

                <Card v-if="refundRequest.status !== 'pending'"
                    ><CardHeader><CardTitle>Decision</CardTitle></CardHeader
                    ><CardContent>
                        <p class="font-semibold">
                            {{ label(refundRequest.status) }} by
                            {{ refundRequest.decided_by?.name ?? 'Customer' }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ date(refundRequest.decided_at) }}
                        </p>
                        <p
                            v-if="refundRequest.admin_note"
                            class="mt-4 rounded-lg border p-4 text-sm"
                        >
                            {{ refundRequest.admin_note }}
                        </p>
                        <Link
                            v-if="refundRequest.refund?.credit_note"
                            :href="`/admin/credit-notes/${refundRequest.refund.credit_note.id}/download`"
                            class="mt-4 inline-flex text-sm font-bold text-primary hover:underline"
                            >Download
                            {{
                                refundRequest.refund.credit_note
                                    .credit_note_number
                            }}</Link
                        >
                    </CardContent></Card
                >
            </div>

            <Card v-if="refundRequest.status === 'pending'"
                ><CardHeader><CardTitle>Billing decision</CardTitle></CardHeader
                ><CardContent class="space-y-6">
                    <form class="space-y-4" @submit.prevent="approve">
                        <textarea
                            v-model="approveForm.admin_note"
                            rows="3"
                            maxlength="2000"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                            placeholder="Approval note shown to customer (optional)"
                        />
                        <label
                            v-if="!automatic"
                            class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"
                            ><input
                                v-model="approveForm.record_only"
                                type="checkbox"
                                class="mt-0.5 size-4"
                            /><span
                                >I already sent this refund externally. Record
                                it and issue the credit note.</span
                            ></label
                        >
                        <label class="flex items-start gap-2 text-sm"
                            ><input
                                v-model="approveForm.revoke_access"
                                type="checkbox"
                                class="mt-0.5 size-4"
                            /><span
                                >Terminate licenses if this completes a full
                                refund.</span
                            ></label
                        >
                        <InputError :message="approveActionError" />
                        <Button
                            class="w-full"
                            :disabled="
                                approveForm.processing ||
                                (!automatic && !approveForm.record_only)
                            "
                            ><CheckCircle2 class="size-4" /> Approve and refund
                            {{ money(refundRequest.amount) }}</Button
                        >
                    </form>
                    <div class="border-t pt-5">
                        <form class="space-y-3" @submit.prevent="reject">
                            <textarea
                                v-model="rejectForm.admin_note"
                                rows="3"
                                required
                                minlength="3"
                                maxlength="2000"
                                class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                placeholder="Reason for rejection (shown to customer)"
                            />
                            <InputError
                                :message="
                                    rejectForm.errors.admin_note ||
                                    rejectActionError
                                "
                            />
                            <Button
                                type="submit"
                                variant="destructive"
                                class="w-full"
                                :disabled="rejectForm.processing"
                                ><XCircle class="size-4" /> Reject
                                request</Button
                            >
                        </form>
                    </div>
                </CardContent></Card
            >
        </div>
    </div>
</template>
