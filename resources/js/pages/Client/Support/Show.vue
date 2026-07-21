<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CircleCheck, Send, ShieldCheck, UserRound } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

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
    status_label: string;
    priority: string;
    department: string | null;
    created_at: string | null;
    can_close: boolean;
}

const props = defineProps<{
    ticket: TicketData;
    replies: Reply[];
}>();

const form = useForm({ message: '' });

const submit = () =>
    form.post(`/client-area/ticket/${props.ticket.id}/reply`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });

const closeTicket = () => {
    if (confirm('Close this ticket? You can reopen it by replying again.')) {
        router.post(
            `/client-area/ticket/${props.ticket.id}/close`,
            {},
            { preserveScroll: true },
        );
    }
};

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
        :title="`Ticket #${props.ticket.ticket_number}`"
        description="Your support ticket conversation."
    />

    <ClientAreaHero
        :title="`#${props.ticket.ticket_number} — ${props.ticket.subject}`"
        :subtitle="`${props.ticket.department ?? 'General'} · ${props.ticket.priority} priority · opened ${formatDate(props.ticket.created_at)}`"
    />

    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Link
                href="/client-area/tickets"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Back to your tickets
            </Link>
            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold"
                    :class="statusClass(props.ticket.status)"
                >
                    {{ props.ticket.status_label }}
                </span>
                <button
                    v-if="props.ticket.can_close"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-semibold text-muted-foreground transition hover:text-foreground"
                    @click="closeTicket"
                >
                    <CircleCheck class="size-4" /> Close Ticket
                </button>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            <article
                v-for="reply in props.replies"
                :key="reply.id"
                class="rounded-2xl border p-5 shadow-sm"
                :class="
                    reply.is_staff
                        ? 'border-[#4fb250]/40 bg-[#eff9ef]/40 dark:bg-[#4fb250]/5'
                        : 'bg-card'
                "
            >
                <div
                    class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm"
                >
                    <span class="flex items-center gap-2 font-bold">
                        <ShieldCheck
                            v-if="reply.is_staff"
                            class="size-4 text-[#4fb250]"
                        />
                        <UserRound
                            v-else
                            class="size-4 text-muted-foreground"
                        />
                        {{ reply.author }}
                        <span
                            v-if="reply.is_staff"
                            class="rounded-full bg-[#4fb250]/10 px-2 py-0.5 text-xs font-bold text-[#357e37] dark:text-[#84d780]"
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
            </article>
        </div>

        <form
            class="mt-8 space-y-3 rounded-2xl border bg-card p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <label class="text-sm font-semibold" for="reply-message">
                {{
                    props.ticket.status === 'closed'
                        ? 'Reply to reopen this ticket'
                        : 'Add a reply'
                }}
            </label>
            <textarea
                id="reply-message"
                v-model="form.message"
                rows="5"
                required
                class="w-full rounded-lg border bg-transparent px-3 py-2 text-sm"
            ></textarea>
            <InputError :message="form.errors.message" />
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4fb250] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#3f9f40] disabled:opacity-60"
            >
                <Send class="size-4" /> Send Reply
            </button>
        </form>
    </section>
</template>
