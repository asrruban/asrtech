<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    LifeBuoy,
    Package,
    Palette,
    ShieldCheck,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Client dashboard',
                href: '/dashboard',
            },
        ],
    },
});
const page = usePage();
const user = computed(() => page.props.auth.user);
const site = computed(() => page.props.site);
</script>

<template>
    <Head title="Client dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 sm:p-6">
        <section
            class="relative overflow-hidden rounded-2xl bg-slate-950 px-6 py-9 text-white shadow-sm sm:px-9"
        >
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_85%_20%,rgba(59,130,246,0.3),transparent_35%)]"
            />
            <div class="relative max-w-2xl">
                <p class="text-sm font-medium text-blue-300">Client portal</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">
                    Welcome, {{ user.name }}
                </h1>
                <p class="mt-3 leading-7 text-slate-300">
                    Access ASRTech products, account settings, documentation,
                    and professional support from one secure workspace.
                </p>
                <Button
                    as-child
                    class="mt-6 bg-white text-slate-950 hover:bg-slate-200"
                >
                    <Link href="/products">
                        Browse products <ArrowRight class="size-4" />
                    </Link>
                </Button>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader>
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                    >
                        <Package class="size-5" />
                    </div>
                    <CardTitle>Products and services</CardTitle>
                    <CardDescription>
                        Explore modules, templates, licenses, and development
                        services.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Link
                        href="/products"
                        class="inline-flex items-center gap-1 text-sm font-semibold"
                    >
                        View catalog <ArrowRight class="size-4" />
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        <UserRound class="size-5" />
                    </div>
                    <CardTitle>Account profile</CardTitle>
                    <CardDescription>
                        Review your account details and manage your client
                        profile.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Link
                        href="/client-area/account-details"
                        class="inline-flex items-center gap-1 text-sm font-semibold"
                    >
                        Profile settings <ArrowRight class="size-4" />
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                    >
                        <LifeBuoy class="size-5" />
                    </div>
                    <CardTitle>Professional support</CardTitle>
                    <CardDescription>
                        Contact ASRTech for product, license, or development
                        assistance.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <a
                        :href="`mailto:${site.supportEmail || ''}`"
                        class="inline-flex items-center gap-1 text-sm font-semibold"
                    >
                        Contact support <ArrowRight class="size-4" />
                    </a>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            <Card>
                <CardHeader>
                    <CardTitle>Quick access</CardTitle>
                    <CardDescription>
                        Built-in Laravel Vue Starter Kit client features.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3 sm:grid-cols-2">
                    <Link
                        href="/client-area/account-details"
                        class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-muted/60"
                    >
                        <UserRound class="size-5" />
                        <div>
                            <p class="font-medium">Profile</p>
                            <p class="text-xs text-muted-foreground">
                                Account information
                            </p>
                        </div>
                    </Link>
                    <Link
                        href="/settings/appearance"
                        class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-muted/60"
                    >
                        <Palette class="size-5" />
                        <div>
                            <p class="font-medium">Appearance</p>
                            <p class="text-xs text-muted-foreground">
                                Light, dark, or system
                            </p>
                        </div>
                    </Link>
                    <Link
                        href="/products"
                        class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-muted/60"
                    >
                        <Package class="size-5" />
                        <div>
                            <p class="font-medium">Product catalog</p>
                            <p class="text-xs text-muted-foreground">
                                ASRTech solutions
                            </p>
                        </div>
                    </Link>
                    <a
                        :href="`mailto:${site.supportEmail || ''}`"
                        class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-muted/60"
                    >
                        <BookOpen class="size-5" />
                        <div>
                            <p class="font-medium">Help and support</p>
                            <p class="text-xs text-muted-foreground">
                                Speak with our team
                            </p>
                        </div>
                    </a>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="size-5 text-emerald-600" />
                        <CardTitle>Secure session</CardTitle>
                    </div>
                    <CardDescription>
                        Client authentication uses secure email and password sign in.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Signed in as
                        </p>
                        <p class="truncate font-medium">{{ user.email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Provider</p>
                        <p class="font-medium">Email + password</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
