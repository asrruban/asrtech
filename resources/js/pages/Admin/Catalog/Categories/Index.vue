<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
defineProps(['categories']);
const page = usePage();
const deleteError = computed(() => page.props.errors.category);
const editingId = ref(null);
const createForm = useForm({ name: '', description: '', status: true });
const editForm = useForm({ name: '', description: '', status: true });
const createCategory = () =>
    createForm.post('/admin/categories', {
        onSuccess: () => createForm.reset(),
    });
const startEdit = (category) => {
    editingId.value = category.id;
    editForm.name = category.name;
    editForm.description = category.description ?? '';
    editForm.status = category.status;
    editForm.clearErrors();
};
const updateCategory = (category) =>
    editForm.put(`/admin/categories/${category.id}`, {
        onSuccess: () => (editingId.value = null),
    });
const removeCategory = (category) => {
    if (confirm(`Delete category “${category.name}”?`)) {
        router.delete(`/admin/categories/${category.id}`);
    }
};
</script>

<template>
    <Head title="Categories" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Catalog</p>
            <h1 class="text-3xl font-semibold tracking-tight">Categories</h1>
            <p class="mt-1 text-muted-foreground">
                Organize products into top-level catalog sections.
            </p>
        </div>

        <Card>
            <CardHeader><CardTitle>Add category</CardTitle></CardHeader>
            <CardContent>
                <form
                    class="grid gap-4 lg:grid-cols-[1fr_2fr_auto]"
                    @submit.prevent="createCategory"
                >
                    <div class="space-y-2">
                        <Label for="category-name">Name</Label>
                        <Input
                            id="category-name"
                            v-model="createForm.name"
                            required
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="category-description">Description</Label>
                        <Input
                            id="category-description"
                            v-model="createForm.description"
                        />
                        <InputError :message="createForm.errors.description" />
                    </div>
                    <div class="flex items-end gap-3 pb-0.5">
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="createForm.status"
                                type="checkbox"
                                class="size-4 rounded"
                            />
                            Active
                        </label>
                        <Button type="submit" :disabled="createForm.processing">
                            <Plus class="size-4" /> Add
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <InputError :message="deleteError" />

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="categories.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No categories yet.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="category in categories"
                        :key="category.id"
                        class="p-4 sm:p-5"
                    >
                        <form
                            v-if="editingId === category.id"
                            class="grid gap-4 lg:grid-cols-[1fr_2fr_auto]"
                            @submit.prevent="updateCategory(category)"
                        >
                            <div class="space-y-2">
                                <Label>Name</Label>
                                <Input v-model="editForm.name" required />
                                <InputError :message="editForm.errors.name" />
                            </div>
                            <div class="space-y-2">
                                <Label>Description</Label>
                                <Input v-model="editForm.description" />
                            </div>
                            <div class="flex items-end gap-2">
                                <label class="flex items-center gap-2 text-sm">
                                    <input
                                        v-model="editForm.status"
                                        type="checkbox"
                                        class="size-4 rounded"
                                    />
                                    Active
                                </label>
                                <Button type="submit" size="sm">Save</Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    @click="editingId = null"
                                >
                                    <X class="size-4" />
                                </Button>
                            </div>
                        </form>

                        <div
                            v-else
                            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-medium">
                                        {{ category.name }}
                                    </p>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs"
                                        :class="
                                            category.status
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{
                                            category.status
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{
                                        category.description || 'No description'
                                    }}
                                </p>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    {{ category.groups_count }} groups ·
                                    {{ category.products_count }} products · /{{
                                        category.slug
                                    }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="startEdit(category)"
                                >
                                    <Pencil class="size-4" /> Edit
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="removeCategory(category)"
                                >
                                    <Trash2 class="size-4" /> Delete
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
