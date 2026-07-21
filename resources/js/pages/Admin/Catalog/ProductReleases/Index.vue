<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ExternalLink,
    FileArchive,
    Pencil,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
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

const props = defineProps(['product', 'releases']);

const localDateTime = (value?: string | null) => {
    if (value) {
        return value.slice(0, 16);
    }

    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

    return date.toISOString().slice(0, 16);
};

const createForm = useForm({
    version: '',
    title: '',
    release_notes: '',
    released_at: localDateTime(),
    available_until: '',
    download_limit: '' as string | number,
    status: true,
    release_file: null as File | null,
});
const editingId = ref<number | null>(null);
const editForm = useForm({
    version: '',
    title: '',
    release_notes: '',
    released_at: '',
    available_until: '',
    download_limit: '' as string | number,
    status: true,
    release_file: null as File | null,
});

const selectFile = (form, event: Event) => {
    form.release_file = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const createRelease = () =>
    createForm.post('/admin/products/' + props.product.id + '/releases', {
        forceFormData: true,
        onSuccess: () => {
            createForm.reset();
            createForm.released_at = localDateTime();
        },
    });

const startEdit = (release) => {
    editingId.value = release.id;
    editForm.version = release.version;
    editForm.title = release.title ?? '';
    editForm.release_notes = release.release_notes ?? '';
    editForm.released_at = localDateTime(release.released_at);
    editForm.available_until = release.available_until
        ? localDateTime(release.available_until)
        : '';
    editForm.download_limit = release.download_limit ?? '';
    editForm.status = release.status;
    editForm.release_file = null;
    editForm.clearErrors();
};

const updateRelease = (release) => {
    editForm
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(
            '/admin/products/' + props.product.id + '/releases/' + release.id,
            {
                forceFormData: true,
                onSuccess: () => (editingId.value = null),
            },
        );
};

const deleteRelease = (release) => {
    if (
        confirm(
            'Delete release ' +
                release.version +
                '? Its package and download history will be removed.',
        )
    ) {
        router.delete(
            '/admin/products/' + props.product.id + '/releases/' + release.id,
        );
    }
};

const fileSize = (bytes: number) => {
    if (bytes < 1024) {
        return bytes + ' B';
    }

    if (bytes < 1024 ** 2) {
        return (bytes / 1024).toFixed(1) + ' KB';
    }

    if (bytes < 1024 ** 3) {
        return (bytes / 1024 ** 2).toFixed(1) + ' MB';
    }

    return (bytes / 1024 ** 3).toFixed(1) + ' GB';
};

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
</script>

<template>
    <Head :title="product.name + ' releases'" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"
        >
            <div>
                <p class="text-sm font-medium text-primary">Catalog</p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Product releases
                </h1>
                <p class="mt-1 text-muted-foreground">
                    Upload private, versioned packages for {{ product.name }}.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button as-child variant="outline">
                    <Link :href="'/admin/products/' + product.id + '/edit'">
                        <ArrowLeft class="size-4" /> Edit product
                    </Link>
                </Button>
                <Button as-child variant="outline">
                    <a :href="product.url" target="_blank" rel="noreferrer">
                        View storefront <ExternalLink class="size-4" />
                    </a>
                </Button>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Upload release</CardTitle>
                <CardDescription>
                    Packages are stored privately and are accessible only to
                    eligible license owners.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-5" @submit.prevent="createRelease">
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        <div class="space-y-2">
                            <Label for="release-version">Version</Label>
                            <Input
                                id="release-version"
                                v-model="createForm.version"
                                placeholder="2.5.0"
                                required
                            />
                            <InputError :message="createForm.errors.version" />
                        </div>
                        <div class="space-y-2">
                            <Label for="release-title">Title</Label>
                            <Input
                                id="release-title"
                                v-model="createForm.title"
                                placeholder="Performance update"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="release-date">Release date</Label>
                            <Input
                                id="release-date"
                                v-model="createForm.released_at"
                                type="datetime-local"
                                required
                            />
                            <InputError
                                :message="createForm.errors.released_at"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="release-expiry">Available until</Label>
                            <Input
                                id="release-expiry"
                                v-model="createForm.available_until"
                                type="datetime-local"
                            />
                            <InputError
                                :message="createForm.errors.available_until"
                            />
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-[1fr_220px]">
                        <div class="space-y-2">
                            <Label for="release-package">Release package</Label>
                            <Input
                                id="release-package"
                                type="file"
                                required
                                @change="selectFile(createForm, $event)"
                            />
                            <p class="text-xs text-muted-foreground">
                                Maximum 500 MB. Packages remain outside the
                                public web root.
                            </p>
                            <InputError
                                :message="createForm.errors.release_file"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="release-limit">
                                Downloads per license
                            </Label>
                            <Input
                                id="release-limit"
                                v-model="createForm.download_limit"
                                type="number"
                                min="1"
                                placeholder="Unlimited"
                            />
                            <InputError
                                :message="createForm.errors.download_limit"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="release-notes">Release notes</Label>
                        <textarea
                            id="release-notes"
                            v-model="createForm.release_notes"
                            rows="5"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="createForm.status"
                                type="checkbox"
                                class="size-4 rounded"
                            />
                            Published
                        </label>
                        <Button type="submit" :disabled="createForm.processing">
                            <Plus class="size-4" />
                            {{
                                createForm.processing
                                    ? 'Uploading…'
                                    : 'Upload release'
                            }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Release history</CardTitle>
                <CardDescription>
                    {{ releases.length }} release records.
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <div
                    v-if="releases.length === 0"
                    class="p-12 text-center text-sm text-muted-foreground"
                >
                    No release packages uploaded yet.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="release in releases"
                        :key="release.id"
                        class="p-5 sm:p-6"
                    >
                        <form
                            v-if="editingId === release.id"
                            class="space-y-5"
                            @submit.prevent="updateRelease(release)"
                        >
                            <div
                                class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                            >
                                <div class="space-y-2">
                                    <Label>Version</Label>
                                    <Input
                                        v-model="editForm.version"
                                        required
                                    />
                                    <InputError
                                        :message="editForm.errors.version"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label>Title</Label>
                                    <Input v-model="editForm.title" />
                                </div>
                                <div class="space-y-2">
                                    <Label>Release date</Label>
                                    <Input
                                        v-model="editForm.released_at"
                                        type="datetime-local"
                                        required
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label>Available until</Label>
                                    <Input
                                        v-model="editForm.available_until"
                                        type="datetime-local"
                                    />
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-[1fr_220px]">
                                <div class="space-y-2">
                                    <Label>Replace package</Label>
                                    <Input
                                        type="file"
                                        @change="selectFile(editForm, $event)"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Leave blank to keep
                                        {{ release.original_filename }}.
                                    </p>
                                    <InputError
                                        :message="editForm.errors.release_file"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label>Downloads per license</Label>
                                    <Input
                                        v-model="editForm.download_limit"
                                        type="number"
                                        min="1"
                                        placeholder="Unlimited"
                                    />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label>Release notes</Label>
                                <textarea
                                    v-model="editForm.release_notes"
                                    rows="5"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                />
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <label class="flex items-center gap-2 text-sm">
                                    <input
                                        v-model="editForm.status"
                                        type="checkbox"
                                        class="size-4 rounded"
                                    />
                                    Published
                                </label>
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="editForm.processing"
                                >
                                    Save release
                                </Button>
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
                            class="flex flex-col justify-between gap-5 lg:flex-row lg:items-start"
                        >
                            <div class="flex min-w-0 gap-4">
                                <span
                                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <FileArchive class="size-6" />
                                </span>
                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h2 class="font-semibold">
                                            v{{ release.version }}
                                        </h2>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="
                                                release.status
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-muted text-muted-foreground'
                                            "
                                        >
                                            {{
                                                release.status
                                                    ? 'Published'
                                                    : 'Draft'
                                            }}
                                        </span>
                                    </div>
                                    <p
                                        v-if="release.title"
                                        class="mt-1 text-sm font-medium"
                                    >
                                        {{ release.title }}
                                    </p>
                                    <p
                                        class="mt-2 text-xs text-muted-foreground"
                                    >
                                        {{ release.original_filename }} ·
                                        {{ fileSize(release.file_size) }} ·
                                        Released
                                        {{ formatDate(release.released_at) }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ release.downloads_count }} total
                                        downloads ·
                                        {{
                                            release.download_limit
                                                ? release.download_limit +
                                                  ' per license'
                                                : 'Unlimited per license'
                                        }}
                                        <template
                                            v-if="release.available_until"
                                        >
                                            · Expires
                                            {{
                                                formatDate(
                                                    release.available_until,
                                                )
                                            }}
                                        </template>
                                    </p>
                                    <p
                                        class="mt-2 max-w-3xl font-mono text-[11px] break-all text-muted-foreground"
                                    >
                                        SHA-256:
                                        {{ release.checksum_sha256 }}
                                    </p>
                                    <p
                                        v-if="release.release_notes"
                                        class="mt-3 max-w-3xl text-sm whitespace-pre-line text-muted-foreground"
                                    >
                                        {{ release.release_notes }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="startEdit(release)"
                                >
                                    <Pencil class="size-4" /> Edit
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="deleteRelease(release)"
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
