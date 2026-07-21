<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
defineProps(['categories', 'groups']);
const editingId = ref(null);
const createForm = useForm({
    category_id: 0,
    name: '',
    description: '',
    status: true,
});
const editForm = useForm({
    category_id: 0,
    name: '',
    description: '',
    status: true,
});
const createGroup = () =>
    createForm.post('/admin/groups', {
        onSuccess: () => createForm.reset(),
    });
const startEdit = (group) => {
    editingId.value = group.id;
    editForm.category_id = group.category_id;
    editForm.name = group.name;
    editForm.description = group.description ?? '';
    editForm.status = group.status;
    editForm.clearErrors();
};
const updateGroup = (group) =>
    editForm.put(`/admin/groups/${group.id}`, {
        onSuccess: () => (editingId.value = null),
    });
const removeGroup = (group) => {
    if (
        confirm(
            `Delete group “${group.name}”? Products will remain without a group.`,
        )
    ) {
        router.delete(`/admin/groups/${group.id}`);
    }
};
</script>

<template>
    <Head title="Groups" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Catalog</p>
            <h1 class="text-3xl font-semibold tracking-tight">Groups</h1>
            <p class="mt-1 text-muted-foreground">
                Create a second level of organization inside each category.
            </p>
        </div>

        <Card>
            <CardHeader><CardTitle>Add group</CardTitle></CardHeader>
            <CardContent>
                <form
                    class="grid gap-4 xl:grid-cols-[1fr_1fr_2fr_auto]"
                    @submit.prevent="createGroup"
                >
                    <div class="space-y-2">
                        <Label>Category</Label>
                        <select
                            v-model.number="createForm.category_id"
                            required
                            class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option :value="0" disabled>Select category</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.category_id" />
                    </div>
                    <div class="space-y-2">
                        <Label>Name</Label
                        ><Input v-model="createForm.name" required />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label>Description</Label
                        ><Input v-model="createForm.description" />
                    </div>
                    <div class="flex items-end gap-3 pb-0.5">
                        <label class="flex items-center gap-2 text-sm"
                            ><input
                                v-model="createForm.status"
                                type="checkbox"
                                class="size-4 rounded"
                            />
                            Active</label
                        >
                        <Button type="submit" :disabled="createForm.processing"
                            ><Plus class="size-4" /> Add</Button
                        >
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="groups.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No groups yet.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="group in groups"
                        :key="group.id"
                        class="p-4 sm:p-5"
                    >
                        <form
                            v-if="editingId === group.id"
                            class="grid gap-4 xl:grid-cols-[1fr_1fr_2fr_auto]"
                            @submit.prevent="updateGroup(group)"
                        >
                            <div class="space-y-2">
                                <Label>Category</Label>
                                <select
                                    v-model.number="editForm.category_id"
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                >
                                    <option
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Name</Label
                                ><Input v-model="editForm.name" required />
                            </div>
                            <div class="space-y-2">
                                <Label>Description</Label
                                ><Input v-model="editForm.description" />
                            </div>
                            <div class="flex items-end gap-2">
                                <label class="flex items-center gap-2 text-sm"
                                    ><input
                                        v-model="editForm.status"
                                        type="checkbox"
                                        class="size-4 rounded"
                                    />
                                    Active</label
                                >
                                <Button type="submit" size="sm">Save</Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    @click="editingId = null"
                                    ><X class="size-4"
                                /></Button>
                            </div>
                        </form>
                        <div
                            v-else
                            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-medium">{{ group.name }}</p>
                                    <span
                                        class="rounded-full bg-muted px-2 py-0.5 text-xs"
                                        >{{ group.category.name }}</span
                                    >
                                    <span
                                        v-if="!group.status"
                                        class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                        >Inactive</span
                                    >
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ group.description || 'No description' }}
                                </p>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    {{ group.products_count }} products · /{{
                                        group.slug
                                    }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="startEdit(group)"
                                    ><Pencil class="size-4" /> Edit</Button
                                >
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="removeGroup(group)"
                                    ><Trash2 class="size-4" /> Delete</Button
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
