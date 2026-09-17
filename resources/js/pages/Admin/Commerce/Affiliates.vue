<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Handshake } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Referral = {
    id: number;
    affiliate: string | null;
    referred: string | null;
    order_number: string | null;
    order_total: number;
    commission: number;
    status: string;
    created_at: string | null;
};

const props = defineProps<{
    affiliates: {
        id: number;
        client: { id: number; name: string; email: string } | null;
        code: string;
        active: boolean;
        commission_rate: number;
        pending_balance: number;
        approved_balance: number;
        paid_balance: number;
        referrals_count: number;
        created_at: string | null;
    }[];
    referrals: {
        data: Referral[];
        links: { url: string | null; label: string; active: boolean }[];
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    statuses: string[];
}>();

const toggle = (id: number) =>
    useForm({}).patch(`/admin/affiliates/${id}/toggle`);
const approve = (id: number) =>
    useForm({}).post(`/admin/referrals/${id}/approve`);
const pay = (id: number) => useForm({}).post(`/admin/referrals/${id}/pay`);

const money = (amount: number) => `USD ${amount.toFixed(2)}`;

const statusClass = (status: string) =>
    ({
        pending: 'bg-amber-500/10 text-amber-600',
        approved: 'bg-blue-500/10 text-blue-600',
        paid: 'bg-emerald-500/10 text-emerald-600',
        cancelled: 'bg-muted text-muted-foreground',
    })[status] ?? 'bg-muted text-muted-foreground';

const paginationLabel = (value: string) =>
    value.replace('&laquo;', '«').replace('&raquo;', '»').trim();
</script>

<template>
    <Head title="Affiliates" />

    <div class="space-y-8 p-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Affiliates</h1>
            <p class="text-sm text-muted-foreground">
                Referral partners, commissions, and payouts.
            </p>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="props.affiliates.length === 0"
                    class="flex flex-col items-center gap-3 p-10 text-center text-sm text-muted-foreground"
                >
                    <Handshake class="size-8 text-muted-foreground/50" />
                    No affiliates yet. Clients can join from their client area.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3.5">Affiliate</th>
                                <th class="px-5 py-3.5">Code</th>
                                <th class="px-5 py-3.5">Rate</th>
                                <th class="px-5 py-3.5">Referrals</th>
                                <th class="px-5 py-3.5">Pending</th>
                                <th class="px-5 py-3.5">Approved</th>
                                <th class="px-5 py-3.5">Paid</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="affiliate in props.affiliates"
                                :key="affiliate.id"
                                class="border-b last:border-0 hover:bg-muted/40"
                            >
                                <td class="px-5 py-4">
                                    <p class="font-medium">
                                        {{ affiliate.client?.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ affiliate.client?.email }}
                                    </p>
                                </td>
                                <td
                                    class="px-5 py-4 font-mono text-xs font-semibold"
                                >
                                    {{ affiliate.code }}
                                </td>
                                <td class="px-5 py-4">
                                    {{ affiliate.commission_rate }}%
                                </td>
                                <td class="px-5 py-4">
                                    {{ affiliate.referrals_count }}
                                </td>
                                <td class="px-5 py-4">
                                    {{ money(affiliate.pending_balance) }}
                                </td>
                                <td class="px-5 py-4">
                                    {{ money(affiliate.approved_balance) }}
                                </td>
                                <td class="px-5 py-4">
                                    {{ money(affiliate.paid_balance) }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="toggle(affiliate.id)"
                                    >
                                        {{
                                            affiliate.active
                                                ? 'Disable'
                                                : 'Enable'
                                        }}
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div>
            <h2 class="text-lg font-bold">Referral commissions</h2>
            <Card class="mt-3">
                <CardContent class="p-0">
                    <div
                        v-if="props.referrals.data.length === 0"
                        class="p-10 text-center text-sm text-muted-foreground"
                    >
                        No referral commissions yet.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[860px] text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    <th class="px-5 py-3.5">Affiliate</th>
                                    <th class="px-5 py-3.5">Referred</th>
                                    <th class="px-5 py-3.5">Order</th>
                                    <th class="px-5 py-3.5">Total</th>
                                    <th class="px-5 py-3.5">Commission</th>
                                    <th class="px-5 py-3.5">Status</th>
                                    <th class="px-5 py-3.5 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="referral in props.referrals.data"
                                    :key="referral.id"
                                    class="border-b last:border-0 hover:bg-muted/40"
                                >
                                    <td class="px-5 py-4 font-medium">
                                        {{ referral.affiliate }}
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ referral.referred }}
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs">
                                        {{ referral.order_number }}
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ money(referral.order_total) }}
                                    </td>
                                    <td class="px-5 py-4 font-semibold">
                                        {{ money(referral.commission) }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                            :class="
                                                statusClass(referral.status)
                                            "
                                        >
                                            {{ referral.status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                v-if="
                                                    referral.status ===
                                                    'pending'
                                                "
                                                size="sm"
                                                variant="outline"
                                                @click="approve(referral.id)"
                                            >
                                                Approve
                                            </Button>
                                            <Button
                                                v-if="
                                                    referral.status ===
                                                    'approved'
                                                "
                                                size="sm"
                                                @click="pay(referral.id)"
                                            >
                                                Mark paid
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <div
                v-if="props.referrals.last_page > 1"
                class="mt-4 flex items-center justify-between gap-4 text-sm"
            >
                <p class="text-muted-foreground">
                    Showing {{ props.referrals.from }}–{{
                        props.referrals.to
                    }}
                    of {{ props.referrals.total }}
                </p>
                <div class="flex gap-2">
                    <template
                        v-for="link in props.referrals.links"
                        :key="link.label"
                    >
                        <Button
                            v-if="link.url"
                            as-child
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                        >
                            <Link :href="link.url">{{
                                paginationLabel(link.label)
                            }}</Link>
                        </Button>
                        <Button v-else size="sm" variant="outline" disabled>
                            {{ paginationLabel(link.label) }}
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
