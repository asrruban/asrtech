<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, ArrowUpRight, Code2, Layers3, Wrench } from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
import { services } from '@/modules/client/data/services';

defineProps<{ seo: Record<string, unknown> }>();
</script>

<template>
    <SeoHead :seo="seo" />
    <section class="page-intro border-b border-border/70">
        <div
            class="site-container grid gap-10 py-16 lg:grid-cols-[1.3fr_0.7fr] lg:items-end lg:py-24"
        >
            <div>
                <p class="section-kicker">
                    Services / Built around your business
                </p>
                <h1 class="display-title mt-5 max-w-3xl">
                    From the first idea.<br />To the everyday details.
                </h1>
            </div>
            <div class="max-w-lg">
                <p class="body-copy">
                    Build a website or app, extend WHMCS and WordPress, or get
                    help with the systems you already rely on. ASR Tech brings
                    development and technical care together.
                </p>
                <Link href="/contact" class="button-primary mt-7"
                    >Discuss Your Project <ArrowUpRight class="size-4"
                /></Link>
            </div>
        </div>
    </section>
    <section
        class="site-container py-14 md:py-20"
        aria-labelledby="services-heading"
    >
        <div class="mb-10 flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="section-kicker">What we can help with</p>
                <h2 id="services-heading" class="section-title mt-3">
                    The right expertise for the job.
                </h2>
            </div>
            <p class="max-w-xs text-sm leading-6 text-muted-foreground">
                A focused change, a new product, or ongoing maintenance. Start
                with what you need.
            </p>
        </div>
        <div class="grid gap-5 md:grid-cols-2">
            <Link
                v-for="service in services"
                :key="service.id"
                :href="`/services/${service.slug}`"
                class="surface-card group flex flex-col p-7 transition hover:-translate-y-1 hover:border-teal-700/30 md:p-9"
            >
                <div class="mb-8 flex items-center justify-between">
                    <span
                        class="rounded-full bg-accent px-3 py-1 text-xs font-semibold text-primary"
                        >{{ service.category }}</span
                    ><span class="font-mono text-xs text-muted-foreground">{{
                        service.number
                    }}</span>
                </div>
                <h3
                    class="text-2xl font-semibold tracking-tight text-foreground"
                >
                    {{ service.title }}
                </h3>
                <p
                    class="mt-3 max-w-lg text-base leading-7 text-muted-foreground"
                >
                    {{ service.shortDescription }}
                </p>
                <span
                    class="mt-8 flex items-center justify-between border-t border-border pt-5 text-sm font-semibold text-primary"
                    >Explore service
                    <ArrowUpRight
                        class="size-5 transition group-hover:translate-x-1"
                /></span>
            </Link>
        </div>
    </section>
    <section class="border-y border-border/70 bg-card">
        <div class="site-container grid gap-10 py-16 md:grid-cols-3">
            <div>
                <Code2 class="mb-6 size-7 text-primary" />
                <h2 class="text-xl font-semibold">Build something new</h2>
                <p class="mt-3 leading-7 text-muted-foreground">
                    Custom development starts with your requirements. We discuss
                    the functionality, scope, and delivery before the work
                    begins.
                </p>
            </div>
            <div>
                <Layers3 class="mb-6 size-7 text-primary" />
                <h2 class="text-xl font-semibold">Find a ready-made fit</h2>
                <p class="mt-3 leading-7 text-muted-foreground">
                    Explore available modules, templates, and plugins. Each
                    product page contains its own features, pricing, and license
                    information.
                </p>
            </div>
            <div>
                <Wrench class="mb-6 size-7 text-primary" />
                <h2 class="text-xl font-semibold">Care for what you have</h2>
                <p class="mt-3 leading-7 text-muted-foreground">
                    Maintenance and troubleshooting focus on existing systems.
                    Tell us what runs today and where you need help.
                </p>
                <Link
                    href="/maintenance"
                    class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary"
                    >Explore maintenance plans <ArrowRight class="size-4"
                /></Link>
            </div>
        </div>
    </section>
    <section class="site-container py-16 md:py-24">
        <div
            class="flex flex-col justify-between gap-8 rounded-3xl bg-[#172c2c] p-8 text-white md:flex-row md:items-center md:p-12"
        >
            <div>
                <p
                    class="text-xs font-semibold tracking-widest text-teal-200 uppercase"
                >
                    A good place to start
                </p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight">
                    Tell us what you have in mind.
                </h2>
                <p class="mt-3 text-slate-300">
                    You do not need a finished brief to start a conversation.
                </p>
            </div>
            <Link
                href="/contact"
                class="inline-flex shrink-0 items-center justify-center gap-3 rounded-xl bg-white px-6 py-4 text-sm font-semibold text-[#172c2c]"
                >Discuss Your Project <ArrowRight class="size-4"
            /></Link>
        </div>
    </section>
</template>
