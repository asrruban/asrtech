<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ProductRichContentFields from '@/modules/admin/catalog/components/ProductRichContentFields.vue';
import SectionSidebar from '@/modules/admin/components/SectionSidebar.vue';
import SeoFields from '@/modules/admin/seo/components/SeoFields.vue';
import { emptySeo } from '@/modules/admin/seo/types';
const props = defineProps([
    'categories',
    'groups',
    'productTypes',
    'billingCycles',
    'currency',
    'product',
]);
const existingSeo = props.product?.seo;
const activeSection = ref('details');
const sections = [
    { value: 'details', label: 'Product details' },
    { value: 'media', label: 'Media gallery' },
    { value: 'pricing', label: 'Pricing and licenses' },
    { value: 'features', label: 'Feature groups' },
    { value: 'requirements', label: 'Requirements' },
    { value: 'changelog', label: 'Changelog' },
    { value: 'addons', label: 'Optional services' },
    { value: 'reviews', label: 'Customer reviews' },
    { value: 'documentation', label: 'Documentation' },
    { value: 'seo', label: 'Search engine optimization' },
];
const seo = existingSeo
    ? {
          ...emptySeo(),
          ...existingSeo,
          schema_json: existingSeo.schema_json
              ? JSON.stringify(existingSeo.schema_json, null, 2)
              : '',
      }
    : emptySeo();
const form = useForm({
    category_id: props.product?.category_id ?? props.categories[0]?.id ?? 0,
    group_id: props.product?.group_id ?? '',
    name: props.product?.name ?? '',
    slug: props.product?.slug ?? '',
    sku: props.product?.sku ?? '',
    type: props.product?.type ?? 'whmcs_module',
    badge: props.product?.badge ?? '',
    version: props.product?.version ?? '',
    release_date: props.product?.release_date ?? '',
    compatibility: props.product?.compatibility ?? '',
    php_compatibility: props.product?.php_compatibility ?? '',
    short_description: props.product?.short_description ?? '',
    description: props.product?.description ?? '',
    featured_image: props.product?.featured_image ?? '',
    featured_image_upload: null as File | null,
    demo_url: props.product?.demo_url ?? '',
    documentation_url: props.product?.documentation_url ?? '',
    documentation_title: props.product?.documentation_title ?? '',
    purchase_url: props.product?.purchase_url ?? '',
    trial_url: props.product?.trial_url ?? '',
    documentation_content: props.product?.documentation_content ?? '',
    documentation_meta_title: props.product?.documentation_meta_title ?? '',
    documentation_meta_description:
        props.product?.documentation_meta_description ?? '',
    documentation_keywords: props.product?.documentation_keywords ?? '',
    documentation_robots: props.product?.documentation_robots ?? 'index,follow',
    documentation_open_graph_image:
        props.product?.documentation_open_graph_image ?? '',
    gallery: props.product?.gallery ?? [],
    feature_groups: (props.product?.feature_groups ?? []).map((group) => ({
        ...group,
        features: (group.features ?? []).join('\n'),
    })),
    requirements: props.product?.requirements ?? [],
    changelog: (props.product?.changelog ?? []).map((release) => ({
        ...release,
        notes: (release.notes ?? []).join('\n'),
    })),
    addons: props.product?.addons ?? [],
    reviews: props.product?.reviews ?? [],
    status: props.product?.status ?? true,
    featured: props.product?.featured ?? false,
    has_free_trial: props.product?.has_free_trial ?? false,
    prices: props.billingCycles.map((cycle, index) => {
        const existing = props.product?.prices.find(
            (price) => price.billing_cycle === cycle,
        );

        return {
            billing_cycle: cycle,
            name: existing?.name ?? '',
            description: existing?.description ?? '',
            currency: existing?.currency ?? props.currency,
            price: existing?.price ?? '0.00',
            sale_price: existing?.sale_price ?? '',
            setup_fee: existing?.setup_fee ?? '0.00',
            purchase_url: existing?.purchase_url ?? '',
            features: (existing?.features ?? []).join('\n'),
            featured: existing?.featured ?? false,
            enabled: existing?.enabled ?? index === 0,
        };
    }),
    seo,
});
const filteredGroups = computed(() =>
    props.groups.filter((group) => group.category_id === form.category_id),
);
const slugify = (value: string) =>
    value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
