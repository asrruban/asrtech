<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { date, label } from '@/modules/projects/types';
interface Inquiry {
    id: number;
    name: string;
    email: string;
    service: string;
    message: string;
    status: string;
    created_at: string;
    follow_up_at: string | null;
    assignee: { name: string } | null;
    quote: { quote_number: string } | null;
    user: { name: string } | null;
}
const props = defineProps<{
    inquiries: {
        data: Inquiry[];
        prev_page_url: string | null;
        next_page_url: string | null;
        current_page: number;
        total: number;
    };
    serviceNames: Record<string, string>;
    statuses: string[];
    filters: { status?: string; follow_up?: string };
}>();
const filters = useForm({
    status: props.filters.status ?? '',
    follow_up: props.filters.follow_up ?? '',
});
const pending = ref<number | null>(null);
const statusError = ref('');
function update(inquiry: Inquiry, event: Event) {
    const select = event.target as HTMLSelectElement;
    pending.value = inquiry.id;
    statusError.value = '';
    router.patch(
        `/admin/inquiries/${inquiry.id}`,
        { status: select.value },
        {
            preserveScroll: true,
            onError: () => {
                select.value = inquiry.status;
                statusError.value =
                    'The status could not be updated. Please try again.';
            },
            onHttpException: () => {
                select.value = inquiry.status;
                statusError.value =
                    'The update was not confirmed. Refresh and try again.';

                return false;
            },
            onNetworkError: () => {
                select.value = inquiry.status;
                statusError.value =
                    'Connection lost. Refresh to confirm the current status.';

                return false;
            },
            onCancel: () => {
                select.value = inquiry.status;
            },
            onFinish: () => (pending.value = null),
        },
    );
}
</script>
<template>
    <Head title="Project inquiries" />
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p
                    class="text-xs font-semibold tracking-widest text-primary uppercase"
                >
                    Support / Website contact
                </p>
                <h1
                    class="mt-3 text-3xl font-semibold tracking-tight text-foreground"
                >
                    Project inquiries
                </h1>
                <p class="mt-3 text-sm text-muted-foreground">
                    Assign follow-ups, connect clients and move agreed work into
                    a project. {{ inquiries.total }} matching inquiries.
                </p>
            </div>
            <Link
                href="/admin/projects"
                class="rounded-lg border bg-card px-4 py-2 text-sm font-semibold"
                >Project workspaces</Link
            >
        </div>
        <form
            class="mb-6 flex flex-wrap items-end gap-4 rounded-xl border bg-card p-4"
            @submit.prevent="filters.get('/admin/inquiries')"
        >
            <label class="grid gap-2 text-xs font-semibold"
                >Status<select
                    v-model="filters.status"
                    class="rounded-lg border bg-background p-2.5 text-sm font-normal"
                >
                    <option value="">All statuses</option>
                    <option
                        v-for="status in statuses"
                        :key="status"
                        :value="status"
                    >
                        {{ label(status) }}
                    </option>
                </select></label
            ><label class="grid gap-2 text-xs font-semibold"
                >Follow-up<select
                    v-model="filters.follow_up"
                    class="rounded-lg border bg-background p-2.5 text-sm font-normal"
                >
                    <option value="">Any date</option>
                    <option value="due">Due today or overdue</option>
                </select></label
            ><button
                class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground"
                :disabled="filters.processing"
            >
                Apply filters</button
            ><Link
                href="/admin/inquiries"
                class="px-3 py-2.5 text-sm text-primary"
                >Clear</Link
            >
        </form>
        <p
            v-if="statusError"
            role="alert"
            class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-800"
        >
            {{ statusError }}
        </p>
        <div
            v-if="!inquiries.data.length"
            class="rounded-xl border bg-card p-12 text-center text-sm text-muted-foreground"
        >
            No inquiries match these filters.
        </div>
        <div class="space-y-4">
            <article
                v-for="inquiry in inquiries.data"
                :key="inquiry.id"
                class="overflow-hidden rounded-xl border bg-card"
            >
                <div
                    class="flex flex-wrap items-start justify-between gap-4 bg-muted/30 p-5"
                >
                    <div class="min-w-0">
                        <Link
                            :href="`/admin/inquiries/${inquiry.id}`"
                            class="text-lg font-semibold text-primary"
                            >{{ inquiry.name }} →</Link
                        >
                        <p class="mt-2 text-sm break-all text-muted-foreground">
                            {{ inquiry.email }}
                        </p>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{
                                serviceNames[inquiry.service] ?? inquiry.service
                            }}
                            · {{ date(inquiry.created_at) }}
                        </p>
                    </div>
                    <label class="grid gap-2 text-xs font-semibold"
                        >Review status<select
                            :value="inquiry.status"
                            :disabled="pending === inquiry.id"
                            class="rounded-lg border bg-background px-3 py-2 text-sm font-normal"
                            @change="update(inquiry, $event)"
                        >
                            <option
                                v-for="status in statuses"
                                :key="status"
                                :value="status"
                            >
                                {{ label(status) }}
                            </option>
                        </select></label
                    >
                </div>
                <div class="p-5">
                    <p
                        class="line-clamp-3 text-sm leading-7 break-words whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ inquiry.message }}
                    </p>
                    <div
                        class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-muted-foreground"
                    >
                        <span
                            >Assigned:
                            {{ inquiry.assignee?.name ?? 'Unassigned' }}</span
                        ><span>Follow-up: {{ date(inquiry.follow_up_at) }}</span
                        ><span v-if="inquiry.user"
                            >Client: {{ inquiry.user.name }}</span
                        ><span v-if="inquiry.quote">{{
                            inquiry.quote.quote_number
                        }}</span>
                    </div>
                    <Link
                        :href="`/admin/inquiries/${inquiry.id}`"
                        class="mt-4 inline-block text-sm font-semibold text-primary"
                        >Manage inquiry →</Link
                    >
                </div>
            </article>
        </div>
        <nav
            v-if="inquiries.prev_page_url || inquiries.next_page_url"
            aria-label="Inquiry pagination"
            class="mt-7 flex items-center justify-between gap-4 text-sm"
        >
            <Link
                v-if="inquiries.prev_page_url"
                :href="inquiries.prev_page_url"
                class="rounded-lg border bg-card px-4 py-2"
                >Previous</Link
            ><span>Page {{ inquiries.current_page }}</span
            ><Link
                v-if="inquiries.next_page_url"
                :href="inquiries.next_page_url"
                class="rounded-lg border bg-card px-4 py-2"
                >Next</Link
            >
        </nav>
    </div>
</template>
