<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps(['productTypes']);

const page = usePage();
const deleteError = computed(() => page.props.errors.product_type);
const editingId = ref<number | null>(null);
const createForm = useForm({
    name: '',
    slug: '',
    description: '',
    status: true,
});
const editForm = useForm({
    name: '',
    slug: '',
    description: '',
    status: true,
});

const createProductType = () =>
    createForm.post('/admin/product-types', {
        onSuccess: () => createForm.reset(),
    });

const startEdit = (productType) => {
    editingId.value = productType.id;
    editForm.name = productType.name;
    editForm.slug = productType.slug;
    editForm.description = productType.description ?? '';
    editForm.status = productType.status;
    editForm.clearErrors();
};

const updateProductType = (productType) =>
    editForm.put(`/admin/product-types/${productType.id}`, {
        onSuccess: () => (editingId.value = null),
    });

const removeProductType = (productType) => {
    if (confirm(`Delete product type “${productType.name}”?`)) {
        router.delete(`/admin/product-types/${productType.id}`);
    }
};
</script>

<template>
    <Head title="Product Types" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Catalog</p>
            <h1 class="text-3xl font-semibold tracking-tight">Product Types</h1>
            <p class="mt-1 text-muted-foreground">
                Manage the types used by products and their public URL segment.
            </p>
        </div>

        <Card>
            <CardHeader><CardTitle>Add product type</CardTitle></CardHeader>
            <CardContent>
                <form
                    class="grid gap-4 lg:grid-cols-[1fr_1fr_2fr_auto]"
                    @submit.prevent="createProductType"
                >
                    <div class="space-y-2">
                        <Label for="product-type-name">Name</Label>
                        <Input
                            id="product-type-name"
                            v-model="createForm.name"
                            placeholder="WHMCS Modules"
                            required
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="product-type-slug">URL slug</Label>
                        <Input
                            id="product-type-slug"
                            v-model="createForm.slug"
                            placeholder="whmcs"
                        />
                        <InputError :message="createForm.errors.slug" />
                    </div>
                    <div class="space-y-2">
                        <Label for="product-type-description"
                            >Description</Label
                        >
                        <Input
                            id="product-type-description"
                            v-model="createForm.description"
                        />
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
                    v-if="productTypes.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No product types yet.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="productType in productTypes"
                        :key="productType.id"
                        class="p-4 sm:p-5"
                    >
                        <form
                            v-if="editingId === productType.id"
                            class="grid gap-4 lg:grid-cols-[1fr_1fr_2fr_auto]"
                            @submit.prevent="updateProductType(productType)"
                        >
                            <div class="space-y-2">
                                <Label>Name</Label>
                                <Input v-model="editForm.name" required />
                                <InputError :message="editForm.errors.name" />
                            </div>
                            <div class="space-y-2">
                                <Label>URL slug</Label>
                                <Input v-model="editForm.slug" required />
                                <InputError :message="editForm.errors.slug" />
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
                                        {{ productType.name }}
                                    </p>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs"
                                        :class="
                                            productType.status
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{
                                            productType.status
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{
                                        productType.description ||
                                        'No description'
                                    }}
                                </p>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    {{ productType.products_count }} products ·
                                    /products/{{ productType.slug }}/… · key:
                                    {{ productType.key }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="startEdit(productType)"
                                >
                                    <Pencil class="size-4" /> Edit
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="removeProductType(productType)"
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