const selectedProductType = computed(() =>
    props.productTypes.find((type) => type.key === form.type),
);
const publicPath = computed(
    () =>
        `/products/${selectedProductType.value?.slug ?? 'type'}/${form.slug || slugify(form.name) || 'product-name'}`,
);
const uploadedImagePreview = ref('');
const featuredImagePreview = computed(
    () => uploadedImagePreview.value || form.featured_image,
);
const selectFeaturedImage = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (uploadedImagePreview.value) {
        URL.revokeObjectURL(uploadedImagePreview.value);
    }

    form.featured_image_upload = file;
    uploadedImagePreview.value = file ? URL.createObjectURL(file) : '';
};

onBeforeUnmount(() => {
    if (uploadedImagePreview.value) {
        URL.revokeObjectURL(uploadedImagePreview.value);
    }
});
watch(
    () => form.category_id,
    () => {
        if (
            form.group_id !== '' &&
            !filteredGroups.value.some((group) => group.id === form.group_id)
        ) {
            form.group_id = '';
        }
    },
);
const submit = () => {
    form.transform((data) => ({
        ...data,
        ...(props.product ? { _method: 'put' } : {}),
        group_id: data.group_id === '' ? null : data.group_id,
        prices: data.prices.filter((price) => price.enabled),
    }));

    const options = {
        onError: (errors) => {
            const fields = Object.keys(errors);

            if (fields.some((field) => field.startsWith('seo.'))) {
                activeSection.value = 'seo';
            } else if (fields.some((field) => field.startsWith('prices'))) {
                activeSection.value = 'pricing';
            } else if (fields.some((field) => field.startsWith('gallery'))) {
                activeSection.value = 'media';
            } else if (
                fields.some((field) => field.startsWith('feature_groups'))
            ) {
                activeSection.value = 'features';
            } else if (
                fields.some((field) => field.startsWith('requirements'))
            ) {
                activeSection.value = 'requirements';
            } else if (fields.some((field) => field.startsWith('changelog'))) {
                activeSection.value = 'changelog';
            } else if (fields.some((field) => field.startsWith('addons'))) {
                activeSection.value = 'addons';
            } else if (fields.some((field) => field.startsWith('reviews'))) {
                activeSection.value = 'reviews';
            } else if (
                fields.some((field) => field.startsWith('documentation_'))
            ) {
                activeSection.value = 'documentation';
            } else {
                activeSection.value = 'details';
            }
        },
    };

    if (props.product) {
        form.post(`/admin/products/${props.product.id}`, {
            ...options,
            forceFormData: true,
        });
    } else {
        form.post('/admin/products', { ...options, forceFormData: true });
    }
};
const label = (value) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
</script>

