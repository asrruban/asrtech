<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Send, Trash2, UserRound } from '@lucide/vue';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';

interface Reply {
    id: number;
    message: string;
    author: string;
    is_staff: boolean;
    created_at: string;
}

interface TicketData {
    id: number;
    ticket_number: string;
    subject: string;
    status: string;
    priority: string;
    priority_label: string;
    client: { id: number; name: string; email: string } | null;
    department: string | null;
    created_at: string | null;
    last_reply_at: string | null;
}

const props = defineProps<{
    ticket: TicketData;
    replies: Reply[];
    statuses: Record<string, string>;
}>();

const replyForm = useForm({ message: '' });
const sendReply = () =>
    replyForm.post(`/admin/support/tickets/${props.ticket.id}/reply`, {
        preserveScroll: true,
        onSuccess: () => replyForm.reset(),
    });

const statusForm = useForm({ status: props.ticket.status });
// Replying changes the status server-side — keep the select in sync.
watch(
    () => props.ticket.status,
    (status) => (statusForm.status = status),
);
const changeStatus = () =>
    statusForm.patch(`/admin/support/tickets/${props.ticket.id}/status`, {
        preserveScroll: true,
    });

const removeTicket = () => {
    if (confirm(`Delete ticket #${props.ticket.ticket_number}?`)) {
        router.delete(`/admin/support/tickets/${props.ticket.id}`);
    }
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
    <Head :title="`Ticket #${props.ticket.ticket_number}`" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <Link
                href="/admin/support/tickets"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Support Tickets
            </Link>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                #{{ props.ticket.ticket_number }} — {{ props.ticket.subject }}
            </h1>
            <p class="mt-1 text-muted-foreground">
                {{ props.ticket.department ?? 'No department' }} ·
                {{ props.ticket.priority_label }} priority · opened
                {{ formatDate(props.ticket.created_at) }}
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
            <div class="space-y-6">
                <div class="space-y-4">
                    <div
                        v-for="reply in props.replies"
                        :key="reply.id"
                        class="rounded-lg border p-4"
                        :class="
                            reply.is_staff
                                ? 'border-primary/30 bg-primary/5'
                                : 'bg-card'
                        "
                    >
                        <div
                            class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm"
                        >
                            <span class="flex items-center gap-2 font-semibold">
                                <UserRound
                                    class="size-4 text-muted-foreground"
                                />
                                {{ reply.author }}
                                <span
                                    v-if="reply.is_staff"
                                    class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary"
                                >
                                    Staff
                                </span>
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDate(reply.created_at) }}
                            </span>
                        </div>
                        <p class="whitespace-pre-wrap text-sm leading-relaxed">
                            {{ reply.message }}
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader><CardTitle>Reply</CardTitle></CardHeader>
                    <CardContent>
                        <form class="space-y-3" @submit.prevent="sendReply">
                            <textarea
                                v-model="replyForm.message"
                                rows="6"
                                required
                                placeholder="Write your reply to the client…"
                                class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                            ></textarea>
                            <InputError :message="replyForm.errors.message" />
                            <Button
                                type="submit"
                                :disabled="replyForm.processing"
                            >
                                <Send class="size-4" /> Send Reply
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader><CardTitle>Client</CardTitle></CardHeader>
                    <CardContent class="text-sm">
                        <template v-if="props.ticket.client">
                            <Link
                                :href="`/admin/users/${props.ticket.client.id}`"
                                class="font-medium hover:underline"
                            >
                                {{ props.ticket.client.name }}
                            </Link>
                            <p class="text-muted-foreground">
                                {{ props.ticket.client.email }}
                            </p>
                        </template>
                        <p v-else class="text-muted-foreground">
                            Client account deleted.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Status</CardTitle></CardHeader>
                    <CardContent class="space-y-3">
                        <div class="space-y-2">
                            <Label for="ticket-status">Ticket status</Label>
                            <select
                                id="ticket-status"
                                v-model="statusForm.status"
                                class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option
                                    v-for="(label, value) in props.statuses"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <InputError :message="statusForm.errors.status" />
                        </div>
                        <Button
                            variant="outline"
                            class="w-full"
                            :disabled="
                                statusForm.processing ||
                                statusForm.status === props.ticket.status
                            "
                            @click="changeStatus"
                        >
                            Update Status
                        </Button>
                    </CardContent>
                </Card>

                <Button
                    variant="outline"
                    class="w-full text-destructive hover:text-destructive"
                    @click="removeTicket"
                >
                    <Trash2 class="size-4" /> Delete Ticket
                </Button>
            </div>
        </div>
    </div>
</template>
