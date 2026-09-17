<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check, Copy, Handshake } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AccountCard from '@/modules/client/components/AccountCard.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

const props = defineProps<{
    account: { name: string; email: string; address: string[] };
    totalDue: string;
    currency: string;
    affiliate: {
        code: string;
        active: boolean;
        referral_url: string;
        commission_rate: number;
        pending_balance: number;
        approved_balance: number;
        paid_balance: number;
    } | null;
    referrals: {
        data: {
            id: number;
            referred: string | null;
            order_number: string | null;
            order_total: number;
            commission: number;
            status: string;
            created_at: string | null;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    } | null;
    defaultRate: number;
}>();

const joinForm = useForm({});
const copied = ref(false);

const join = () => joinForm.post('/client-area/affiliate/join');

const copyLink = async () => {
    if (!props.affiliate) {
        return;
    }

    await navigator.clipboard.writeText(props.affiliate.referral_url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const money = (amount: number) => `${props.currency} ${amount.toFixed(2)}`;

const statusClass = (status: string) =>
    ({
        pending: 'bg-amber-500/10 text-amber-600',
        approved: 'bg-[#087f75]/10 text-[var(--client-accent)]',
        paid: 'bg-emerald-500/10 text-emerald-600',
        cancelled: 'bg-muted text-muted-foreground',
    })[status] ?? 'bg-muted text-muted-foreground';

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleDateString() : '—';
</script>

<template>
    <SeoHead
        title="Affiliate program"
        description="Earn commission on referrals."
    />

    <ClientAreaHero title="Affiliate Program" overlap />

    <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
        <div
            class="-mt-24 grid items-start gap-6 lg:grid-cols-[360px_minmax(0,1fr)]"
        >
            <AccountCard
                :account="props.account"
                :total-due="props.totalDue"
                :currency="props.currency"
            />

            <div class="space-y-6">
                <!-- Join card -->
                <div
                    v-if="!props.affiliate"
                    class="rounded-xl border bg-card p-8 text-center"
                >
                    <Handshake class="mx-auto size-12 text-primary" />
                    <h2 class="mt-4 text-xl font-bold">
                        Earn {{ props.defaultRate }}% on every sale
                    </h2>
                    <p
                        class="mx-auto mt-2 max-w-md text-sm text-muted-foreground"
                    >
                        Join the affiliate program, share your unique link, and
                        earn commission on every paid order from customers you
                        refer.
                    </p>
                    <button
                        type="button"
                        class="mt-6 inline-flex h-11 items-center rounded-md bg-primary px-8 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-50"
                        :disabled="joinForm.processing"
                        @click="join"
                    >
                        Join the program
                    </button>
                </div>

                <template v-else>
                    <!-- Link + stats -->
                    <div class="rounded-xl border bg-card p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-bold">Your referral link</h2>
                            <span
                                v-if="!props.affiliate.active"
                                class="rounded-full bg-red-500/10 px-2.5 py-1 text-xs font-semibold text-red-500"
                            >
                                Disabled
                            </span>
                        </div>
                        <div class="mt-3 flex items-center gap-2">
                            <code
                                class="flex-1 truncate rounded-md border bg-muted/50 px-3 py-2 font-mono text-sm"
                            >
                                {{ props.affiliate.referral_url }}
                            </code>
                            <Button
                                size="icon"
                                variant="outline"
                                aria-label="Copy referral link"
                                @click="copyLink"
                            >
                                <Check
                                    v-if="copied"
                                    class="size-4 text-emerald-500"
                                />
                                <Copy v-else class="size-4" />
                            </Button>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">
                            You earn {{ props.affiliate.commission_rate }}% of
                            each referred paid order.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <Card>
                            <CardHeader class="pb-1">
                                <p class="text-xs text-muted-foreground">
                                    Pending
                                </p>
                                <CardTitle class="text-xl">{{
                                    money(props.affiliate.pending_balance)
                                }}</CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader class="pb-1">
                                <p class="text-xs text-muted-foreground">
                                    Approved (payable)
                                </p>
                                <CardTitle class="text-xl">{{
                                    money(props.affiliate.approved_balance)
                                }}</CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader class="pb-1">
                                <p class="text-xs text-muted-foreground">
                                    Paid out
                                </p>
                                <CardTitle class="text-xl">{{
                                    money(props.affiliate.paid_balance)
                                }}</CardTitle>
                            </CardHeader>
                        </Card>
                    </div>

                    <!-- Referrals -->
                    <Card>
                        <CardContent class="p-0">
                            <div
                                v-if="
                                    !props.referrals ||
                                    props.referrals.data.length === 0
                                "
                                class="p-10 text-center text-sm text-muted-foreground"
                            >
                                No referrals yet — share your link to get
                                started.
                            </div>
                            <template v-else>
                                <!-- Mobile: cards -->
                                <div class="divide-y md:hidden">
                                    <div
                                        v-for="referral in props.referrals.data"
                                        :key="referral.id"
                                        class="px-4 py-4"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <p class="font-semibold">
                                                {{ referral.referred }}
                                            </p>
                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                                :class="
                                                    statusClass(referral.status)
                                                "
                                            >
                                                {{ referral.status }}
                                            </span>
                                        </div>
                                        <p
                                            class="mt-1 font-mono text-xs text-muted-foreground"
                                        >
                                            {{ referral.order_number }} ·
                                            {{
                                                formatDate(referral.created_at)
                                            }}
                                        </p>
                                        <div
                                            class="mt-2 flex items-center justify-between text-sm"
                                        >
                                            <span class="text-muted-foreground">
                                                Order
                                                {{
                                                    money(referral.order_total)
                                                }}
                                            </span>
                                            <span
                                                class="font-bold text-[var(--client-accent)]"
                                            >
                                                +{{
                                                    money(referral.commission)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Desktop: table -->
                                <table
                                    class="hidden w-full text-left text-sm md:table"
                                >
                                    <thead>
                                        <tr
                                            class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                        >
                                            <th class="px-5 py-3.5">
                                                Customer
                                            </th>
                                            <th class="px-5 py-3.5">Order</th>
                                            <th class="px-5 py-3.5">
                                                Order total
                                            </th>
                                            <th class="px-5 py-3.5">
                                                Commission
                                            </th>
                                            <th class="px-5 py-3.5">Status</th>
                                            <th class="px-5 py-3.5">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="referral in props.referrals
                                                .data"
                                            :key="referral.id"
                                            class="border-b last:border-0"
                                        >
                                            <td class="px-5 py-3.5 font-medium">
                                                {{ referral.referred }}
                                            </td>
                                            <td
                                                class="px-5 py-3.5 font-mono text-xs"
                                            >
                                                {{ referral.order_number }}
                                            </td>
                                            <td class="px-5 py-3.5">
                                                {{
                                                    money(referral.order_total)
                                                }}
                                            </td>
                                            <td
                                                class="px-5 py-3.5 font-semibold"
                                            >
                                                {{ money(referral.commission) }}
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <span
                                                    class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                                    :class="
                                                        statusClass(
                                                            referral.status,
                                                        )
                                                    "
                                                >
                                                    {{ referral.status }}
                                                </span>
                                            </td>
                                            <td
                                                class="px-5 py-3.5 text-muted-foreground"
                                            >
                                                {{
                                                    formatDate(
                                                        referral.created_at,
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                        </CardContent>
                    </Card>
                </template>
            </div>
        </div>
    </section>
</template>
