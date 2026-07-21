<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    BarChart3,
    Home,
    ImageIcon,
    Save,
    Search,
    ShieldCheck,
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

interface SeoSettings {
    default_meta_title: string;
    default_meta_description: string;
    default_og_image: string | null;
    home_meta_title: string | null;
    home_meta_description: string | null;
    home_meta_keywords: string | null;
    home_og_image: string | null;
    google_site_verification: string | null;
    bing_site_verification: string | null;
    yandex_site_verification: string | null;
    baidu_site_verification: string | null;
    pinterest_site_verification: string | null;
    ga4_measurement_id: string | null;
    gtm_container_id: string | null;
    meta_pixel_id: string | null;
}

const props = defineProps<{ settings: SeoSettings }>();

const form = useForm<{
    default_meta_title: string;
    default_meta_description: string;
    home_meta_title: string;
    home_meta_description: string;
    home_meta_keywords: string;
    google_site_verification: string;
    bing_site_verification: string;
    yandex_site_verification: string;
    baidu_site_verification: string;
    pinterest_site_verification: string;
    ga4_measurement_id: string;
    gtm_container_id: string;
    meta_pixel_id: string;
    og_image: File | null;
    home_og_image: File | null;
    remove_og_image: boolean;
    remove_home_og_image: boolean;
}>({
    default_meta_title: props.settings.default_meta_title ?? '',
    default_meta_description: props.settings.default_meta_description ?? '',
    home_meta_title: props.settings.home_meta_title ?? '',
    home_meta_description: props.settings.home_meta_description ?? '',
    home_meta_keywords: props.settings.home_meta_keywords ?? '',
    google_site_verification: props.settings.google_site_verification ?? '',
    bing_site_verification: props.settings.bing_site_verification ?? '',
    yandex_site_verification: props.settings.yandex_site_verification ?? '',
    baidu_site_verification: props.settings.baidu_site_verification ?? '',
    pinterest_site_verification:
        props.settings.pinterest_site_verification ?? '',
    ga4_measurement_id: props.settings.ga4_measurement_id ?? '',
    gtm_container_id: props.settings.gtm_container_id ?? '',
    meta_pixel_id: props.settings.meta_pixel_id ?? '',
    og_image: null,
    home_og_image: null,
    remove_og_image: false,
    remove_home_og_image: false,
});

const verificationFields = [
    {
        name: 'google_site_verification' as const,
        label: 'Google Search Console',
        hint: 'google-site-verification',
    },
    {
        name: 'bing_site_verification' as const,
        label: 'Bing Webmaster Tools',
        hint: 'msvalidate.01',
    },
    {
        name: 'yandex_site_verification' as const,
        label: 'Yandex Webmaster',
        hint: 'yandex-verification',
    },
    {
        name: 'baidu_site_verification' as const,
        label: 'Baidu Webmaster',
        hint: 'baidu-site-verification',
    },
    {
        name: 'pinterest_site_verification' as const,
        label: 'Pinterest',
        hint: 'p:domain_verify',
    },
];

const pickImage = (field: 'og_image' | 'home_og_image', event: Event) => {
    form[field] = (event.target as HTMLInputElement).files?.[0] ?? null;
};

// PUT with multipart is not parsed by PHP, so spoof the method over POST.
const submit = () =>
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post('/admin/settings/seo', {
            onSuccess: () => {
                form.og_image = null;
                form.home_og_image = null;
                form.remove_og_image = false;
                form.remove_home_og_image = false;
            },
        });
</script>

