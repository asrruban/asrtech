<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AccountNav from '@/modules/client/components/AccountNav.vue';
import MaintenanceScope from '@/modules/client/components/MaintenanceScope.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
import type { MaintenanceRequest } from '@/types/maintenance';
const props = defineProps<{ request: MaintenanceRequest }>();
const action = useForm({});
const page = usePage();
const errors = computed(() => [
    ...new Set(
        [
            ...Object.values(page.props.errors ?? {}),
            ...Object.values(action.errors),
        ].filter((value): value is string => typeof value === 'string'),
    ),
]);
const withdraw = () =>
    action.post(`/client-area/maintenance/${props.request.id}/withdraw`, {
        preserveScroll: true,
    });
const checkout = () => {
    if (props.request.checkout_url) {
        action.post(props.request.checkout_url);
    }
};
</script>
<template>
    <SeoHead
        :title="`Maintenance request #${request.id}`"
        description="Review your maintenance request and agreed scope."
    />
    <section class="site-container py-8">
        <AccountNav /><Link
            href="/client-area/maintenance"
            class="mt-8 inline-block text-sm font-semibold text-[var(--client-accent-dark)]"
            >← Your maintenance</Link
        >
        <div class="my-7 flex flex-wrap items-center justify-between gap-4">
            <h1 class="section-title">Request #{{ request.id }}</h1>
            <span
                class="rounded-full bg-[var(--client-surface-soft)] px-4 py-2 text-sm font-semibold capitalize"
                >{{ request.status.replaceAll('_', ' ') }}</span
            >
        </div>
        <div class="grid items-start gap-8 lg:grid-cols-[1.2fr_1fr]">
            <div class="surface-card p-6 md:p-8">
                <p class="body-copy mb-7 text-sm">
                    Scope saved when you submitted this request. Later plan
                    edits do not change this record.
                </p>
                <MaintenanceScope :plan="request.plan" />
            </div>
            <div class="space-y-6">
                <section class="surface-card p-6">
                    <h2 class="text-xl font-semibold">Your requirements</h2>
                    <p v-if="request.website" class="body-copy mt-3 break-all">
                        {{ request.website }}
                    </p>
                    <p class="body-copy mt-4 whitespace-pre-line">
                        {{ request.requirements }}
                    </p>
                </section>
                <section class="surface-card p-6">
                    <h2 class="text-xl font-semibold">Latest update</h2>
                    <p class="body-copy mt-3 whitespace-pre-line">
                        {{
                            request.client_update ||
                            'Your request has been received. We will add updates here as we review your setup.'
                        }}
                    </p>
                    <div class="mt-5 space-y-3">
                        <div
                            v-if="request.checkout_order"
                            class="rounded-xl border border-[var(--client-border)] p-4"
                        >
                            <p class="text-sm font-semibold">
                                Order {{ request.checkout_order.order_number }}
                            </p>
                            <p class="body-copy mt-2 text-sm capitalize">
                                Payment: {{ request.checkout_order.status }}
                            </p>
                            <p
                                v-if="
                                    request.checkout_order.status === 'pending'
                                "
                                class="body-copy mt-2 text-sm"
                            >
                                Continue the existing payment session if
                                available. If payment is uncertain, contact
                                support with this order number before trying
                                again.
                            </p>
                        </div>
                        <a
                            v-if="request.checkout_resume_url"
                            :href="request.checkout_resume_url"
                            class="button-primary w-full"
                            rel="noreferrer"
                            >Resume existing payment</a
                        >
                        <Link
                            v-if="request.subscription"
                            :href="request.subscription.url"
                            class="button-secondary w-full"
                            >Subscription:
                            {{ request.subscription.status }}</Link
                        ><Link
                            v-if="request.quote"
                            :href="request.quote.url"
                            class="button-secondary w-full"
                            >Review quote {{ request.quote.number }}</Link
                        ><button
                            v-if="request.checkout_url"
                            :disabled="action.processing"
                            class="button-primary w-full"
                            @click="checkout"
                        >
                            {{
                                action.processing
                                    ? 'Opening billing…'
                                    : 'Continue to billing'
                            }}
                        </button>
                        <p
                            v-for="error in errors"
                            :key="error"
                            role="alert"
                            class="text-sm text-red-600"
                        >
                            {{ error }}
                        </p>
                        <p
                            v-if="
                                request.status === 'awaiting_payment' &&
                                !request.checkout_url &&
                                !request.quote &&
                                !request.subscription &&
                                !request.checkout_order
                            "
                            class="body-copy text-sm"
                        >
                            Billing arrangements need confirmation. Contact
                            support before making payment.
                        </p>
                        <Link
                            href="/client-area/tickets"
                            class="button-secondary w-full"
                            >Contact support</Link
                        >
                    </div>
                    <p class="body-copy mt-4 text-xs">
                        Service status and subscription billing are managed
                        separately. Use your subscription page to review renewal
                        and cancellation options.
                    </p>
                </section>
                <form
                    v-if="['requested', 'reviewing'].includes(request.status)"
                    class="surface-card p-6"
                    @submit.prevent="withdraw"
                >
                    <h2 class="font-semibold">Changed your plans?</h2>
                    <p class="body-copy mt-2 text-sm">
                        You can withdraw before billing is arranged.
                    </p>
                    <button
                        :disabled="action.processing"
                        class="button-secondary mt-4"
                    >
                        Withdraw request
                    </button>
                </form>
            </div>
        </div>
    </section>
</template>
