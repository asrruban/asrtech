<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
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
import SeoFields from '@/modules/admin/seo/components/SeoFields.vue';
import { emptySeo } from '@/modules/admin/seo/types';
const props = defineProps(['managedPage']);
const savedSeo = props.managedPage?.seo;
const form = useForm({
    title: props.managedPage?.title ?? '',
    slug: props.managedPage?.slug ?? '',
    excerpt: props.managedPage?.excerpt ?? '',
    content: props.managedPage?.content ?? '',
    template: props.managedPage?.template ?? 'default',
    status: props.managedPage?.status ?? true,
    sort_order: props.managedPage?.sort_order ?? 0,
    seo: savedSeo
        ? {
              ...emptySeo(),
              ...savedSeo,
              schema_json: savedSeo.schema_json
                  ? JSON.stringify(savedSeo.schema_json, null, 2)
                  : '',
          }
        : emptySeo(),
});
const submit = () => {
    if (props.managedPage) {
        form.put(`/admin/pages/${props.managedPage.id}`);
    } else {
        form.post('/admin/pages');
    }
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <Card>
            <CardHeader>
                <CardTitle>Page content</CardTitle>
                <CardDescription>
                    Create an SEO-ready public page without changing code.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-5 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                    <Label>Title</Label>
                    <Input v-model="form.title" required />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="space-y-2">
                    <Label>Slug</Label>
                    <Input
                        v-model="form.slug"
                        placeholder="Generated from title when empty"
                    />
                    <InputError :message="form.errors.slug" />
                </div>
                <div class="space-y-2">
                    <Label>Template</Label>
                    <select
                        v-model="form.template"
                        class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option value="default">Default</option>
                        <option value="wide">Wide</option>
                        <option value="legal">Legal</option>
                    </select>
                    <InputError :message="form.errors.template" />
                </div>
                <div class="space-y-2 md:col-span-2">
                    <Label>Excerpt</Label>
                    <textarea
                        v-model="form.excerpt"
                        rows="3"
                        maxlength="500"
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                    <InputError :message="form.errors.excerpt" />
                </div>
                <div class="space-y-2 md:col-span-2">
                    <Label>Content</Label>
                    <textarea
                        v-model="form.content"
                        rows="16"
                        placeholder="Write page content. Plain text and line breaks are supported."
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                    <InputError :message="form.errors.content" />
                </div>
                <div class="space-y-2">
                    <Label>Navigation order</Label>
                    <Input
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        max="10000"
                    />
                    <InputError :message="form.errors.sort_order" />
                </div>
                <label
                    class="flex items-center gap-2 self-end pb-2 text-sm font-medium"
                >
                    <input
                        v-model="form.status"
                        type="checkbox"
                        class="size-4 rounded"
                    />
                    Published
                </label>
            </CardContent>
        </Card>

        <SeoFields v-model:seo="form.seo" :errors="form.errors" />

        <div class="flex justify-end gap-3">
            <Button as-child type="button" variant="outline">
                <Link href="/admin/pages">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Save class="size-4" /> Save page
            </Button>
        </div>
    </form>
</template>
