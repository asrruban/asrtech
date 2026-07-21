<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BookOpen,
    CalendarDays,
    ExternalLink,
    PackageCheck,
} from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface DocumentationProduct {
    name: string;
    slug: string;
    url: string;
    title: string;
    content: string;
    version?: string | null;
    release_date?: string | null;
    compatibility?: string | null;
    documentation_url?: string | null;
    category: { name: string; slug: string };
    seo: Record<string, unknown>;
}

defineProps<{ product: DocumentationProduct }>();

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));
</script>

<template>
    <SeoHead
        :title="product.title"
        :description="product.seo.meta_description"
        :image="product.seo.open_graph_image"
        :seo="product.seo"
        type="article"
    />

    <div class="min-h-screen bg-[#e9edf3] pb-20 dark:bg-slate-950">
        <header
            class="relative overflow-hidden bg-[radial-gradient(circle_at_80%_20%,rgba(57,184,255,0.25),transparent_34%),linear-gradient(128deg,#0874df_0%,#075dbb_48%,#064296_100%)] text-white"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#fff_1px,transparent_1px),linear-gradient(to_bottom,#fff_1px,transparent_1px)] bg-[size:42px_42px] opacity-[0.07]"
            ></div>
            <div class="relative mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
                <nav
                    class="flex flex-wrap items-center gap-2 text-xs font-semibold text-blue-100/75"
                >
                    <Link href="/products" class="hover:text-white"
                        >Products</Link
                    >
                    <span>/</span>
                    <Link :href="product.url" class="hover:text-white">
                        {{ product.name }}
                    </Link>
                    <span>/</span>
                    <span class="text-white">Documentation</span>
                </nav>

                <div class="mt-8 flex items-start gap-4">
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15"
                    >
                        <BookOpen class="size-6" />
                    </span>
                    <div>
                        <p
                            class="text-xs font-extrabold tracking-[0.18em] text-[#b7ec37] uppercase"
                        >
                            Product documentation
                        </p>
                        <h1
                            class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl"
                        >
                            {{ product.title }}
                        </h1>
                        <p
                            class="mt-3 max-w-3xl text-sm leading-6 text-blue-100/80"
                        >
                            Installation, configuration, and product usage
                            guidance for
                            {{ product.name }}.
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <main
            class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_280px] lg:px-8"
        >
            <article
                class="rounded-sm bg-white p-6 shadow-[0_18px_55px_rgba(40,55,82,0.1)] sm:p-9 dark:bg-slate-900"
            >
                <div class="prose prose-slate dark:prose-invert max-w-none">
                    <div
                        class="text-sm leading-7 font-medium whitespace-pre-line text-slate-600 dark:text-slate-300"
                    >
                        {{ product.content }}
                    </div>
                </div>
            </article>

            <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
                <div
                    class="rounded-sm bg-white p-5 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-white/10"
                >
                    <h2
                        class="text-sm font-extrabold text-slate-900 dark:text-white"
                    >
                        Product information
                    </h2>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div
                            v-if="product.version"
                            class="flex items-start gap-3"
                        >
                            <PackageCheck class="mt-0.5 size-4 text-blue-600" />
                            <div>
                                <dt class="text-xs text-slate-400">Version</dt>
                                <dd
                                    class="font-bold text-slate-700 dark:text-slate-200"
                                >
                                    {{ product.version }}
                                </dd>
                            </div>
                        </div>
                        <div
                            v-if="product.release_date"
                            class="flex items-start gap-3"
                        >
                            <CalendarDays class="mt-0.5 size-4 text-blue-600" />
                            <div>
                                <dt class="text-xs text-slate-400">
                                    Last updated
                                </dt>
                                <dd
                                    class="font-bold text-slate-700 dark:text-slate-200"
                                >
                                    {{ formatDate(product.release_date) }}
                                </dd>
                            </div>
                        </div>
                    </dl>
                </div>

                <Link
                    :href="product.url"
                    class="flex h-11 items-center justify-center gap-2 rounded-sm border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-blue-500 hover:text-blue-600 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200"
                >
                    <ArrowLeft class="size-4" /> Back to product
                </Link>

                <a
                    v-if="product.documentation_url"
                    :href="product.documentation_url"
                    target="_blank"
                    rel="noreferrer"
                    class="flex h-11 items-center justify-center gap-2 rounded-sm bg-blue-600 px-4 text-sm font-bold text-white transition hover:bg-blue-700"
                >
                    External documentation <ExternalLink class="size-4" />
                </a>
            </aside>
        </main>
    </div>
</template>
