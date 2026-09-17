<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormFeedback from '@/modules/projects/components/FormFeedback.vue';
import { date, label } from '@/modules/projects/types';
const props = defineProps<{
    projects: {
        data: {
            id: number;
            title: string;
            status: string;
            target_date: string | null;
            user: { name: string; email: string };
            milestones_count: number;
        }[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    clients: { id: number; name: string; email: string }[];
    inquiries: { id: number; name: string; user_id: number }[];
    quotes: { id: number; quote_number: string; user_id: number }[];
}>();
const form = useForm({
    user_id: '',
    project_inquiry_id: '',
    quote_id: '',
    title: '',
    description: '',
    target_date: '',
});
const inquiries = computed(() =>
    props.inquiries.filter((i) => i.user_id === Number(form.user_id)),
);
const quotes = computed(() =>
    props.quotes.filter((q) => q.user_id === Number(form.user_id)),
);
</script>
<template>
    <Head title="Client projects" />
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <header class="mb-7 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p
                    class="text-xs font-semibold tracking-widest text-primary uppercase"
                >
                    Client delivery
                </p>
                <h1 class="mt-3 text-3xl font-semibold">Project workspaces</h1>
                <p class="mt-3 text-sm text-muted-foreground">
                    Manage scope, milestones, files and client approvals.
                    {{ projects.total }} total.
                </p>
            </div>
            <Link
                href="/admin/inquiries"
                class="rounded-lg border px-4 py-2 text-sm font-semibold"
                >Project inquiries</Link
            >
        </header>
        <details class="mb-7 rounded-xl border border-border bg-card p-5">
            <summary class="cursor-pointer font-semibold text-primary">
                Create project workspace
            </summary>
            <form
                class="mt-5 grid gap-4"
                @submit.prevent="form.post('/admin/projects')"
            >
                <p class="text-sm text-muted-foreground">
                    The selected client can immediately see this workspace. Use
                    agreed scope; creating a project does not send email or
                    issue an invoice.
                </p>
                <label class="grid gap-2 text-sm font-semibold"
                    >Client<select
                        v-model="form.user_id"
                        class="rounded-lg border bg-background p-3 font-normal"
                        required
                        @change="
                            form.project_inquiry_id = '';
                            form.quote_id = '';
                        "
                    >
                        <option value="">Select an existing client</option>
                        <option
                            v-for="client in clients"
                            :key="client.id"
                            :value="client.id"
                        >
                            {{ client.name }} — {{ client.email }}
                        </option>
                    </select></label
                >
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold"
                        >Source inquiry (optional)<select
                            v-model="form.project_inquiry_id"
                            class="rounded-lg border bg-background p-3 font-normal"
                        >
                            <option value="">None</option>
                            <option
                                v-for="inquiry in inquiries"
                                :key="inquiry.id"
                                :value="inquiry.id"
                            >
                                #{{ inquiry.id }} — {{ inquiry.name }}
                            </option>
                        </select></label
                    ><label class="grid gap-2 text-sm font-semibold"
                        >Accepted quote (optional)<select
                            v-model="form.quote_id"
                            class="rounded-lg border bg-background p-3 font-normal"
                        >
                            <option value="">None</option>
                            <option
                                v-for="quote in quotes"
                                :key="quote.id"
                                :value="quote.id"
                            >
                                {{ quote.quote_number }}
                            </option>
                        </select></label
                    >
                </div>
                <label class="grid gap-2 text-sm font-semibold"
                    >Project title<input
                        v-model="form.title"
                        required
                        maxlength="180"
                        class="rounded-lg border bg-background p-3 font-normal" /></label
                ><label class="grid gap-2 text-sm font-semibold"
                    >Agreed scope<textarea
                        v-model="form.description"
                        required
                        maxlength="10000"
                        rows="4"
                        class="rounded-lg border bg-background p-3 font-normal"
                    /></label
                ><label class="grid gap-2 text-sm font-semibold"
                    >Target date (optional)<input
                        v-model="form.target_date"
                        type="date"
                        class="rounded-lg border bg-background p-3 font-normal" /></label
                ><FormFeedback :errors="form.errors" /><button
                    class="justify-self-start rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Creating…' : 'Create project' }}
                </button>
            </form>
        </details>
        <p
            v-if="!projects.data.length"
            class="rounded-xl border bg-card p-10 text-center text-muted-foreground"
        >
            No projects yet. Create a workspace for an existing client above.
        </p>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="project in projects.data"
                :key="project.id"
                :href="`/admin/projects/${project.id}`"
                class="min-w-0 rounded-xl border border-border bg-card p-6 hover:border-primary"
                ><p class="text-xs font-semibold text-primary capitalize">
                    {{ label(project.status) }}
                </p>
                <h2 class="mt-3 text-xl font-semibold break-words">
                    {{ project.title }}
                </h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{ project.user.name }}
                </p>
                <p class="mt-5 text-xs text-muted-foreground">
                    {{ project.milestones_count }} milestones · Target
                    {{ date(project.target_date) }}
                </p>
                <p class="mt-5 text-sm font-semibold text-primary">
                    Open workspace →
                </p></Link
            >
        </div>
        <nav
            v-if="projects.prev_page_url || projects.next_page_url"
            aria-label="Project pagination"
            class="mt-7 flex justify-between gap-4"
        >
            <Link
                v-if="projects.prev_page_url"
                :href="projects.prev_page_url"
                class="rounded-lg border px-4 py-2"
                >Previous</Link
            ><Link
                v-if="projects.next_page_url"
                :href="projects.next_page_url"
                class="rounded-lg border px-4 py-2"
                >Next</Link
            >
        </nav>
    </div>
</template>
