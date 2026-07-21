<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Send } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface DepartmentOption {
    id: number;
    name: string;
    description: string | null;
}

const props = defineProps<{
    departments: DepartmentOption[];
    selectedDepartmentId: number | null;
    priorities: Record<string, string>;
}>();

const form = useForm({
    ticket_department_id:
        props.selectedDepartmentId ?? props.departments[0]?.id ?? null,
    subject: '',
    priority: 'medium',
    message: '',
});

const submit = () => form.post('/client-area/tickets');

const selectedDepartment = () =>
    props.departments.find((d) => d.id === form.ticket_department_id);
</script>

<template>
    <SeoHead title="Open a ticket" description="Contact our support team." />

    <ClientAreaHero title="Open a Ticket" />

    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <Link
            href="/client-area/tickets"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to your tickets
        </Link>

        <div
            v-if="props.departments.length === 0"
            class="mt-6 rounded-2xl border bg-card p-10 text-center text-sm text-muted-foreground"
        >
            Support is not available right now — please email us instead.
        </div>

        <form
            v-else
            class="mt-6 space-y-5 rounded-2xl border bg-card p-6 shadow-sm sm:p-8"
            @submit.prevent="submit"
        >
            <div class="space-y-2">
                <label class="text-sm font-semibold" for="department">
                    Department
                </label>
                <select
                    id="department"
                    v-model="form.ticket_department_id"
                    class="h-10 w-full rounded-lg border bg-transparent px-3 text-sm"
                >
                    <option
                        v-for="department in props.departments"
                        :key="department.id"
                        :value="department.id"
                    >
                        {{ department.name }}
                    </option>
                </select>
                <p
                    v-if="selectedDepartment()?.description"
                    class="text-xs text-muted-foreground"
                >
                    {{ selectedDepartment()?.description }}
                </p>
                <InputError :message="form.errors.ticket_department_id" />
            </div>

            <div class="grid gap-5 sm:grid-cols-[minmax(0,1fr)_160px]">
                <div class="space-y-2">
                    <label class="text-sm font-semibold" for="subject">
                        Subject
                    </label>
                    <input
                        id="subject"
                        v-model="form.subject"
                        type="text"
                        required
                        class="h-10 w-full rounded-lg border bg-transparent px-3 text-sm"
                    />
                    <InputError :message="form.errors.subject" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-semibold" for="priority">
                        Priority
                    </label>
                    <select
                        id="priority"
                        v-model="form.priority"
                        class="h-10 w-full rounded-lg border bg-transparent px-3 text-sm"
                    >
                        <option
                            v-for="(label, value) in props.priorities"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.priority" />
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-semibold" for="message">
                    Message
                </label>
                <textarea
                    id="message"
                    v-model="form.message"
                    rows="8"
                    required
                    placeholder="Describe your issue in as much detail as possible…"
                    class="w-full rounded-lg border bg-transparent px-3 py-2 text-sm"
                ></textarea>
                <InputError :message="form.errors.message" />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4fb250] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#3f9f40] disabled:opacity-60"
            >
                <Send class="size-4" /> Submit Ticket
            </button>
        </form>
    </section>
</template>
