<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { date, label } from '../types';
import type { Project } from '../types';
import ApprovalCard from './ApprovalCard.vue';
import FormFeedback from './FormFeedback.vue';
import MilestoneCard from './MilestoneCard.vue';
const props = defineProps<{ project: Project; admin?: boolean }>();
const base = computed(
    () =>
        `${props.admin ? '/admin' : '/client-area'}/projects/${props.project.id}`,
);
const closed = computed(() =>
    ['completed', 'cancelled'].includes(props.project.status),
);
const completed = computed(
    () =>
        props.project.milestones.filter((m) => m.status === 'completed').length,
);
const editing = ref(false);
const details = useForm({
    title: props.project.title,
    description: props.project.description,
    status: props.project.status,
    target_date: props.project.target_date ?? '',
});
const milestone = useForm({ title: '', description: '', due_date: '' });
const update = useForm({ body: '' });
const approval = useForm({ title: '', description: '' });
const upload = useForm<{ file: File | null }>({ file: null });
const input = ref<HTMLInputElement | null>(null);
function attach(event: Event) {
    upload.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}
function submitFile() {
    upload.post(`${base.value}/files`, {
        preserveScroll: true,
        onSuccess: () => {
            upload.reset();

            if (input.value) {
                input.value.value = '';
            }
        },
    });
}
</script>
<template>
    <div class="project-workspace mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <Link
            :href="admin ? '/admin/projects' : '/client-area/projects'"
            class="text-sm font-semibold text-primary"
            >← All projects</Link
        >
        <header
            class="mt-6 grid gap-6 rounded-2xl border border-border bg-card p-6 sm:p-8 lg:grid-cols-[1fr_auto]"
        >
            <div class="min-w-0">
                <p
                    class="text-xs font-semibold tracking-widest text-primary uppercase"
                >
                    Project workspace · #{{ project.id }}
                </p>
                <h1
                    class="mt-3 text-3xl font-semibold tracking-tight break-words"
                >
                    {{ project.title }}
                </h1>
                <p
                    class="mt-4 max-w-3xl text-sm leading-7 break-words whitespace-pre-wrap text-muted-foreground"
                >
                    {{ project.description }}
                </p>
                <p v-if="admin" class="mt-4 text-sm">
                    <strong>Client:</strong> {{ project.client.name }} ·
                    {{ project.client.email }}
                </p>
                <div v-if="admin" class="mt-4 flex flex-wrap gap-4 text-sm">
                    <Link
                        v-if="project.project_inquiry_id"
                        :href="`/admin/inquiries/${project.project_inquiry_id}`"
                        class="font-semibold text-primary"
                        >Source inquiry</Link
                    ><button
                        type="button"
                        class="font-semibold text-primary underline underline-offset-4"
                        :aria-expanded="editing"
                        @click="editing = !editing"
                    >
                        {{ editing ? 'Close settings' : 'Edit project' }}
                    </button>
                </div>
            </div>
            <dl class="grid gap-5 rounded-xl bg-muted p-5 text-sm lg:min-w-52">
                <div>
                    <dt class="text-muted-foreground">Status</dt>
                    <dd class="mt-1 font-semibold capitalize">
                        {{ label(project.status) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Target date</dt>
                    <dd class="mt-1 font-semibold">
                        {{ date(project.target_date) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Milestones completed</dt>
                    <dd class="mt-1 font-semibold">
                        {{ completed }} of {{ project.milestones.length }}
                    </dd>
                </div>
            </dl>
            <form
                v-if="admin && editing"
                class="grid gap-4 border-t border-border pt-6 lg:col-span-2"
                @submit.prevent="
                    details.patch(base, {
                        preserveScroll: true,
                        onSuccess: () => (editing = false),
                    })
                "
            >
                <label class="project-label"
                    >Project title<input
                        v-model="details.title"
                        class="project-field"
                        required
                        maxlength="180" /></label
                ><label class="project-label"
                    >Agreed scope<textarea
                        v-model="details.description"
                        class="project-field"
                        rows="5"
                        required
                        maxlength="10000"
                    />
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="project-label"
                        >Status<select
                            v-model="details.status"
                            class="project-field"
                        >
                            <option
                                v-for="status in [
                                    'planned',
                                    'active',
                                    'paused',
                                    'completed',
                                    'cancelled',
                                ]"
                                :key="status"
                                :value="status"
                            >
                                {{ label(status) }}
                            </option>
                        </select></label
                    ><label class="project-label"
                        >Target date<input
                            v-model="details.target_date"
                            type="date"
                            class="project-field"
                    /></label>
                </div>
                <p class="text-xs text-muted-foreground">
                    Resolve pending approvals before closing. Complete all
                    milestones before marking the project completed. Returning a
                    closed project to active reopens collaboration.
                </p>
                <FormFeedback :errors="details.errors" /><button
                    class="project-button"
                    :disabled="details.processing"
                >
                    {{ details.processing ? 'Saving…' : 'Save project' }}
                </button>
            </form>
        </header>
        <p
            v-if="closed"
            role="status"
            class="mt-6 rounded-lg bg-muted p-4 text-sm"
        >
            This project is {{ project.status }}. Its files and activity remain
            available.
            <Link
                v-if="!admin"
                href="/client-area/tickets/create"
                class="font-semibold text-primary underline"
                >Contact support</Link
            ><span v-else
                >Reopen it in project settings to continue collaboration.</span
            >
        </p>
        <div
            class="mt-7 grid items-start gap-7 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]"
        >
            <div class="min-w-0 space-y-7">
                <section
                    class="rounded-2xl border border-border bg-card p-5 sm:p-6"
                >
                    <h2 class="text-xl font-semibold">Milestones</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Scope and status are updated by ASR Tech as work
                        progresses.
                    </p>
                    <p
                        v-if="!project.milestones.length"
                        class="mt-6 rounded-lg bg-muted p-4 text-sm text-muted-foreground"
                    >
                        No milestones have been scheduled yet.
                    </p>
                    <div class="mt-5 space-y-3">
                        <MilestoneCard
                            v-for="item in project.milestones"
                            :key="`${item.id}-${item.status}-${item.due_date}-${item.title}`"
                            :milestone="item"
                            :base="base"
                            :editable="!!admin && !closed"
                        />
                    </div>
                    <details v-if="admin && !closed" class="mt-5">
                        <summary
                            class="cursor-pointer text-sm font-semibold text-primary"
                        >
                            Add milestone
                        </summary>
                        <form
                            class="mt-4 grid gap-3"
                            @submit.prevent="
                                milestone.post(`${base}/milestones`, {
                                    preserveScroll: true,
                                    onSuccess: () => milestone.reset(),
                                })
                            "
                        >
                            <label class="project-label"
                                >Milestone title<input
                                    v-model="milestone.title"
                                    class="project-field"
                                    required
                                    maxlength="180" /></label
                            ><label class="project-label"
                                >Scope<textarea
                                    v-model="milestone.description"
                                    class="project-field"
                                    rows="3"
                                    maxlength="5000"
                                /></label
                            ><label class="project-label"
                                >Target date<input
                                    v-model="milestone.due_date"
                                    type="date"
                                    class="project-field" /></label
                            ><FormFeedback
                                :errors="milestone.errors"
                                :saved="milestone.recentlySuccessful"
                            /><button
                                class="project-button"
                                :disabled="milestone.processing"
                            >
                                {{
                                    milestone.processing
                                        ? 'Adding…'
                                        : 'Add milestone'
                                }}
                            </button>
                        </form>
                    </details>
                </section>
                <section
                    class="rounded-2xl border border-border bg-card p-5 sm:p-6"
                >
                    <h2 class="text-xl font-semibold">Updates & discussion</h2>
                    <form
                        v-if="!closed"
                        class="mt-5 grid gap-3"
                        @submit.prevent="
                            update.post(`${base}/updates`, {
                                preserveScroll: true,
                                onSuccess: () => update.reset(),
                            })
                        "
                    >
                        <label class="project-label"
                            >{{
                                admin
                                    ? 'Share a progress update'
                                    : 'Add a comment or question'
                            }}<textarea
                                v-model="update.body"
                                class="project-field"
                                rows="4"
                                required
                                maxlength="10000"
                            />
                        </label>
                        <p class="text-xs text-muted-foreground">
                            Visible to the client and ASR Tech. Do not include
                            passwords or access keys.
                        </p>
                        <FormFeedback
                            :errors="update.errors"
                            :saved="update.recentlySuccessful"
                        /><button
                            class="project-button"
                            :disabled="update.processing"
                        >
                            {{ update.processing ? 'Posting…' : 'Post update' }}
                        </button>
                    </form>
                    <p
                        v-if="!project.updates.length"
                        class="mt-5 text-sm text-muted-foreground"
                    >
                        Updates will appear here when there is news to share.
                    </p>
                    <ol class="mt-6 space-y-5">
                        <li
                            v-for="item in project.updates"
                            :key="item.id"
                            class="border-t border-border pt-5"
                        >
                            <p class="text-xs text-muted-foreground">
                                <strong class="text-foreground">{{
                                    item.author
                                }}</strong>
                                · {{ date(item.created_at) }}
                            </p>
                            <p
                                class="mt-3 text-sm leading-7 break-words whitespace-pre-wrap"
                            >
                                {{ item.body }}
                            </p>
                        </li>
                    </ol>
                </section>
            </div>
            <div class="min-w-0 space-y-7">
                <section
                    class="rounded-2xl border border-border bg-card p-5 sm:p-6"
                >
                    <h2 class="text-xl font-semibold">Approval requests</h2>
                    <p
                        v-if="!project.approvals.length"
                        class="mt-4 text-sm text-muted-foreground"
                    >
                        No approval requests yet. Decisions will be recorded
                        here.
                    </p>
                    <div class="mt-5 space-y-4">
                        <ApprovalCard
                            v-for="item in project.approvals"
                            :key="`${item.id}-${item.status}`"
                            :approval="item"
                            :base="base"
                            :admin="!!admin"
                            :closed="closed"
                        />
                    </div>
                    <details v-if="admin && !closed" class="mt-5">
                        <summary
                            class="cursor-pointer text-sm font-semibold text-primary"
                        >
                            Request client approval
                        </summary>
                        <form
                            class="mt-4 grid gap-3"
                            @submit.prevent="
                                approval.post(`${base}/approvals`, {
                                    preserveScroll: true,
                                    onSuccess: () => approval.reset(),
                                })
                            "
                        >
                            <label class="project-label"
                                >Request title<input
                                    v-model="approval.title"
                                    class="project-field"
                                    required
                                    maxlength="180" /></label
                            ><label class="project-label"
                                >What needs approval?<textarea
                                    v-model="approval.description"
                                    class="project-field"
                                    rows="4"
                                    required
                                    maxlength="10000"
                                />
                            </label>
                            <p class="text-xs text-muted-foreground">
                                Describe the exact deliverable and refer to
                                shared filenames where useful.
                            </p>
                            <FormFeedback
                                :errors="approval.errors"
                                :saved="approval.recentlySuccessful"
                            /><button
                                class="project-button"
                                :disabled="approval.processing"
                            >
                                {{
                                    approval.processing
                                        ? 'Creating…'
                                        : 'Create request'
                                }}
                            </button>
                        </form>
                    </details>
                </section>
                <section
                    class="rounded-2xl border border-border bg-card p-5 sm:p-6"
                >
                    <h2 class="text-xl font-semibold">Shared files</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Private to this client and ASR Tech support
                        administrators.
                    </p>
                    <form
                        v-if="!closed"
                        class="mt-5 grid gap-3"
                        @submit.prevent="submitFile"
                    >
                        <label class="project-label"
                            >Attach a file<input
                                ref="input"
                                type="file"
                                accept=".pdf,.txt,.png,.jpg,.jpeg,.webp,.zip"
                                class="project-field text-xs"
                                required
                                @change="attach"
                        /></label>
                        <p class="text-xs text-muted-foreground">
                            PDF, TXT, PNG, JPG, WebP or ZIP. Up to 10 MB. Do not
                            upload secrets or credentials.
                        </p>
                        <FormFeedback
                            :errors="upload.errors"
                            :saved="upload.recentlySuccessful"
                        /><progress
                            v-if="upload.progress"
                            :value="upload.progress.percentage"
                            max="100"
                            class="w-full"
                            aria-label="File upload progress"
                        /><button
                            class="project-button"
                            :disabled="upload.processing"
                        >
                            {{
                                upload.processing ? 'Uploading…' : 'Upload file'
                            }}
                        </button>
                    </form>
                    <p
                        v-if="!project.files.length"
                        class="mt-5 text-sm text-muted-foreground"
                    >
                        No files shared yet.
                    </p>
                    <ul class="mt-5 divide-y divide-border">
                        <li
                            v-for="file in project.files"
                            :key="file.id"
                            class="py-4"
                        >
                            <a
                                :href="`${base}/files/${file.id}`"
                                class="block text-sm font-semibold break-words text-primary underline underline-offset-4"
                                >{{ file.original_name }}</a
                            >
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ (file.size / 1024).toFixed(1) }} KB ·
                                {{ file.author }} · {{ date(file.created_at) }}
                            </p>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</template>
<style>
.project-workspace .project-label {
    display: grid;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
}
.project-workspace .project-field {
    width: 100%;
    min-width: 0;
    border: 1px solid var(--border);
    border-radius: 0.625rem;
    background: var(--background);
    padding: 0.7rem 0.8rem;
    font-weight: 400;
}
.project-workspace .project-field:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}
.project-workspace .project-button {
    justify-self: start;
    border-radius: 0.625rem;
    background: var(--primary);
    color: var(--primary-foreground);
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
}
.project-workspace .project-button:disabled {
    opacity: 0.6;
    cursor: wait;
}
</style>
