<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { date, label } from '../types';
import type { ProjectMilestone } from '../types';
import FormFeedback from './FormFeedback.vue';
const props = defineProps<{
    milestone: ProjectMilestone;
    base: string;
    editable: boolean;
}>();
const editing = ref(false);
const form = useForm({
    title: props.milestone.title,
    description: props.milestone.description ?? '',
    status: props.milestone.status,
    due_date: props.milestone.due_date ?? '',
});
function save() {
    form.patch(`${props.base}/milestones/${props.milestone.id}`, {
        preserveScroll: true,
        onSuccess: () => (editing.value = false),
    });
}
</script>
<template>
    <article class="rounded-xl border border-border p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold">{{ milestone.title }}</h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    Target {{ date(milestone.due_date) }}
                </p>
            </div>
            <span
                class="rounded-full bg-muted px-3 py-1 text-xs font-semibold capitalize"
                >{{ label(milestone.status) }}</span
            >
        </div>
        <p
            v-if="milestone.description"
            class="mt-3 text-sm leading-6 break-words whitespace-pre-wrap text-muted-foreground"
        >
            {{ milestone.description }}
        </p>
        <button
            v-if="editable"
            type="button"
            class="mt-3 text-sm font-semibold text-primary underline underline-offset-4"
            :aria-expanded="editing"
            @click="editing = !editing"
        >
            {{ editing ? 'Cancel editing' : 'Edit milestone' }}
        </button>
        <form v-if="editing" class="mt-4 grid gap-3" @submit.prevent="save">
            <label class="project-label"
                >Title<input
                    v-model="form.title"
                    class="project-field"
                    required
                    maxlength="180"
            /></label>
            <label class="project-label"
                >Scope<textarea
                    v-model="form.description"
                    class="project-field"
                    rows="3"
                    maxlength="5000"
                />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="project-label"
                    >Status<select v-model="form.status" class="project-field">
                        <option
                            v-for="status in [
                                'planned',
                                'in_progress',
                                'blocked',
                                'completed',
                            ]"
                            :key="status"
                            :value="status"
                        >
                            {{ label(status) }}
                        </option>
                    </select></label
                ><label class="project-label"
                    >Target date<input
                        v-model="form.due_date"
                        type="date"
                        class="project-field"
                /></label>
            </div>
            <FormFeedback :errors="form.errors" /><button
                class="project-button"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Saving…' : 'Save milestone' }}
            </button>
        </form>
    </article>
</template>
