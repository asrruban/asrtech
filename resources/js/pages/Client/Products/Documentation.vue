<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, ExternalLink } from '@lucide/vue';
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
    <div class="min-h-[70vh] bg-[var(--client-canvas)] pb-16">
        <header class="border-b border-border bg-card">
            <div class="site-container py-10 sm:py-14">
                <nav
                    aria-label="Breadcrumb"
                    class="mb-8 flex flex-wrap gap-2 text-sm text-muted-foreground"
                >
                    <Link href="/products" class="hover:text-primary"
                        >Products</Link
                    ><span aria-hidden="true">/</span
                    ><Link :href="product.url" class="hover:text-primary">{{
                        product.name
                    }}</Link
                    ><span aria-hidden="true">/</span
                    ><span aria-current="page">Documentation</span>
                </nav>
                <p class="section-kicker">Product documentation</p>
                <h1
                    class="mt-4 max-w-4xl text-3xl leading-tight font-semibold tracking-tight sm:text-5xl"
                >
                    {{ product.title }}
                </h1>
                <p class="body-copy mt-5">
                    The published guide for {{ product.name }}.
                </p>
            </div>
        </header>
        <div
            class="site-container grid gap-8 py-8 sm:py-12 lg:grid-cols-[minmax(0,1fr)_280px]"
        >
            <article class="surface-card min-w-0 p-6 sm:p-10">
                <div
                    class="mb-7 flex items-center gap-3 border-b border-border pb-5"
                >
                    <BookOpen class="size-5 text-primary" />
                    <h2 class="text-lg font-semibold">
                        {{ product.name }} guide
                    </h2>
                </div>
                <div
                    class="text-base leading-8 [overflow-wrap:anywhere] whitespace-pre-line text-muted-foreground"
                >
                    {{ product.content }}
                </div>
            </article>
            <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
                <div class="surface-card p-6">
                    <h2 class="font-semibold">Product information</h2>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Category
                            </dt>
                            <dd class="mt-1 font-medium">
                                {{ product.category.name }}
                            </dd>
                        </div>
                        <div v-if="product.version">
                            <dt class="text-xs text-muted-foreground">
                                Version
                            </dt>
                            <dd class="mt-1 font-medium">
                                {{ product.version }}
                            </dd>
                        </div>
                        <div v-if="product.compatibility">
                            <dt class="text-xs text-muted-foreground">
                                Compatibility
                            </dt>
                            <dd class="mt-1 font-medium">
                                {{ product.compatibility }}
                            </dd>
                        </div>
                        <div v-if="product.release_date">
                            <dt class="text-xs text-muted-foreground">
                                Last updated
                            </dt>
                            <dd class="mt-1 font-medium">
                                {{ formatDate(product.release_date) }}
                            </dd>
                        </div>
                    </dl>
                </div>
                <Link :href="product.url" class="button-secondary w-full"
                    ><ArrowLeft class="size-4" /> Back to product</Link
                ><a
                    v-if="product.documentation_url"
                    :href="product.documentation_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="button-secondary w-full"
                    >External documentation <ExternalLink class="size-4"
                /></a>
                <div class="rounded-xl bg-accent p-6">
                    <h2 class="font-semibold">Need a hand?</h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Tell us what you are setting up and where you need help.
                    </p>
                    <Link
                        href="/support"
                        class="mt-4 inline-flex text-sm font-semibold text-primary underline underline-offset-4"
                        >Visit support</Link
                    >
                </div>
            </aside>
        </div>
    </div>
</template>
