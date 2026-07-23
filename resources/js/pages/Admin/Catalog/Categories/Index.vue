<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SeoFields from '@/modules/admin/seo/components/SeoFields.vue';
import { emptySeo } from '@/modules/admin/seo/types';

defineProps(['categories']);
const page = usePage();
const deleteError = computed(() => page.props.errors.category);
const editingId = ref<number | null>(null);
const normalizeSeo = (seo) => ({
    ...emptySeo(),
    ...(seo ?? {}),
    schema_json: seo?.schema_json
        ? JSON.stringify(seo.schema_json, null, 2)
        : '',
});
const createForm = useForm({
    name: '',
    description: '',
    status: true,
    seo: emptySeo(),
});
const editForm = useForm({
    name: '',
    description: '',
    status: true,
    seo: emptySeo(),
});
const createCategory = () =>
    createForm.post('/admin/categories', {
        onSuccess: () => createForm.reset(),
    });
const startEdit = (category) => {
    editingId.value = category.id;
    editForm.name = category.name;
    editForm.description = category.description ?? '';
    editForm.status = category.status;
    editForm.seo = normalizeSeo(category.seo);
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
const aiContext = (form, canonicalUrl = '') => ({
    type: 'category',
    name: form.name,
    description: form.description || null,
    parent_name: null,
    canonical_url: canonicalUrl || null,
});
</script>

<template>
    <Head title="Categories" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Catalog</p>
            <h1 class="text-3xl font-semibold tracking-tight">Categories</h1>
            <p class="mt-1 text-muted-foreground">
                Create top-level catalog pages, organize their subcategories,
                and control search visibility.
            </p>
        </div>

        <form class="space-y-6" @submit.prevent="createCategory">
            <Card>
                <CardHeader><CardTitle>Add category</CardTitle></CardHeader>
                <CardContent class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="category-name">Name</Label>
                        <Input
                            id="category-name"
                            v-model="createForm.name"
                            required
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <label
                        class="flex items-center gap-2 self-end pb-2 text-sm font-medium"
                    >
                        <input
                            v-model="createForm.status"
                            type="checkbox"
                            class="size-4 rounded"
                        />
                        Active
                    </label>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="category-description">Description</Label>
                        <textarea
                            id="category-description"
                            v-model="createForm.description"
                            rows="4"
                            maxlength="5000"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        />
                        <InputError :message="createForm.errors.description" />
                    </div>
                </CardContent>
            </Card>

            <SeoFields
                v-model:seo="createForm.seo"
                :errors="createForm.errors"
                :ai-context="aiContext(createForm)"
            />

            <div class="flex justify-end">
                <Button type="submit" :disabled="createForm.processing">
                    <Plus class="size-4" /> Add category
                </Button>
            </div>
        </form>

        <InputError :message="deleteError" />

        <Card>
            <CardHeader><CardTitle>Existing categories</CardTitle></CardHeader>
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
                            class="space-y-5"
                            @submit.prevent="updateCategory(category)"
                        >
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label>Name</Label>
                                    <Input v-model="editForm.name" required />
                                    <InputError
                                        :message="editForm.errors.name"
                                    />
                                </div>
                                <label
                                    class="flex items-center gap-2 self-end pb-2 text-sm"
                                >
                                    <input
                                        v-model="editForm.status"
                                        type="checkbox"
                                        class="size-4 rounded"
                                    />
                                    Active
                                </label>
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
                            </div>

                            <SeoFields
                                v-model:seo="editForm.seo"
                                :errors="editForm.errors"
                                :ai-context="
                                    aiContext(
                                        editForm,
                                        editForm.seo.canonical_url,
                                    )
                                "
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
                                    {{ category.groups_count }} subcategories ·
                                    {{ category.products_count }} products ·
                                    /categories/{{ category.slug }}
                                </p>
                                <p
                                    v-if="category.seo?.meta_title"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    SEO: {{ category.seo.meta_title }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button as-child size="sm" variant="outline">
                                    <Link
                                        :href="`/categories/${category.slug}`"
                                        target="_blank"
                                    >
                                        <ExternalLink class="size-4" /> View
                                    </Link>
                                </Button>
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
