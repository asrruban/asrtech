<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import MaintenanceScope from '@/modules/client/components/MaintenanceScope.vue';
import type { MaintenanceRequest } from '@/types/maintenance';
const props = defineProps<{
    request: MaintenanceRequest;
    subscriptions: { id: number; label: string }[];
    quotes: { id: number; label: string }[];
    statuses: string[];
}>();
const page = usePage();
const canBill = computed(() =>
    ((page.props.adminPermissions ?? []) as string[]).some((p) =>
        ['*', 'billing.manage'].includes(p),
    ),
);
const form = useForm({
    status: props.request.status,
    client_update: props.request.client_update ?? '',
    internal_notes: props.request.internal_notes ?? '',
    subscription_id: props.request.subscription_id ?? null,
    quote_id: props.request.quote_id ?? null,
});
const save = () =>
    form.patch(`/admin/maintenance/requests/${props.request.id}`, {
        preserveScroll: true,
    });
</script>
<template>
    <Head :title="`Maintenance request #${request.id}`" />
    <div class="space-y-7 p-4 md:p-8">
        <Link
            href="/admin/maintenance/requests"
            class="text-sm font-semibold text-primary"
            >← All maintenance requests</Link
        >
        <div>
            <h1 class="text-3xl font-semibold">Request #{{ request.id }}</h1>
            <p class="mt-3 text-sm text-muted-foreground">
                {{ request.user?.name }} · {{ request.user?.email }}
            </p>
        </div>
        <div class="grid items-start gap-8 xl:grid-cols-2">
            <div class="space-y-6">
                <section class="rounded-2xl border bg-card p-6">
                    <h2 class="text-lg font-semibold">Customer requirements</h2>
                    <p class="mt-3 text-sm break-all">
                        {{ request.website || 'No website supplied' }}
                    </p>
                    <p
                        class="mt-4 text-sm leading-7 whitespace-pre-line text-muted-foreground"
                    >
                        {{ request.requirements }}
                    </p>
                    <p class="mt-4 text-xs text-muted-foreground">
                        Scope acknowledged
                        {{
                            new Date(
                                request.scope_acknowledged_at,
                            ).toLocaleString()
                        }}.
                    </p>
                </section>
                <section class="rounded-2xl border bg-card p-6">
                    <p class="mb-6 text-sm text-muted-foreground">
                        Saved scope at submission. Keep changes to any agreement
                        explicit with the customer.
                    </p>
                    <MaintenanceScope :plan="request.plan" />
                </section>
            </div>
            <form
                class="space-y-5 rounded-2xl border bg-card p-6"
                @submit.prevent="save"
            >
                <h2 class="text-xl font-semibold">Review and service status</h2>
                <div
                    v-if="Object.keys(form.errors).length"
                    role="alert"
                    class="space-y-1 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950/40 dark:text-red-200"
                >
                    <p v-for="(error, key) in form.errors" :key="key">
                        {{ error }}
                    </p>
                </div>
                <div>
                    <label for="maintenance-status" class="text-sm font-medium"
                        >Status</label
                    ><select
                        id="maintenance-status"
                        v-model="form.status"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                        :aria-invalid="Boolean(form.errors.status)"
                    >
                        <option
                            v-for="status in statuses"
                            :key="status"
                            :value="status"
                        >
                            {{ status.replaceAll('_', ' ') }}
                        </option>
                    </select>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Activation requires an eligible customer subscription or
                        an accepted, paid custom quote. Service status changes
                        do not cancel billing.
                    </p>
                </div>
                <div>
                    <label for="client-update" class="text-sm font-medium"
                        >Customer-visible update</label
                    ><textarea
                        id="client-update"
                        v-model="form.client_update"
                        rows="5"
                        maxlength="10000"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                    />
                    <p class="mt-1 text-xs text-muted-foreground">
                        Appears in the customer’s Client Area. This does not
                        send an email.
                    </p>
                </div>
                <div>
                    <label for="internal-notes" class="text-sm font-medium"
                        >Internal notes</label
                    ><textarea
                        id="internal-notes"
                        v-model="form.internal_notes"
                        rows="5"
                        maxlength="10000"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                    />
                </div>
                <div v-if="request.plan.billing">
                    <label
                        for="maintenance-subscription"
                        class="text-sm font-medium"
                        >Matching subscription</label
                    ><select
                        id="maintenance-subscription"
                        v-model="form.subscription_id"
                        :disabled="!canBill"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                    >
                        <option :value="null">Not yet linked</option>
                        <option
                            v-for="subscription in subscriptions"
                            :key="subscription.id"
                            :value="subscription.id"
                        >
                            {{ subscription.label }}
                        </option>
                    </select>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Set Awaiting payment to make the agreed checkout
                        available to the customer.
                    </p>
                </div>
                <div v-else>
                    <label for="maintenance-quote" class="text-sm font-medium"
                        >Customer quote</label
                    ><select
                        id="maintenance-quote"
                        v-model="form.quote_id"
                        :disabled="!canBill"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                    >
                        <option :value="null">Not yet linked</option>
                        <option
                            v-for="quote in quotes"
                            :key="quote.id"
                            :value="quote.id"
                        >
                            {{ quote.label }}
                        </option></select
                    ><Link
                        v-if="canBill"
                        href="/admin/quotes"
                        class="mt-3 inline-block text-sm text-primary underline"
                        >Create or manage quotes</Link
                    >
                    <p class="mt-2 text-xs text-muted-foreground">
                        Draft quotes are hidden from this customer request until
                        sent. Agree the maintenance scope in the quote before
                        requesting payment.
                    </p>
                </div>
                <p v-if="!canBill" class="text-xs text-muted-foreground">
                    A billing administrator must link subscriptions or quotes.
                </p>
                <button :disabled="form.processing" class="button-primary">
                    {{ form.processing ? 'Saving…' : 'Save review' }}
                </button>
            </form>
        </div>
    </div>
</template>
