<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Save, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export interface DepartmentField {
    id: number;
    name: string;
    type: string;
    description: string | null;
    validation: string | null;
    select_options: string | null;
    required: boolean;
    admin_only: boolean;
    sort_order: number;
}

const props = defineProps<{
    departmentId: number;
    field?: DepartmentField | null;
    fieldTypes: Record<string, string>;
}>();

const form = useForm({
    name: props.field?.name ?? '',
    type: props.field?.type ?? 'text',
    description: props.field?.description ?? '',
    validation: props.field?.validation ?? '',
    select_options: props.field?.select_options ?? '',
    required: props.field?.required ?? false,
    admin_only: props.field?.admin_only ?? false,
    sort_order: props.field?.sort_order ?? 0,
});

const submit = () => {
    const base = `/admin/support/departments/${props.departmentId}/fields`;

    if (props.field) {
        form.put(`${base}/${props.field.id}`, { preserveScroll: true });
    } else {
        form.post(base, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }
};

const remove = () => {
    if (props.field && confirm(`Delete custom field “${props.field.name}”?`)) {
        router.delete(
            `/admin/support/departments/${props.departmentId}/fields/${props.field.id}`,
            { preserveScroll: true },
        );
    }
};

const uid = (name: string) =>
    `field-${props.field?.id ?? 'new'}-${name}`;
</script>

<template>
    <form
        class="space-y-4 rounded-lg border p-4"
        @submit.prevent="submit"
    >
        <div class="grid gap-4 md:grid-cols-3">
            <div class="space-y-2">
                <Label :for="uid('name')">Field Name</Label>
                <Input :id="uid('name')" v-model="form.name" required />
                <InputError :message="form.errors.name" />
            </div>
            <div class="space-y-2">
                <Label :for="uid('type')">Field Type</Label>
                <select
                    :id="uid('type')"
                    v-model="form.type"
                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                >
                    <option
                        v-for="(label, value) in props.fieldTypes"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>
                <InputError :message="form.errors.type" />
            </div>
            <div class="space-y-2">
                <Label :for="uid('sort')">Display Order</Label>
                <Input
                    :id="uid('sort')"
                    v-model.number="form.sort_order"
                    type="number"
                    min="0"
                />
                <InputError :message="form.errors.sort_order" />
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <Label :for="uid('description')">Description</Label>
                <Input
                    :id="uid('description')"
                    v-model="form.description"
                    placeholder="Shown to the user under the field"
                />
                <InputError :message="form.errors.description" />
            </div>
            <div class="space-y-2">
                <Label :for="uid('validation')">Validation</Label>
                <Input
                    :id="uid('validation')"
                    v-model="form.validation"
                    placeholder="Optional regular expression, e.g. /^[0-9]+$/"
                />
                <InputError :message="form.errors.validation" />
            </div>
        </div>

        <div v-if="form.type === 'dropdown'" class="space-y-2">
            <Label :for="uid('options')">Select Options</Label>
            <textarea
                :id="uid('options')"
                v-model="form.select_options"
                rows="3"
                placeholder="One option per line"
                class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
            ></textarea>
            <InputError :message="form.errors.select_options" />
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input
                    v-model="form.required"
                    type="checkbox"
                    class="size-4 rounded"
                />
                Required Field
            </label>
            <label class="flex items-center gap-2 text-sm font-medium">
                <input
                    v-model="form.admin_only"
                    type="checkbox"
                    class="size-4 rounded"
                />
                Admin Only
            </label>

            <div class="ml-auto flex gap-2">
                <Button
                    v-if="props.field"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    @click="remove"
                >
                    <Trash2 class="size-4" /> Delete
                </Button>
                <Button type="submit" size="sm" :disabled="form.processing">
                    <component :is="props.field ? Save : Plus" class="size-4" />
                    {{ props.field ? 'Save Field' : 'Add Field' }}
                </Button>
            </div>
        </div>
    </form>
</template>