<template>
    <Head title="Global SEO" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Configuration</p>
            <h1 class="text-3xl font-semibold tracking-tight">Global SEO</h1>
            <p class="mt-1 text-muted-foreground">
                Search engine verification, analytics and tracking tags, home
                page metadata, and the social sharing images used across the
                storefront.
            </p>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ShieldCheck class="size-4" /> Search engine
                        verification
                    </CardTitle>
                    <CardDescription>
                        Ownership verification codes — paste either the code
                        or the full meta tag; the code is extracted
                        automatically. Rendered on every page.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-5 md:grid-cols-2">
                    <div
                        v-for="field in verificationFields"
                        :key="field.name"
                        class="space-y-2"
                    >
                        <Label :for="field.name">{{ field.label }}</Label>
                        <Input
                            :id="field.name"
                            v-model="form[field.name]"
                            :placeholder="`<meta name=&quot;${field.hint}&quot; …>`"
                        />
                        <InputError :message="form.errors[field.name]" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BarChart3 class="size-4" /> Analytics &amp; tracking
                    </CardTitle>
                    <CardDescription>
                        Tags load on the storefront only — never in the admin
                        panel.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-5 md:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="ga4">Google Analytics 4</Label>
                        <Input
                            id="ga4"
                            v-model="form.ga4_measurement_id"
                            placeholder="G-XXXXXXXXXX"
                        />
                        <p class="text-xs text-muted-foreground">
                            Measurement ID from GA4 data streams.
                        </p>
                        <InputError
                            :message="form.errors.ga4_measurement_id"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="gtm">Google Tag Manager</Label>
                        <Input
                            id="gtm"
                            v-model="form.gtm_container_id"
                            placeholder="GTM-XXXXXXX"
                        />
                        <p class="text-xs text-muted-foreground">
                            Container ID; loads the GTM snippet.
                        </p>
                        <InputError :message="form.errors.gtm_container_id" />
                    </div>
                    <div class="space-y-2">
                        <Label for="pixel">Meta Pixel</Label>
                        <Input
                            id="pixel"
                            v-model="form.meta_pixel_id"
                            placeholder="1234567890123456"
                        />
                        <p class="text-xs text-muted-foreground">
                            Numeric Pixel ID from Meta Events Manager.
                        </p>
                        <InputError :message="form.errors.meta_pixel_id" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Home class="size-4" /> Home page SEO
                    </CardTitle>
                    <CardDescription>
                        Overrides for the storefront home page. Blank fields
                        fall back to the site defaults below.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="space-y-2">
                        <Label for="home-title">Home meta title</Label>
                        <Input
                            id="home-title"
                            v-model="form.home_meta_title"
                        />
                        <InputError :message="form.errors.home_meta_title" />
                    </div>
                    <div class="space-y-2">
                        <Label for="home-description">
                            Home meta description
                        </Label>
                        <textarea
                            id="home-description"
                            v-model="form.home_meta_description"
                            rows="3"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        />
                        <InputError
                            :message="form.errors.home_meta_description"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="home-keywords">Home meta keywords</Label>
                        <Input
                            id="home-keywords"
                            v-model="form.home_meta_keywords"
                            placeholder="whmcs modules, hosting templates, web development"
                        />
                        <p class="text-xs text-muted-foreground">
                            Comma-separated. Most search engines ignore
                            keywords, but they are rendered when set.
                        </p>
                        <InputError :message="form.errors.home_meta_keywords" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Search class="size-4" /> Site default SEO
                    </CardTitle>
                    <CardDescription>
                        Fallback metadata for public pages without custom SEO.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="space-y-2">
                        <Label for="default-title">Default meta title</Label>
                        <Input
                            id="default-title"
                            v-model="form.default_meta_title"
                            required
                        />
                        <InputError :message="form.errors.default_meta_title" />
                    </div>
                    <div class="space-y-2">
                        <Label for="default-description">
                            Default meta description
                        </Label>
                        <textarea
                            id="default-description"
                            v-model="form.default_meta_description"
                            rows="3"
                            required
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        />
                        <InputError
                            :message="form.errors.default_meta_description"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ImageIcon class="size-4" /> Open Graph images
                    </CardTitle>
                    <CardDescription>
                        Social sharing images (1200×630 recommended). Stored on
                        the disk configured in Storage Settings.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-6 md:grid-cols-2">
                    <div
                        v-for="slot in [
                            {
                                field: 'og_image' as const,
                                remove: 'remove_og_image' as const,
                                label: 'Default OG image',
                                current: props.settings.default_og_image,
                                hint: 'Used site-wide when a page has no custom social image.',
                            },
                            {
                                field: 'home_og_image' as const,
                                remove: 'remove_home_og_image' as const,
                                label: 'Home page OG image',
                                current: props.settings.home_og_image,
                                hint: 'Optional override for the home page only.',
                            },
                        ]"
                        :key="slot.field"
                        class="space-y-2 rounded-lg border p-4"
                    >
                        <Label>{{ slot.label }}</Label>
                        <div
                            class="flex h-32 items-center justify-center overflow-hidden rounded-md border border-dashed bg-muted/40 p-2"
                        >
                            <img
                                v-if="slot.current"
                                :src="slot.current"
                                :alt="slot.label"
                                class="max-h-full max-w-full object-contain"
                            />
                            <span
                                v-else
                                class="text-xs text-muted-foreground"
                            >
                                Not uploaded
                            </span>
                        </div>
                        <input
                            type="file"
                            accept="image/*"
                            class="block w-full text-xs file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-xs file:font-semibold"
                            @change="pickImage(slot.field, $event)"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ slot.hint }} PNG or JPG, up to 2 MB.
                        </p>
                        <label
                            v-if="slot.current"
                            class="flex items-center gap-2 text-xs font-medium"
                        >
                            <input
                                v-model="form[slot.remove]"
                                type="checkbox"
                                class="size-3.5 rounded"
                            />
                            Remove current image
                        </label>
                        <InputError :message="form.errors[slot.field]" />
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center justify-end gap-3">
                <p
                    v-if="form.wasSuccessful"
                    class="text-sm font-medium text-emerald-600"
                >
                    SEO settings saved.
                </p>
                <Button type="submit" :disabled="form.processing">
                    <Save class="size-4" /> Save SEO settings
                </Button>
            </div>
        </form>
    </div>
</template>
