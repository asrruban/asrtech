<script setup lang="ts">
import { LoaderCircle, Sparkles } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
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
const props = defineProps(['seo', 'errors', 'aiContext']);
const emit = defineEmits(['update:seo']);
const generating = ref(false);
const aiError = ref('');
const update = (key, value) => {
    emit('update:seo', { ...props.seo, [key]: String(value) });
};
const eventValue = (event: Event): string =>
    (event.target as HTMLInputElement).value;
const generateSeo = async () => {
    if (!props.aiContext?.name?.trim()) {
        aiError.value = 'Enter a name before generating SEO.';

        return;
    }

    generating.value = true;
    aiError.value = '';

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        const response = await fetch('/admin/seo/generate', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(props.aiContext),
        });
        const payload: any = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                payload.errors?.ai?.[0] ||
                    payload.message ||
                    'SEO generation failed.',
            );
        }

        emit('update:seo', {
            ...props.seo,
            ...payload.seo,
            schema_json: payload.seo.schema_json
                ? JSON.stringify(payload.seo.schema_json, null, 2)
                : props.seo.schema_json,
        });
        toast.success('SEO fields generated. Review them before saving.');
    } catch (error) {
        aiError.value =
            error instanceof Error
                ? error.message
                : 'SEO generation failed. Please try again.';
    } finally {
        generating.value = false;
    }
};
</script>

<template>
    <Card>
        <CardHeader class="gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <CardTitle>Search engine optimization</CardTitle>
                <CardDescription class="mt-1">
                    Control search snippets, canonical URLs, social cards, and
                    structured data.
                </CardDescription>
            </div>
            <Button
                v-if="aiContext"
                type="button"
                variant="outline"
                :disabled="generating"
                @click="generateSeo"
            >
                <LoaderCircle v-if="generating" class="size-4 animate-spin" />
                <Sparkles v-else class="size-4" />
                {{ generating ? 'Generating…' : 'Generate SEO with ChatGPT' }}
            </Button>
        </CardHeader>
        <CardContent class="grid gap-5 md:grid-cols-2">
            <InputError
                v-if="aiError"
                class="md:col-span-2"
                :message="aiError"
            />
            <div class="space-y-2 md:col-span-2">
                <Label>Meta title</Label>
                <Input
                    :model-value="seo.meta_title"
                    maxlength="255"
                    placeholder="Leave blank to use the content title"
                    @update:model-value="update('meta_title', $event)"
                />
                <InputError :message="errors['seo.meta_title']" />
            </div>
            <div class="space-y-2 md:col-span-2">
                <Label>Meta description</Label>
                <textarea
                    :value="seo.meta_description"
                    rows="3"
                    maxlength="500"
                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    @input="update('meta_description', eventValue($event))"
                />
                <InputError :message="errors['seo.meta_description']" />
            </div>
            <div class="space-y-2 md:col-span-2">
                <Label>Keywords</Label>
                <Input
                    :model-value="seo.keywords"
                    placeholder="whmcs module, template, web development"
                    @update:model-value="update('keywords', $event)"
                />
                <InputError :message="errors['seo.keywords']" />
            </div>
            <div class="space-y-2">
                <Label>Canonical URL</Label>
                <Input
                    :model-value="seo.canonical_url"
                    type="url"
                    placeholder="https://example.com/page"
                    @update:model-value="update('canonical_url', $event)"
                />
                <InputError :message="errors['seo.canonical_url']" />
            </div>
            <div class="space-y-2">
                <Label>Robots</Label>
                <select
                    :value="seo.robots"
                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                    @change="update('robots', eventValue($event))"
                >
                    <option value="index,follow">Index, follow</option>
                    <option value="noindex,follow">No index, follow</option>
                    <option value="noindex,nofollow">
                        No index, no follow
                    </option>
                </select>
            </div>
            <div class="space-y-2">
                <Label>Open Graph title</Label>
                <Input
                    :model-value="seo.open_graph_title"
                    @update:model-value="update('open_graph_title', $event)"
                />
            </div>
            <div class="space-y-2">
                <Label>Open Graph image URL</Label>
                <Input
                    :model-value="seo.open_graph_image"
                    type="url"
                    @update:model-value="update('open_graph_image', $event)"
                />
                <InputError :message="errors['seo.open_graph_image']" />
            </div>
            <div class="space-y-2 md:col-span-2">
                <Label>Open Graph description</Label>
                <textarea
                    :value="seo.open_graph_description"
                    rows="2"
                    maxlength="500"
                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    @input="
                        update('open_graph_description', eventValue($event))
                    "
                />
            </div>
            <div class="space-y-2">
                <Label>Twitter card</Label>
                <select
                    :value="seo.twitter_card"
                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                    @change="update('twitter_card', eventValue($event))"
                >
                    <option value="summary_large_image">Large image</option>
                    <option value="summary">Summary</option>
                </select>
            </div>
            <div class="space-y-2 md:col-span-2">
                <Label>Schema JSON-LD</Label>
                <textarea
                    :value="seo.schema_json"
                    rows="6"
                    placeholder='{"@context":"https://schema.org","@type":"Product"}'
                    class="w-full rounded-md border bg-transparent px-3 py-2 font-mono text-xs"
                    @input="update('schema_json', eventValue($event))"
                />
                <InputError :message="errors['seo.schema_json']" />
            </div>
        </CardContent>
    </Card>
</template>
