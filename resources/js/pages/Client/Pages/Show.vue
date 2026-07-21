<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText, LifeBuoy, ShieldCheck } from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
defineProps(['managedPage']);
</script>

<template>
    <SeoHead
        :title="managedPage.title"
        :description="managedPage.excerpt"
        :seo="managedPage.seo"
    />
    <template v-if="managedPage.template === 'legal'">
        <section
            class="relative overflow-hidden border-b bg-[radial-gradient(circle_at_80%_20%,rgba(59,130,246,.25),transparent_30%),linear-gradient(135deg,#071b36,#0b3165)] py-18 text-white"
        >
            <div
                class="absolute inset-0 bg-[linear-gradient(to_right,#fff_1px,transparent_1px),linear-gradient(to_bottom,#fff_1px,transparent_1px)] bg-[size:48px_48px] opacity-[0.035]"
            ></div>
            <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold text-blue-200"
                >
                    <ShieldCheck class="size-3.5" /> Legal information
                </span>
                <h1
                    class="mt-6 text-4xl font-extrabold tracking-tight sm:text-5xl"
                >
                    {{ managedPage.title }}
                </h1>
                <p
                    v-if="managedPage.excerpt"
                    class="mt-5 max-w-3xl text-base leading-7 text-slate-300"
                >
                    {{ managedPage.excerpt }}
                </p>
                <p class="mt-6 text-xs font-semibold text-slate-500">
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

        <section
            class="bg-slate-50 px-4 py-16 sm:px-6 lg:px-8 dark:bg-slate-950"
        >
            <div
                class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[260px_minmax(0,1fr)]"
            >
                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <p
                        class="px-3 text-xs font-extrabold tracking-wider text-muted-foreground uppercase"
                    >
                        Legal pages
                    </p>
                    <nav class="mt-3 space-y-1">
                        <Link
                            v-for="page in [
                                {
                                    title: 'Terms of Service',
                                    slug: 'terms-of-service',
                                },
                                {
                                    title: 'Privacy Policy',
                                    slug: 'privacy-policy',
                                },
                                {
                                    title: 'Refund Policy',
                                    slug: 'refund-policy',
                                },
                            ]"
                            :key="page.slug"
                            :href="`/${page.slug}`"
                            class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold transition"
                            :class="
                                managedPage.slug === page.slug
                                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/15'
                                    : 'text-muted-foreground hover:bg-white hover:text-foreground dark:hover:bg-white/5'
                            "
                        >
                            <FileText class="size-4" /> {{ page.title }}
                        </Link>
                    </nav>
                    <div class="mt-8 rounded-2xl bg-slate-900 p-5 text-white">
                        <LifeBuoy class="size-6 text-cyan-300" />
                        <p class="mt-4 text-sm font-extrabold">
                            Have a question?
                        </p>
                        <p class="mt-2 text-xs leading-5 text-slate-400">
                            Contact the appropriate team through our secure
                            support desk.
                        </p>
                        <Link
                            href="/support/ticket"
                            class="mt-4 inline-flex text-xs font-extrabold text-cyan-300 hover:text-cyan-200"
                        >
                            Open support center →
                        </Link>
                    </div>
                </aside>

                <article
                    class="rounded-3xl border bg-white p-7 shadow-sm sm:p-10 dark:bg-slate-900"
                >
                    <div
                        class="text-[15px] leading-8 whitespace-pre-line text-slate-600 dark:text-slate-300"
                    >
                        {{ managedPage.content }}
                    </div>
                </article>
            </div>
        </section>
    </template>

    <template v-else>
        <section class="border-b bg-slate-950 py-18 text-white">
            <div
                class="mx-auto px-4 sm:px-6 lg:px-8"
                :class="
                    managedPage.template === 'wide' ? 'max-w-7xl' : 'max-w-4xl'
                "
            >
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                    {{ managedPage.title }}
                </h1>
                <p
                    v-if="managedPage.excerpt"
                    class="mt-5 max-w-3xl text-lg leading-8 text-slate-300"
                >
                    {{ managedPage.excerpt }}
                </p>
            </div>
        </section>
        <article
            class="mx-auto px-4 py-16 sm:px-6 lg:px-8"
            :class="
                managedPage.template === 'wide'
                    ? 'max-w-7xl'
                    : managedPage.template === 'legal'
                      ? 'max-w-3xl'
                      : 'max-w-4xl'
            "
        >
            <div
                class="text-base leading-8 whitespace-pre-line text-muted-foreground"
            >
                {{ managedPage.content }}
            </div>
        </article>
    </template>
</template>
