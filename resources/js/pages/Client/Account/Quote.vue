<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AccountCard from '@/modules/client/components/AccountCard.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

const props = defineProps<{
    account: { name: string; email: string; address: string[] };
    totalDue: string;
    currency: string;
    quote: {
        id: number;
        quote_number: string;
        status: string;
        currency: string;
        subtotal: number;
        tax_amount: number;
        total: number;
        admin_note: string | null;
        valid_until: string | null;
        actionable: boolean;
        order_id: number | null;
        items: {
            product_name: string;
            quantity: number;
            unit_price: number;
            line_total: number;
            billing_cycle: string;
        }[];
    };
}>();

const acceptForm = useForm({});
const declineForm = useForm({ client_note: '' });

const accept = () =>
    acceptForm.post(`/client-area/quotes/${props.quote.id}/accept`);
const decline = () => {
    if (!confirm('Decline this quote? This cannot be undone.')) {
        return;
    }

    declineForm.post(`/client-area/quotes/${props.quote.id}/decline`);
};

const money = (amount: number) =>
    `${props.quote.currency} ${amount.toFixed(2)}`;

const cycleLabel = (cycle: string) =>
    ({ one_time: 'one-time', monthly: '/month', yearly: '/year' })[cycle] ??
    cycle;
</script>

<template>
    <SeoHead
        :title="`Quote ${props.quote.quote_number}`"
        description="Review your quote."
    />

    <ClientAreaHero title="Quote Review" overlap />

    <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
        <div
            class="-mt-24 grid items-start gap-6 lg:grid-cols-[360px_minmax(0,1fr)]"
        >
            <AccountCard
                :account="props.account"
                :total-due="props.totalDue"
                :currency="props.currency"
            />

            <div class="rounded-xl border bg-card">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b px-6 py-4"
                >
                    <div>
                        <Link
                            href="/client-area/quotes"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
                        >
                            <ArrowLeft class="size-3.5" /> All quotes
                        </Link>
                        <h2 class="mt-1 font-mono text-lg font-bold">
                            {{ props.quote.quote_number }}
                        </h2>
                    </div>
                    <p
                        v-if="props.quote.valid_until"
                        class="text-sm text-muted-foreground"
                    >
                        Valid until {{ props.quote.valid_until }}
                    </p>
                </div>

                <div class="px-6 py-5">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-xs text-muted-foreground"
                            >
                                <th class="pb-2 font-medium">Item</th>
                                <th class="pb-2 text-right font-medium">Qty</th>
                                <th class="pb-2 text-right font-medium">
                                    Unit price
                                </th>
                                <th class="pb-2 text-right font-medium">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in props.quote.items"
                                :key="index"
                                class="border-b last:border-0"
                            >
                                <td class="py-3">
                                    <p class="font-medium">
                                        {{ item.product_name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ cycleLabel(item.billing_cycle) }}
                                    </p>
                                </td>
                                <td class="py-3 text-right">
                                    {{ item.quantity }}
                                </td>
                                <td class="py-3 text-right">
                                    {{ money(item.unit_price) }}
                                </td>
                                <td class="py-3 text-right font-semibold">
                                    {{ money(item.line_total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-4 space-y-1 border-t pt-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span>{{ money(props.quote.subtotal) }}</span>
                        </div>
                        <div
                            v-if="props.quote.tax_amount > 0"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground">Tax</span>
                            <span>{{ money(props.quote.tax_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold">
                            <span>Total</span>
                            <span class="text-primary">{{
                                money(props.quote.total)
                            }}</span>
                        </div>
                    </div>

                    <div
                        v-if="props.quote.admin_note"
                        class="mt-5 rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground"
                    >
                        {{ props.quote.admin_note }}
                    </div>
                </div>

                <div
                    v-if="props.quote.actionable"
                    class="flex flex-wrap gap-3 border-t px-6 py-5"
                >
                    <button
                        type="button"
                        class="inline-flex h-11 items-center rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-50"
                        :disabled="acceptForm.processing"
                        @click="accept"
                    >
                        Accept quote & get invoice
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-11 items-center rounded-md border px-6 text-sm font-semibold text-destructive transition hover:bg-destructive/5 disabled:opacity-50"
                        :disabled="declineForm.processing"
                        @click="decline"
                    >
                        Decline
                    </button>
                </div>
                <div
                    v-else
                    class="border-t px-6 py-5 text-sm text-muted-foreground"
                >
                    <template v-if="props.quote.status === 'converted'">
                        This quote was converted to an order. Check your
                        <Link
                            href="/client-area/invoices"
                            class="font-semibold text-primary hover:underline"
                            >invoices</Link
                        >.
                    </template>
                    <template v-else>
                        This quote is
                        <span class="font-semibold capitalize">{{
                            props.quote.status
                        }}</span>
                        and can no longer be changed.
                    </template>
                </div>
            </div>
        </div>
    </section>
</template>
