<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Copy, Eye, Lock, Save, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
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

interface Template {
    id: number;
    name: string;
    slug: string;
    category: string;
    subject: string;
    body: string;
    enabled: boolean;
    is_system: boolean;
}

const props = defineProps<{
    template: Template;
    categories: Record<string, string>;
    mergeFields: string[];
}>();

const form = useForm({
    name: props.template.name,
    category: props.template.category,
    subject: props.template.subject,
    body: props.template.body,
    enabled: props.template.enabled,
});

const showPreview = ref(false);
const copiedTag = ref<string | null>(null);

const mergeTags = props.mergeFields.map((field) => `{{${field}}}`);

const submit = () =>
    form.put(`/admin/settings/emailtemplates/${props.template.id}`);

const copyTag = async (tag: string) => {
    try {
        await navigator.clipboard.writeText(tag);
        copiedTag.value = tag;
        setTimeout(() => (copiedTag.value = null), 1500);
    } catch {
        // Clipboard unavailable (non-secure context) — ignore.
    }
};

const removeTemplate = () => {
    if (confirm(`Delete email template “${props.template.name}”?`)) {
        router.delete(`/admin/settings/emailtemplates/${props.template.id}`);
    }
};
</script>

<template>
    <Head :title="`Edit template — ${props.template.name}`" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <Link
                href="/admin/settings/emailtemplates"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Email templates
            </Link>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-semibold tracking-tight">
                    {{ props.template.name }}
                </h1>
                <span
                    v-if="props.template.is_system"
                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground"
                >
                    <Lock class="size-3" /> System template
                </span>
            </div>
            <p class="mt-1 text-muted-foreground">
                Sent as
                <code class="rounded bg-muted px-1.5 py-0.5 text-xs">{{
                    props.template.slug
                }}</code>
                — edit the subject and HTML body below.
            </p>
        </div>

        <form
            class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]"
            @submit.prevent="submit"
        >
            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Template content</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name">Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    :disabled="props.template.is_system"
                                />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="space-y-2">
                                <Label for="category">Category</Label>
                                <select
                                    id="category"
                                    v-model="form.category"
                                    :disabled="props.template.is_system"
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option
                                        v-for="(label, value) in props.categories"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.category" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="subject">Subject</Label>
                            <Input id="subject" v-model="form.subject" />
                            <InputError :message="form.errors.subject" />
                        </div>
                        <div class="space-y-2">
                            <Label for="body">HTML body</Label>
                            <textarea
                                id="body"
                                v-model="form.body"
                                rows="18"
                                spellcheck="false"
                                class="w-full rounded-md border bg-transparent px-3 py-2 font-mono text-xs leading-relaxed"
                            ></textarea>
                            <InputError :message="form.errors.body" />
                            <p class="text-xs text-muted-foreground">
                                The body is wrapped in your email layout
                                (header, footer, and signature from General
                                Configuration).
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="showPreview">
                    <CardHeader>
                        <CardTitle>Preview</CardTitle>
                        <CardDescription>
                            Merge tags are shown as-is; they are replaced when
                            the email is sent.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="rounded-lg border bg-white p-6 text-black"
                            v-html="form.body"
                        ></div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Status</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input
                                v-model="form.enabled"
                                type="checkbox"
                                class="size-4 rounded"
                            />
                            Enabled
                        </label>
                        <p class="text-xs text-muted-foreground">
                            Disabled system templates fall back to the built-in
                            email design.
                        </p>
                        <InputError :message="form.errors.enabled" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Merge fields</CardTitle>
                        <CardDescription>
                            Click a tag to copy it, then paste it into the
                            subject or body.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in mergeTags"
                                :key="tag"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-md border bg-muted/50 px-2 py-1 font-mono text-xs transition hover:bg-muted"
                                @click="copyTag(tag)"
                            >
                                <Copy class="size-3" />
                                {{ copiedTag === tag ? 'Copied!' : tag }}
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col gap-2">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" /> Save template
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        @click="showPreview = !showPreview"
                    >
                        <Eye class="size-4" />
                        {{ showPreview ? 'Hide preview' : 'Show preview' }}
                    </Button>
                    <Button
                        v-if="!props.template.is_system"
                        type="button"
                        variant="outline"
                        class="text-destructive hover:text-destructive"
                        @click="removeTemplate"
                    >
                        <Trash2 class="size-4" /> Delete template
                    </Button>
                    <p
                        v-if="form.wasSuccessful"
                        class="text-center text-sm font-medium text-emerald-600"
                    >
                        Template saved.
                    </p>
                </div>
            </div>
        </form>
    </div>
</template>
