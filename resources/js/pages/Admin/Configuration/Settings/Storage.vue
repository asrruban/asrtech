<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Cloud,
    FolderTree,
    HardDrive,
    Save,
    Server,
    Zap,
} from '@lucide/vue';
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

interface StorageSettings {
    storage_driver: string;
    storage_s3_key: string | null;
    storage_s3_secret_configured: boolean;
    storage_s3_region: string | null;
    storage_s3_bucket: string | null;
    storage_s3_url: string | null;
    storage_r2_account_id: string | null;
    storage_r2_key: string | null;
    storage_r2_secret_configured: boolean;
    storage_r2_bucket: string | null;
    storage_r2_url: string | null;
    storage_b2_key_id: string | null;
    storage_b2_key_configured: boolean;
    storage_b2_region: string | null;
    storage_b2_bucket: string | null;
    storage_b2_url: string | null;
    storage_path_branding: string;
    storage_path_tickets: string;
    storage_path_products: string;
}

const props = defineProps<{
    drivers: Record<string, string>;
    settings: StorageSettings;
}>();

const driverMeta: Record<
    string,
    { icon: typeof HardDrive; description: string }
> = {
    local: {
        icon: HardDrive,
        description:
            'Files are stored on this server under storage/app/public and served from /storage.',
    },
    s3: {
        icon: Cloud,
        description:
            'Amazon Web Services S3 bucket — provide IAM credentials with read/write access to the bucket.',
    },
    r2: {
        icon: Zap,
        description:
            'Cloudflare R2 (S3-compatible, no egress fees) — use an R2 API token with Object Read & Write.',
    },
    b2: {
        icon: Server,
        description:
            'Backblaze B2 cloud storage via its S3-compatible endpoint — use an application key scoped to the bucket.',
    },
};

const form = useForm({
    storage_driver: props.settings.storage_driver,
    storage_s3_key: props.settings.storage_s3_key ?? '',
    storage_s3_secret: '',
    storage_s3_region: props.settings.storage_s3_region ?? '',
    storage_s3_bucket: props.settings.storage_s3_bucket ?? '',
    storage_s3_url: props.settings.storage_s3_url ?? '',
    storage_r2_account_id: props.settings.storage_r2_account_id ?? '',
    storage_r2_key: props.settings.storage_r2_key ?? '',
    storage_r2_secret: '',
    storage_r2_bucket: props.settings.storage_r2_bucket ?? '',
    storage_r2_url: props.settings.storage_r2_url ?? '',
    storage_b2_key_id: props.settings.storage_b2_key_id ?? '',
    storage_b2_key: '',
    storage_b2_region: props.settings.storage_b2_region ?? '',
    storage_b2_bucket: props.settings.storage_b2_bucket ?? '',
    storage_b2_url: props.settings.storage_b2_url ?? '',
    storage_path_branding: props.settings.storage_path_branding,
    storage_path_tickets: props.settings.storage_path_tickets,
    storage_path_products: props.settings.storage_path_products,
});

const submit = () => form.put('/admin/settings/storage');

const secretPlaceholder = (configured: boolean) =>
    configured ? 'Configured — leave blank to keep it' : '';
</script>

