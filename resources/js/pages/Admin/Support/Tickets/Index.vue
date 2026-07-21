<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { LifeBuoy } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';

interface TicketRow {
    id: number;
    ticket_number: string;
    subject: string;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    client: { id: number; name: string; email: string } | null;
    department: string | null;
    last_reply_at: string | null;
}

const props = defineProps<{
    tickets: TicketRow[];
    counts: Record<string, number>;
    statuses: Record<string, string>;
    activeStatus: string;
}>();

const tabs = [
    { key: 'awaiting', label: 'Awaiting Reply' },
    { key: 'all', label: 'All' },
    ...Object.entries(props.statuses).map(([key, label]) => ({ key, label })),
];

const statusClasses: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    customer_reply:
        'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
    answered: 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
    in_progress:
        'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
    on_hold: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    closed: 'bg-muted text-muted-foreground',
};

const priorityClasses: Record<string, string> = {
    high: 'text-red-600 dark:text-red-400',
    medium: 'text-amber-600 dark:text-amber-400',
    low: 'text-muted-foreground',
};

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
    <Head title="Support tickets" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Support</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                Support Tickets
            </h1>
            <p class="mt-1 text-muted-foreground">
                Tickets opened by clients — reply to mark them answered.
            </p>
        </div>

        <div class="flex flex-wrap gap-1">
            <Link
                v-for="tab in tabs"
                :key="tab.key"
                :href="`/admin/support/tickets?status=${tab.key}`"
                class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                :class="
                    props.activeStatus === tab.key
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                "
                preserve-scroll
            >
                {{ tab.label }}
                <span class="ml-1 text-xs opacity-75">
                    {{ props.counts[tab.key] ?? 0 }}
                </span>
            </Link>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="props.tickets.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No tickets here.
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground"
                        >
                            <th class="px-4 py-3">Ticket</th>
                            <th class="hidden px-4 py-3 md:table-cell">
                                Client
                            </th>
                            <th class="hidden px-4 py-3 lg:table-cell">
                                Department
                            </th>
                            <th class="hidden px-4 py-3 lg:table-cell">
                                Priority
                            </th>
                            <th class="px-4 py-3">Status</th>
                            <th class="hidden px-4 py-3 md:table-cell">
                                Last Reply
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="ticket in props.tickets"
                            :key="ticket.id"
                            class="border-b last:border-0 hover:bg-muted/40"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/admin/support/tickets/${ticket.id}`"
                                    class="flex items-center gap-2 font-medium hover:underline"
                                >
                                    <LifeBuoy
                                        class="size-4 shrink-0 text-muted-foreground"
                                    />
                                    <span>
                                        #{{ ticket.ticket_number }} —
                                        {{ ticket.subject }}
                                    </span>
                                </Link>
                            </td>
                            <td
                                class="hidden px-4 py-3 text-muted-foreground md:table-cell"
                            >
                                <template v-if="ticket.client">
                                    {{ ticket.client.name }}
                                    <span class="block text-xs">
                                        {{ ticket.client.email }}
                                    </span>
                                </template>
                                <template v-else>—</template>
                            </td>
                            <td
                                class="hidden px-4 py-3 text-muted-foreground lg:table-cell"
                            >
                                {{ ticket.department ?? '—' }}
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <span
                                    class="text-xs font-semibold"
                                    :class="priorityClasses[ticket.priority]"
                                >
                                    {{ ticket.priority_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-bold"
                                    :class="
                                        statusClasses[ticket.status] ??
                                        'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ ticket.status_label }}
                                </span>
                            </td>
                            <td
                                class="hidden px-4 py-3 text-muted-foreground md:table-cell"
                            >
                                {{ formatDate(ticket.last_reply_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
