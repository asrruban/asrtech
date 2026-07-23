<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SeoFields from '@/modules/admin/seo/components/SeoFields.vue';
import { emptySeo } from '@/modules/admin/seo/types';

const props = defineProps(['categories', 'groups']);
const editingId = ref<number | null>(null);
const normalizeSeo = (seo) => ({
    ...emptySeo(),
    ...(seo ?? {}),
    schema_json: seo?.schema_json
        ? JSON.stringify(seo.schema_json, null, 2)
        : '',
});
const createForm = useForm({
    category_id: props.categories[0]?.id ?? 0,
    name: '',
    description: '',
    status: true,
    seo: emptySeo(),
});
const editForm = useForm({
    category_id: 0,
    name: '',
    description: '',
    status: true,
    seo: emptySeo(),
});
const categoryName = (categoryId) =>
    props.categories.find((category) => category.id === categoryId)?.name ?? '';
const aiContext = (form) => ({
    type: 'subcategory',
    name: form.name,
    description: form.description || null,
    parent_name: categoryName(form.category_id) || null,
    canonical_url: form.seo.canonical_url || null,
});
const createSubcategory = () =>
    createForm.post('/admin/subcategories', {
        onSuccess: () => createForm.reset(),
    });
const startEdit = (group) => {
    editingId.value = group.id;
    editForm.category_id = group.category_id;
    editForm.name = group.name;
    editForm.description = group.description ?? '';
    editForm.status = group.status;
    editForm.seo = normalizeSeo(group.seo);
    editForm.clearErrors();
};
const updateSubcategory = (group) =>
    editForm.put(`/admin/subcategories/${group.id}`, {
        onSuccess: () => (editingId.value = null),
    });
const removeSubcategory = (group) => {
    if (
        confirm(
            `Delete subcategory “${group.name}”? Products will remain in the parent category.`,
        )
    ) {
        router.delete(`/admin/subcategories/${group.id}`);
    }
};
</script>

<template>
    <Head title="Subcategories" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Catalog</p>
            <h1 class="text-3xl font-semibold tracking-tight">Subcategories</h1>
            <p class="mt-1 text-muted-foreground">
                Create a second level inside each category and optimize its
                public landing page.
            </p>
        </div>

        <form class="space-y-6" @submit.prevent="createSubcategory">
            <Card>
                <CardHeader><CardTitle>Add subcategory</CardTitle></CardHeader>
                <CardContent class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="subcategory-category">Category</Label>
                        <select
                            id="subcategory-category"
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
                        <Label for="subcategory-name">Name</Label>
                        <Input
                            id="subcategory-name"
                            v-model="createForm.name"
                            required
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="subcategory-description">
                            Description
                        </Label>
                        <textarea
                            id="subcategory-description"
                            v-model="createForm.description"
                            rows="4"
                            maxlength="5000"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        />
                        <InputError :message="createForm.errors.description" />
                    </div>
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input
                            v-model="createForm.status"
                            type="checkbox"
                            class="size-4 rounded"
                        />
                        Active
                    </label>
                </CardContent>
            </Card>

            <SeoFields
                v-model:seo="createForm.seo"
                :errors="createForm.errors"
                :ai-context="aiContext(createForm)"
            />

            <div class="flex justify-end">
                <Button type="submit" :disabled="createForm.processing">
                    <Plus class="size-4" /> Add subcategory
                </Button>
            </div>
        </form>

        <Card>
            <CardHeader
                ><CardTitle>Existing subcategories</CardTitle></CardHeader
            >
            <CardContent class="p-0">
                <div
                    v-if="groups.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No subcategories yet.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="group in groups"
                        :key="group.id"
                        class="p-4 sm:p-5"
                    >
                        <form
                            v-if="editingId === group.id"
                            class="space-y-5"
                            @submit.prevent="updateSubcategory(group)"
                        >
                            <div class="grid gap-4 md:grid-cols-2">
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
                                    <InputError
                                        :message="editForm.errors.category_id"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label>Name</Label>
                                    <Input v-model="editForm.name" required />
                                    <InputError
                                        :message="editForm.errors.name"
                                    />
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <Label>Description</Label>
                                    <textarea
                                        v-model="editForm.description"
                                        rows="4"
                                        maxlength="5000"
                                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                    />
                                    <InputError
                                        :message="editForm.errors.description"
                                    />
                                </div>
                                <label class="flex items-center gap-2 text-sm">
                                    <input
                                        v-model="editForm.status"
                                        type="checkbox"
                                        class="size-4 rounded"
                                    />
                                    Active
                                </label>
                            </div>

                            <SeoFields
                                v-model:seo="editForm.seo"
                                :errors="editForm.errors"
                                :ai-context="aiContext(editForm)"
                            />

                            <div class="flex justify-end gap-2">
                                <Button type="submit" size="sm">Save</Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    @click="editingId = null"
                                >
                                    <X class="size-4" /> Cancel
                                </Button>
                            </div>
                        </form>

                        <div
                            v-else
                            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium">{{ group.name }}</p>
                                    <span
                                        class="rounded-full bg-muted px-2 py-0.5 text-xs"
                                    >
                                        {{ group.category.name }}
                                    </span>
                                    <span
                                        v-if="!group.status"
                                        class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                    >
                                        Inactive
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ group.description || 'No description' }}
                                </p>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    {{ group.products_count }} products ·
                                    /categories/{{ group.category.slug }}/{{
                                        group.slug
                                    }}
                                </p>
                                <p
                                    v-if="group.seo?.meta_title"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    SEO: {{ group.seo.meta_title }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button as-child size="sm" variant="outline">
                                    <Link
                                        :href="`/categories/${group.category.slug}/${group.slug}`"
                                        target="_blank"
                                    >
                                        <ExternalLink class="size-4" /> View
                                    </Link>
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="startEdit(group)"
                                >
                                    <Pencil class="size-4" /> Edit
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="removeSubcategory(group)"
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
