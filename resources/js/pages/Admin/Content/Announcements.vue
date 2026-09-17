<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Megaphone, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Announcement = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    published: boolean;
    published_at: string | null;
    created_at: string | null;
};

const props = defineProps<{ announcements: Announcement[] }>();

const editorOpen = ref(false);
const editing = ref<Announcement | null>(null);

const form = useForm({
    title: '',
    excerpt: '',
    body: '',
    published: false,
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    editorOpen.value = true;
};

const openEdit = (announcement: Announcement) => {
    editing.value = announcement;
    form.title = announcement.title;
    form.excerpt = announcement.excerpt ?? '';
    form.body = announcement.body;
    form.published = announcement.published;
    editorOpen.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(`/admin/announcements/${editing.value.id}`, {
            onSuccess: () => (editorOpen.value = false),
        });
    } else {
        form.post('/admin/announcements', {
            onSuccess: () => (editorOpen.value = false),
        });
    }
};

const remove = (announcement: Announcement) => {
    if (!confirm(`Delete "${announcement.title}"?`)) {
        return;
    }

    useForm({}).delete(`/admin/announcements/${announcement.id}`);
};

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleDateString() : '—';
</script>

<template>
    <Head title="Announcements" />

    <div class="space-y-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Announcements</h1>
                <p class="text-sm text-muted-foreground">
                    News and updates shown on the public site.
                </p>
            </div>
            <Dialog v-model:open="editorOpen">
                <DialogTrigger as-child>
                    <Button @click="openCreate"
                        ><Plus class="size-4" /> New announcement</Button
                    >
                </DialogTrigger>
                <DialogContent class="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{{
                            editing ? 'Edit announcement' : 'New announcement'
                        }}</DialogTitle>
                        <DialogDescription>
                            Published announcements appear at /announcements.
                        </DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-1.5">
                            <Label for="ann-title">Title</Label>
                            <Input
                                id="ann-title"
                                v-model="form.title"
                                required
                            />
                            <InputError :message="form.errors.title" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="ann-excerpt">Excerpt (optional)</Label>
                            <Input id="ann-excerpt" v-model="form.excerpt" />
                            <InputError :message="form.errors.excerpt" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="ann-body">Body</Label>
                            <textarea
                                id="ann-body"
                                v-model="form.body"
                                rows="8"
                                required
                                class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                            />
                            <InputError :message="form.errors.body" />
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="form.published"
                                type="checkbox"
                                class="size-4 rounded"
                            />
                            Published
                        </label>
                        <DialogFooter>
                            <Button type="submit" :disabled="form.processing">
                                {{
                                    editing
                                        ? 'Save changes'
                                        : 'Create announcement'
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="props.announcements.length === 0"
                    class="flex flex-col items-center gap-3 p-10 text-center text-sm text-muted-foreground"
                >
                    <Megaphone class="size-8 text-muted-foreground/50" />
                    No announcements yet.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="announcement in props.announcements"
                        :key="announcement.id"
                        class="flex flex-wrap items-center gap-4 px-5 py-4"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-semibold">
                                    {{ announcement.title }}
                                </p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="
                                        announcement.published
                                            ? 'bg-emerald-500/10 text-emerald-500'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{
                                        announcement.published
                                            ? 'Published'
                                            : 'Draft'
                                    }}
                                </span>
                            </div>
                            <p
                                class="mt-0.5 truncate text-xs text-muted-foreground"
                            >
                                /announcements/{{ announcement.slug }} ·
                                {{
                                    formatDate(
                                        announcement.published_at ??
                                            announcement.created_at,
                                    )
                                }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openEdit(announcement)"
                            >
                                <Pencil class="size-4" /> Edit
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="remove(announcement)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