<template>
    <Head title="Storage settings" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Configuration</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                Storage settings
            </h1>
            <p class="mt-1 text-muted-foreground">
                Choose where uploaded files are stored — branding images,
                support ticket attachments, and product images. Cloud drivers
                are tested with a connection probe when you save.
            </p>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <Card>
                <CardHeader>
                    <CardTitle>Storage driver</CardTitle>
                    <CardDescription>
                        Uploads go to the selected driver; existing files stay
                        where they were uploaded.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <InputError :message="form.errors.storage_driver" />

                    <label
                        v-for="(label, key) in props.drivers"
                        :key="key"
                        class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition"
                        :class="
                            form.storage_driver === key
                                ? 'border-primary bg-primary/5'
                                : 'hover:bg-muted/40'
                        "
                    >
                        <input
                            v-model="form.storage_driver"
                            type="radio"
                            :value="key"
                            class="mt-1 size-4"
                        />
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2 font-semibold">
                                <component
                                    :is="driverMeta[key]?.icon ?? HardDrive"
                                    class="size-4 text-muted-foreground"
                                />
                                {{ label }}
                            </span>
                            <span
                                class="mt-0.5 block text-sm text-muted-foreground"
                            >
                                {{ driverMeta[key]?.description }}
                            </span>

                            <span
                                v-if="form.storage_driver === key && key === 's3'"
                                class="mt-4 grid gap-4 md:grid-cols-2"
                            >
                                <span class="space-y-2">
                                    <Label for="s3-key">Access key ID</Label>
                                    <Input
                                        id="s3-key"
                                        v-model="form.storage_s3_key"
                                    />
                                    <InputError
                                        :message="form.errors.storage_s3_key"
                                    />
                                </span>
                                <span class="space-y-2">
                                    <Label for="s3-secret">
                                        Secret access key
                                    </Label>
                                    <Input
                                        id="s3-secret"
                                        v-model="form.storage_s3_secret"
                                        type="password"
                                        autocomplete="off"
                                        :placeholder="
                                            secretPlaceholder(
                                                props.settings
                                                    .storage_s3_secret_configured,
                                            )
                                        "
                                    />
                                    <InputError
                                        :message="
                                            form.errors.storage_s3_secret
                                        "
                                    />
                                </span>
                                <span class="space-y-2">
                                    <Label for="s3-region">Region</Label>
                                    <Input
                                        id="s3-region"
                                        v-model="form.storage_s3_region"
                                        placeholder="us-east-1"
                                    />
                                    <InputError
                                        :message="
                                            form.errors.storage_s3_region
                                        "
                                    />
                                </span>
                                <span class="space-y-2">
                                    <Label for="s3-bucket">Bucket</Label>
                                    <Input
                                        id="s3-bucket"
                                        v-model="form.storage_s3_bucket"
                                    />
                                    <InputError
                                        :message="
                                            form.errors.storage_s3_bucket
                                        "
                                    />
                                </span>
                                <span class="space-y-2 md:col-span-2">
                                    <Label for="s3-url">
                                        Public URL (optional)
                                    </Label>
                                    <Input
                                        id="s3-url"
                                        v-model="form.storage_s3_url"
                                        placeholder="https://cdn.example.com"
                                    />
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        CloudFront or another CDN in front of
                                        the bucket.
                                    </span>
                                    <InputError
                                        :message="form.errors.storage_s3_url"
                                    />
                                </span>
                            </span>

                            <span
                                v-if="form.storage_driver === key && key === 'r2'"
                                class="mt-4 grid gap-4 md:grid-cols-2"
                            >
                                <span class="space-y-2">
                                    <Label for="r2-account">Account ID</Label>
                                    <Input
                                        id="r2-account"
                                        v-model="form.storage_r2_account_id"
                                    />
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        From the Cloudflare dashboard — R2 →
                                        Overview.
                                    </span>
                                    <InputError
                                        :message="
                                            form.errors.storage_r2_account_id
                                        "
                                    />
                                </span>
                                <span class="space-y-2">
                                    <Label for="r2-bucket">Bucket</Label>
                                    <Input
                                        id="r2-bucket"
                                        v-model="form.storage_r2_bucket"
                                    />
                                    <InputError
                                        :message="
                                            form.errors.storage_r2_bucket
                                        "
                                    />
                                </span>
                                <span class="space-y-2">
                                    <Label for="r2-key">Access key ID</Label>
                                    <Input
                                        id="r2-key"
                                        v-model="form.storage_r2_key"
                                    />
                                    <InputError
                                        :message="form.errors.storage_r2_key"
                                    />
                                </span>
                                <span class="space-y-2">
                                    <Label for="r2-secret">
                                        Secret access key
                                    </Label>
                                    <Input
                                        id="r2-secret"
                                        v-model="form.storage_r2_secret"
                                        type="password"
                                        autocomplete="off"
                                        :placeholder="
                                            secretPlaceholder(
                                                props.settings
                                                    .storage_r2_secret_configured,
                                            )
                                        "
                                    />
                                    <InputError
                                        :message="
                                            form.errors.storage_r2_secret
                                        "
                                    />
                                </span>
                                <span class="space-y-2 md:col-span-2">
                                    <Label for="r2-url">
                                        Public URL (optional)
                                    </Label>
                                    <Input
                                        id="r2-url"
                                        v-model="form.storage_r2_url"
                                        placeholder="https://pub-xxxx.r2.dev or a custom domain"
                                    />
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        Required for publicly served files —
                                        enable public access on the bucket or
                                        connect a custom domain.
                                    </span>
                                    <InputError
                                        :message="form.errors.storage_r2_url"
                                    />
                                </span>
                            </span>

                            <span
                                v-if="form.storage_driver === key && key === 'b2'"
                                class="mt-4 grid gap-4 md:grid-cols-2"
                            >
                                <span class="space-y-2">
                                    <Label for="b2-key-id">Key ID</Label>
                                    <Input
                                        id="b2-key-id"
                                        v-model="form.storage_b2_key_id"
                                    />
                                    <InputError
                                        :message="
                                            form.errors.storage_b2_key_id
                                        "
                                    />
                                </span>
                                <span class="space-y-2">
                                    <Label for="b2-key">Application key</Label>
                                    <Input
                                        id="b2-key"
                                        v-model="form.storage_b2_key"
                                        type="password"
                                        autocomplete="off"
                                        :placeholder="
                                            secretPlaceholder(
                                                props.settings
                                                    .storage_b2_key_configured,
                                            )
                                        "
                                    />
                                    <InputError
                                        :message="form.errors.storage_b2_key"
                                    />
                                </span>
                                <span class="space-y-2">
                                    <Label for="b2-region">Region</Label>
                                    <Input
                                        id="b2-region"
                                        v-model="form.storage_b2_region"
                                        placeholder="us-west-004"
                                    />
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        From the bucket's S3 endpoint, e.g.
                                        s3.us-west-004.backblazeb2.com.
                                    </span>
                                    <InputError
                                        :message="
                                            form.errors.storage_b2_region
                                        "
                                    />
                                </span>
                                <span class="space-y-2">
                                    <Label for="b2-bucket">Bucket</Label>
                                    <Input
                                        id="b2-bucket"
                                        v-model="form.storage_b2_bucket"
                                    />
                                    <InputError
                                        :message="
                                            form.errors.storage_b2_bucket
                                        "
                                    />
                                </span>
                                <span class="space-y-2 md:col-span-2">
                                    <Label for="b2-url">
                                        Public URL (optional)
                                    </Label>
                                    <Input
                                        id="b2-url"
                                        v-model="form.storage_b2_url"
                                        placeholder="https://f004.backblazeb2.com/file/your-bucket"
                                    />
                                    <InputError
                                        :message="form.errors.storage_b2_url"
                                    />
                                </span>
                            </span>
                        </span>
                    </label>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FolderTree class="size-4" /> Upload paths
                    </CardTitle>
                    <CardDescription>
                        Directories (inside the selected storage) where each
                        kind of upload is stored.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-5 md:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="path-branding">Branding images</Label>
                        <Input
                            id="path-branding"
                            v-model="form.storage_path_branding"
                        />
                        <p class="text-xs text-muted-foreground">
                            Logos and favicons uploaded from Site &amp;
                            Branding.
                        </p>
                        <InputError
                            :message="form.errors.storage_path_branding"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="path-tickets">
                            Support ticket attachments
                        </Label>
                        <Input
                            id="path-tickets"
                            v-model="form.storage_path_tickets"
                        />
                        <p class="text-xs text-muted-foreground">
                            Images and files clients attach to support
                            tickets.
                        </p>
                        <InputError
                            :message="form.errors.storage_path_tickets"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="path-products">Product images</Label>
                        <Input
                            id="path-products"
                            v-model="form.storage_path_products"
                        />
                        <p class="text-xs text-muted-foreground">
                            Uploaded product and catalog imagery.
                        </p>
                        <InputError
                            :message="form.errors.storage_path_products"
                        />
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center justify-end gap-3">
                <p
                    v-if="form.wasSuccessful"
                    class="text-sm font-medium text-emerald-600"
                >
                    Storage settings saved.
                </p>
                <Button type="submit" :disabled="form.processing">
                    <Save class="size-4" /> Save storage settings
                </Button>
            </div>
        </form>
    </div>
</template>
