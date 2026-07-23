<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Boxes,
    CheckCircle2,
    FileText,
    Layers3,
    Package,
    Plus,
    Settings,
    ShieldCheck,
    Sparkles,
    Tags,
    TrendingUp,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps(['stats', 'recentClients']);

const statCards = computed(() => [
    {
        label: 'Products',
        value: props.stats.products,
        note: 'Catalog items',
        icon: Package,
        iconClass:
            'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300',
    },
    {
        label: 'Clients',
        value: props.stats.clients,
        note: 'Customer accounts',
        icon: Users,
        iconClass:
            'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
    },
    {
        label: 'Categories',
        value: props.stats.categories,
        note: 'Product sections',
        icon: Tags,
        iconClass:
            'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
    },
    {
        label: 'Pages',
        value: props.stats.pages,
        note: 'SEO-ready content',
        icon: FileText,
        iconClass:
            'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
    },
]);

const catalogTotal = computed(
    () => props.stats.products + props.stats.categories + props.stats.groups,
);

const formatDate = (date) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));

const initials = (name) =>
    name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
</script>

<template>
    <Head title="Admin dashboard" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <section
            class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-2xl shadow-slate-200 sm:px-8 lg:px-10 dark:shadow-none"
        >
            <div
                class="pointer-events-none absolute -top-28 -right-20 size-80 rounded-full bg-cyan-400/20 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-36 left-1/3 size-80 rounded-full bg-blue-600/25 blur-3xl"
            />
            <div class="relative grid items-end gap-8 lg:grid-cols-[1fr_auto]">
                <div>
                    <div
                        class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-cyan-200"
                    >
                        <Sparkles class="size-3.5" />
                        ASRTech control center
                    </div>
                    <h1
                        class="max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl"
                    >
                        Everything you need to grow your digital catalog.
                    </h1>
                    <p
                        class="mt-3 max-w-2xl text-sm leading-6 text-slate-400 sm:text-base"
                    >
                        Manage WHMCS modules, templates, web-development
                        services, pricing, pages, and SEO from one focused
                        workspace.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Button
                        as-child
                        class="bg-white text-slate-950 hover:bg-slate-100"
                    >
                        <Link href="/admin/products/create">
                            <Plus class="size-4" />
                            Add product
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        class="border-white/15 bg-white/5 text-white hover:bg-white/10 hover:text-white"
                    >
                        <Link href="/admin/settings/general">
                            <Settings class="size-4" />
                            Site settings
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="card in statCards"
                :key="card.label"
                class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/60 dark:border-white/10 dark:bg-slate-900 dark:hover:shadow-none"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            {{ card.label }}
                        </p>
                        <p class="mt-2 text-3xl font-bold tracking-tight">
                            {{ card.value }}
                        </p>
                    </div>
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl transition-transform group-hover:scale-105"
                        :class="card.iconClass"
                    >
                        <component :is="card.icon" class="size-5" />
                    </span>
                </div>
                <div
                    class="mt-4 flex items-center gap-1.5 text-xs text-slate-500"
                >
                    <TrendingUp class="size-3.5 text-emerald-500" />
                    {{ card.note }}
                </div>
            </div>
        </section>

        <section
            class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]"
        >
            <div
                class="rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 p-5 sm:p-6 dark:border-white/10"
                >
                    <div>
                        <h2 class="font-semibold">Recent client accounts</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Latest registered customers
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-300"
                    >
                        {{ recentClients.length }} recent
                    </span>
                </div>

                <div
                    v-if="recentClients.length === 0"
                    class="m-5 flex min-h-56 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 p-8 text-center dark:border-white/10"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-white/10"
                    >
                        <Users class="size-5 text-slate-500" />
                    </div>
                    <p class="mt-4 font-medium">No clients yet</p>
                    <p class="mt-1 max-w-xs text-sm text-slate-500">
                        Client accounts will appear here after their first
                        account sign up.
                    </p>
                </div>

                <div
                    v-else
                    class="divide-y divide-slate-100 dark:divide-white/10"
                >
                    <div
                        v-for="client in recentClients"
                        :key="client.id"
                        class="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-slate-50 sm:px-6 dark:hover:bg-white/5"
                    >
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-100 to-blue-100 text-xs font-bold text-blue-700 dark:from-cyan-500/20 dark:to-blue-500/20 dark:text-cyan-200"
                        >
                            {{ initials(client.name) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">
                                {{ client.name }}
                            </p>
                            <p class="truncate text-xs text-slate-500">
                                {{ client.email }}
                            </p>
                        </div>
                        <time
                            class="hidden shrink-0 text-xs text-slate-400 sm:block"
                            :datetime="client.created_at"
                        >
                            {{ formatDate(client.created_at) }}
                        </time>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6 dark:border-white/10 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold">
                                Catalog overview
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Your content structure
                            </p>
                        </div>
                        <Boxes class="size-5 text-blue-500" />
                    </div>
                    <p class="mt-6 text-4xl font-bold tracking-tight">
                        {{ catalogTotal }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        total catalog records
                    </p>

                    <div class="mt-6 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span
                                class="flex items-center gap-2 text-slate-600 dark:text-slate-300"
                            >
                                <Package class="size-4 text-blue-500" />
                                Products
                            </span>
                            <span class="font-semibold">{{
                                stats.products
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span
                                class="flex items-center gap-2 text-slate-600 dark:text-slate-300"
                            >
                                <Tags class="size-4 text-amber-500" />
                                Categories
                            </span>
                            <span class="font-semibold">{{
                                stats.categories
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span
                                class="flex items-center gap-2 text-slate-600 dark:text-slate-300"
                            >
                                <Layers3 class="size-4 text-violet-500" />
                                Subcategories
                            </span>
                            <span class="font-semibold">{{
                                stats.groups
                            }}</span>
                        </div>
                    </div>

                    <Button variant="outline" class="mt-6 w-full" as-child>
                        <Link href="/admin/products">
                            Manage catalog
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </div>

                <div
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                >
                    <div class="flex items-start gap-3">
                        <CheckCircle2
                            class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                        />
                        <div>
                            <p
                                class="text-sm font-semibold text-emerald-950 dark:text-emerald-100"
                            >
                                Secure admin separation
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-emerald-800/75 dark:text-emerald-200/70"
                            >
                                {{ stats.admins }} administrator account(s) use
                                the dedicated admins table. Client access
                                remains isolated from the storefront.
                            </p>
                        </div>
                        <ShieldCheck
                            class="size-5 shrink-0 text-emerald-600/50"
                        />
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
