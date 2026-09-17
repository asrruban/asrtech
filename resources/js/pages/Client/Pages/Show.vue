<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, FileText } from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
defineProps(['managedPage']);
const legalPages = [
    { title: 'Terms of Service', slug: 'terms-of-service' },
    { title: 'Privacy Policy', slug: 'privacy-policy' },
    { title: 'Refund Policy', slug: 'refund-policy' },
];
</script>

<template>
    <SeoHead
        :title="managedPage.title"
        :description="managedPage.excerpt"
        :seo="managedPage.seo"
    />
    <section class="page-intro">
        <div class="site-container py-16 sm:py-24">
            <p class="section-kicker">
                {{
                    managedPage.template === 'legal'
                        ? 'Legal information'
                        : 'ASR Tech resources'
                }}
            </p>
            <h1 class="display-title mt-5 max-w-4xl">
                {{ managedPage.title }}
            </h1>
            <p v-if="managedPage.excerpt" class="body-copy mt-6 max-w-2xl">
                {{ managedPage.excerpt }}
            </p>
            <p
                v-if="managedPage.updated_at"
                class="mt-7 text-xs text-muted-foreground"
            >
                Last updated
                {{
                    new Intl.DateTimeFormat('en', {
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric',
                    }).format(new Date(managedPage.updated_at))
                }}
            </p>
        </div>
    </section>
    <section class="site-container py-14 sm:py-20">
        <div
            class="grid gap-10"
            :class="
                managedPage.template === 'legal'
                    ? 'lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-16'
                    : ''
            "
        >
            <aside
                v-if="managedPage.template === 'legal'"
                class="lg:sticky lg:top-28 lg:self-start"
            >
                <nav aria-label="Legal pages" class="space-y-2">
                    <Link
                        v-for="legalPage in legalPages"
                        :key="legalPage.slug"
                        :href="`/${legalPage.slug}`"
                        :aria-current="
                            managedPage.slug === legalPage.slug
                                ? 'page'
                                : undefined
                        "
                        class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium"
                        :class="
                            managedPage.slug === legalPage.slug
                                ? 'bg-primary/10 text-primary'
                                : 'text-muted-foreground hover:bg-muted'
                        "
                        ><FileText class="size-4" />{{ legalPage.title }}</Link
                    >
                </nav>
                <div class="mt-8 border-t px-4 pt-7">
                    <p class="text-sm font-semibold">
                        Need something clarified?
                    </p>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Contact us with your question about these terms.
                    </p>
                    <Link
                        href="/contact"
                        class="mt-4 inline-flex items-center gap-3 text-sm font-semibold text-primary"
                        >Get in touch <ArrowUpRight class="size-4"
                    /></Link>
                </div>
            </aside>
            <article
                class="min-w-0"
                :class="managedPage.template === 'wide' ? '' : 'max-w-3xl'"
            >
                <div
                    class="text-base leading-8 break-words whitespace-pre-line text-muted-foreground"
                >
                    {{ managedPage.content }}
                </div>
            </article>
        </div>
    </section>
</template>
