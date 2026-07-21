<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LifeBuoy, MessageSquareText, Plus } from '@lucide/vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface TicketItem {
    id: number;
    ticket_number: string;
    subject: string;
    status: string;
    status_label: string;
    priority: string;
    department: string | null;
    replies_count: number;
    last_reply_at: string | null;
}

const props = defineProps<{
    tickets: TicketItem[];
}>();

const statusClass = (status: string) =>
    ({
        open: 'bg-[#eff9ef] text-[#357e37] dark:bg-[#4fb250]/10 dark:text-[#84d780]',
        customer_reply:
            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        answered:
            'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        in_progress:
            'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
        on_hold:
            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        closed: 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300',
    })[status] ?? 'bg-slate-100 text-slate-600';

const formatDate = (date: string | null) =>
    date
        ? new Intl.DateTimeFormat('en', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          }).format(new Date(date))
        : '—';
</script>

<template>
    <SeoHead
        title="Support tickets"
        description="Open and follow your support tickets."
    />

    <ClientAreaHero
        title="Support Tickets"
        subtitle="Need a hand with a product or your account? Open a ticket and our team will get back to you."
    />

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <LifeBuoy class="size-5 text-[#4fb250]" />
                <h2 class="text-xl font-bold tracking-tight sm:text-2xl">
                    Your tickets
                </h2>
            </div>
            <Link
                href="/client-area/tickets/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4fb250] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#3f9f40]"
            >
                <Plus class="size-4" /> Open Ticket
            </Link>
        </div>

        <div
            v-if="props.tickets.length === 0"
            class="mt-6 rounded-2xl border bg-card p-10 text-center text-sm text-muted-foreground"
        >
            You haven't opened any support tickets yet.
        </div>

        <div v-else class="mt-6 space-y-4">
            <Link
                v-for="ticket in props.tickets"
                :key="ticket.id"
                :href="`/client-area/ticket/${ticket.id}`"
                class="block rounded-2xl border bg-card p-6 shadow-sm transition hover:border-[#4fb250]/50"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-bold tracking-tight">
                            #{{ ticket.ticket_number }} — {{ ticket.subject }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ ticket.department ?? 'General' }} ·
                            {{ ticket.priority }} priority ·
                            {{ ticket.replies_count }}
                            {{ ticket.replies_count === 1 ? 'message' : 'messages' }}
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"
                        :class="statusClass(ticket.status)"
                    >
                        <MessageSquareText class="size-3.5" />
                        {{ ticket.status_label }}
                    </span>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    Last activity: {{ formatDate(ticket.last_reply_at) }}
                </p>
            </Link>
        </div>
    </section>
</template>
