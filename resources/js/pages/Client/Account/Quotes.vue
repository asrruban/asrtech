<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileSignature } from '@lucide/vue';
import AccountCard from '@/modules/client/components/AccountCard.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

const props = defineProps<{
    account: { name: string; email: string; address: string[] };
    totalDue: string;
    currency: string;
    quotes: {
        id: number;
        quote_number: string;
        status: string;
        currency: string;
        total: number;
        items_count: number;
        valid_until: string | null;
        actionable: boolean;
        created_at: string | null;
    }[];
}>();

const money = (currency: string, amount: number) =>
    `${currency} ${amount.toFixed(2)}`;

const statusClass = (status: string) =>
    ({
        draft: 'bg-muted text-muted-foreground',
        sent: 'bg-[#087f75]/10 text-[var(--client-accent)]',
        accepted: 'bg-emerald-500/10 text-emerald-600',
        converted: 'bg-emerald-500/10 text-emerald-600',
        declined: 'bg-red-500/10 text-red-500',
        expired: 'bg-amber-500/10 text-amber-600',
    })[status] ?? 'bg-muted text-muted-foreground';

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(new Date(value))
        : '—';
</script>

<template>
    <SeoHead title="Quotes" description="Review and accept your quotes." />

    <ClientAreaHero title="My Quotes" overlap />

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
                    v-if="props.quotes.length === 0"
                    class="flex flex-col items-center gap-3 px-6 py-16 text-center"
                >
                    <FileSignature class="size-10 text-muted-foreground/50" />
                    <p class="text-sm text-muted-foreground">
                        You have no quotes yet.
                    </p>
                </div>

                <ul v-else class="divide-y">
                    <li v-for="quote in props.quotes" :key="quote.id">
                        <Link
                            :href="`/client-area/quotes/${quote.id}`"
                            class="flex flex-wrap items-center gap-4 px-6 py-4 transition hover:bg-muted/40"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-sm font-semibold">
                                    {{ quote.quote_number }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ quote.items_count }} item(s) · valid
                                    until {{ formatDate(quote.valid_until) }}
                                </p>
                            </div>
                            <p class="font-semibold">
                                {{ money(quote.currency, quote.total) }}
                            </p>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                :class="statusClass(quote.status)"
                            >
                                {{ quote.status }}
                            </span>
                            <span
                                v-if="quote.actionable"
                                class="rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground"
                            >
                                Review
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
