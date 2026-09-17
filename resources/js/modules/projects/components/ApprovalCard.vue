<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { date, label } from '../types';
import type { ProjectApproval } from '../types';
import FormFeedback from './FormFeedback.vue';
const props = defineProps<{
    approval: ProjectApproval;
    base: string;
    admin: boolean;
    closed: boolean;
}>();
const form = useForm({ status: 'approved', response: '' });
const cancel = useForm({});
function decide() {
    form.post(`${props.base}/approvals/${props.approval.id}`, {
        preserveScroll: true,
    });
}
</script>
<template>
    <article
        class="rounded-xl border p-5"
        :class="
            approval.status === 'pending'
                ? 'border-amber-200 bg-amber-50/30'
                : 'border-border'
        "
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h3 class="font-semibold">{{ approval.title }}</h3>
            <span
                class="rounded-full bg-muted px-3 py-1 text-xs font-semibold capitalize"
                >{{ label(approval.status) }}</span
            >
        </div>
        <p
            class="mt-3 text-sm leading-6 break-words whitespace-pre-wrap text-muted-foreground"
        >
            {{ approval.description }}
        </p>
        <p class="mt-3 text-xs text-muted-foreground">
            Requested {{ date(approval.created_at)
            }}<span v-if="approval.decided_at">
                · Updated {{ date(approval.decided_at) }}</span
            >
        </p>
        <p
            v-if="approval.response"
            class="mt-4 rounded-lg bg-muted p-3 text-sm break-words whitespace-pre-wrap"
        >
            <strong>Client response:</strong> {{ approval.response }}
        </p>
        <form
            v-if="approval.status === 'pending' && !admin && !closed"
            class="mt-4 grid gap-3"
            @submit.prevent="decide"
        >
            <label class="project-label"
                >Your decision<select
                    v-model="form.status"
                    class="project-field"
                >
                    <option value="approved">
                        Approve the work described above
                    </option>
                    <option value="changes_requested">Request changes</option>
                </select></label
            >
            <label class="project-label"
                >{{
                    form.status === 'changes_requested'
                        ? 'Changes needed (required)'
                        : 'Comment (optional)'
                }}<textarea
                    v-model="form.response"
                    class="project-field"
                    rows="3"
                    maxlength="5000"
                    :required="form.status === 'changes_requested'"
                />
            </label>
            <p class="text-xs text-muted-foreground">
                Your response is recorded in this project and cannot be edited
                after submission. Project approval does not make a payment.
            </p>
            <FormFeedback :errors="form.errors" /><button
                class="project-button"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Submitting…' : 'Submit decision' }}
            </button>
        </form>
        <form
            v-if="approval.status === 'pending' && admin && !closed"
            class="mt-4"
            @submit.prevent="
                cancel.delete(`${base}/approvals/${approval.id}`, {
                    preserveScroll: true,
                })
            "
        >
            <FormFeedback :errors="cancel.errors" /><button
                class="text-sm font-semibold text-red-700 underline underline-offset-4"
                :disabled="cancel.processing"
            >
                {{ cancel.processing ? 'Cancelling…' : 'Cancel request' }}
            </button>
        </form>
    </article>
</template>