<template>
    <form @submit.prevent="submit">
        <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-8">
            <SectionSidebar
                v-model="activeSection"
                title="Product"
                :items="sections"
            />

            <div class="min-w-0 space-y-6">
                <Card v-show="activeSection === 'details'">
                    <CardHeader>
                        <CardTitle>Product details</CardTitle>
                        <CardDescription>
                            Describe the WHMCS module, template, license, or
                            development service.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="product-name">Name</Label>
                            <Input
                                id="product-name"
                                v-model="form.name"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="product-slug">Product slug</Label>
                            <Input
                                id="product-slug"
                                v-model="form.slug"
                                :placeholder="
                                    slugify(form.name) || 'product-name'
                                "
                            />
                            <p class="text-xs text-muted-foreground">
                                Public URL: {{ publicPath }}
                            </p>
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-category">Category</Label>
                            <select
                                id="product-category"
                                v-model.number="form.category_id"
                                required
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
                            <InputError :message="form.errors.category_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-group">Group</Label>
                            <select
                                id="product-group"
                                v-model.number="form.group_id"
                                class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">No group</option>
                                <option
                                    v-for="group in filteredGroups"
                                    :key="group.id"
                                    :value="group.id"
                                >
                                    {{ group.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.group_id" />
                        </div>

                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <Label for="product-type">Product type</Label>
                                <Link
                                    href="/admin/product-types"
                                    class="text-xs font-medium text-primary hover:underline"
                                >
                                    Manage types
                                </Link>
                            </div>
                            <select
                                id="product-type"
                                v-model="form.type"
                                required
                                class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option
                                    v-for="type in productTypes"
                                    :key="type.key"
                                    :value="type.key"
                                >
                                    {{ type.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-sku">SKU</Label>
                            <Input
                                id="product-sku"
                                v-model="form.sku"
                                placeholder="Optional unique code"
                            />
                            <InputError :message="form.errors.sku" />
                        </div>

                        <div class="space-y-2">
                            <Label>Product badge</Label>
                            <Input
                                v-model="form.badge"
                                placeholder="Popular, New, WHMCS 9 Ready"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>Current version</Label>
                            <Input v-model="form.version" placeholder="1.0.0" />
                        </div>

                        <div class="space-y-2">
                            <Label>Release date</Label>
                            <Input v-model="form.release_date" type="date" />
                        </div>

                        <div class="space-y-2">
                            <Label>WHMCS or platform compatibility</Label>
                            <Input
                                v-model="form.compatibility"
                                placeholder="WHMCS 9.x back to 8.10"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>PHP compatibility</Label>
                            <Input
                                v-model="form.php_compatibility"
                                placeholder="PHP 8.4 back to 8.2"
                            />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="short-description"
                                >Short description</Label
                            >
                            <Input
                                id="short-description"
                                v-model="form.short_description"
                                maxlength="500"
                            />
                            <InputError
                                :message="form.errors.short_description"
                            />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="product-description"
                                >Full description</Label
                            >
                            <textarea
                                id="product-description"
                                v-model="form.description"
                                rows="8"
                                class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div
                            class="grid gap-5 rounded-xl border bg-muted/20 p-4 md:col-span-2 md:grid-cols-[minmax(0,1fr)_220px]"
                        >
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="featured-image-url"
                                        >Featured image URL</Label
                                    >
                                    <Input
                                        id="featured-image-url"
                                        v-model="form.featured_image"
                                        type="text"
                                        placeholder="https://… or /images/product.jpg"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        This image is always shown first on the
                                        public product page.
                                    </p>
                                    <InputError
                                        :message="form.errors.featured_image"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="featured-image-upload"
                                        >Upload featured image</Label
                                    >
                                    <Input
                                        id="featured-image-upload"
                                        type="file"
                                        accept="image/*"
                                        @change="selectFeaturedImage"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        JPG, PNG, GIF, or WebP up to 5 MB. An
                                        upload replaces the URL when saved.
                                    </p>
                                    <InputError
                                        :message="
                                            form.errors.featured_image_upload
                                        "
                                    />
                                </div>
                            </div>
                            <div
                                class="flex min-h-36 items-center justify-center overflow-hidden rounded-lg border bg-background"
                            >
                                <img
                                    v-if="featuredImagePreview"
                                    :src="featuredImagePreview"
                                    alt="Featured image preview"
                                    class="h-40 w-full object-contain"
                                />
                                <span
                                    v-else
                                    class="px-4 text-center text-xs text-muted-foreground"
                                >
                                    Featured image preview
                                </span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label>Demo URL</Label>
                            <Input v-model="form.demo_url" type="url" />
                        </div>
                        <div class="space-y-2">
                            <Label>Documentation URL</Label>
                            <Input
                                v-model="form.documentation_url"
                                type="url"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Purchase URL</Label>
                            <Input v-model="form.purchase_url" type="url" />
                        </div>
                        <div class="space-y-2">
                            <Label>Trial URL</Label>
                            <Input v-model="form.trial_url" type="url" />
                        </div>

                        <div class="flex flex-wrap gap-6 md:col-span-2">
                            <label
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <input
                                    v-model="form.status"
                                    type="checkbox"
                                    class="size-4 rounded"
                                />
                                Product is active
                            </label>
                            <label
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <input
                                    v-model="form.featured"
                                    type="checkbox"
                                    class="size-4 rounded"
                                />
                                Feature on homepage
                            </label>
                            <label
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <input
                                    v-model="form.has_free_trial"
                                    type="checkbox"
                                    class="size-4 rounded"
                                />
                                Enable 7 days Free Trial
                            </label>
                        </div>
                    </CardContent>
                </Card>

                <Card v-show="activeSection === 'pricing'">
                    <CardHeader>
                        <CardTitle>Pricing</CardTitle>
                        <CardDescription>
                            Enable any combination of one-time, monthly, and
                            yearly billing.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 lg:grid-cols-3">
                        <div
                            v-for="(price, index) in form.prices"
                            :key="price.billing_cycle"
                            class="space-y-4 rounded-xl border p-4"
                        >
                            <label
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="font-semibold">
                                    {{ label(price.billing_cycle) }}
                                </span>
                                <input
                                    v-model="price.enabled"
                                    type="checkbox"
                                    class="size-4 rounded"
                                />
                            </label>
                            <div class="space-y-2">
                                <Label>Plan name</Label>
                                <Input
                                    v-model="price.name"
                                    :placeholder="`${label(price.billing_cycle)} license`"
                                    :disabled="!price.enabled"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Currency</Label>
                                <Input
                                    v-model="price.currency"
                                    maxlength="3"
                                    :disabled="!price.enabled"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Regular price</Label>
                                <Input
                                    v-model="price.price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!price.enabled"
                                />
                                <InputError
                                    :message="
                                        form.errors[`prices.${index}.price`]
                                    "
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Sale price</Label>
                                <Input
                                    v-model="price.sale_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="Optional"
                                    :disabled="!price.enabled"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Setup fee</Label>
                                <Input
                                    v-model="price.setup_fee"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!price.enabled"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Purchase URL</Label>
                                <Input
                                    v-model="price.purchase_url"
                                    type="url"
                                    :disabled="!price.enabled"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Plan description</Label>
                                <textarea
                                    v-model="price.description"
                                    rows="3"
                                    :disabled="!price.enabled"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm disabled:opacity-50"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Included features</Label>
                                <textarea
                                    v-model="price.features"
                                    rows="6"
                                    placeholder="One feature per line"
                                    :disabled="!price.enabled"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm disabled:opacity-50"
                                />
                            </div>
                            <label
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <input
                                    v-model="price.featured"
                                    type="checkbox"
                                    class="size-4 rounded"
                                    :disabled="!price.enabled"
                                />
                                Highlight this plan
                            </label>
                        </div>
                        <InputError :message="form.errors.prices" />
                    </CardContent>
                </Card>

                <ProductRichContentFields
                    :form="form"
                    :active-section="activeSection"
                    :currency="currency"
                />

                <SeoFields
                    v-show="activeSection === 'seo'"
                    :seo="form.seo"
                    :errors="form.errors"
                    @update:seo="form.seo = $event"
                />

                <div class="flex justify-end gap-3">
                    <Button as-child type="button" variant="outline">
                        <Link href="/admin/products">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" />
                        {{ product ? 'Save product' : 'Create product' }}
                    </Button>
                </div>
            </div>
        </div>
    </form>
</template>
