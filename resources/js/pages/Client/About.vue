<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Code2, MapPin, Wrench } from '@lucide/vue';
import { computed } from 'vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
defineProps<{ seo: Record<string, unknown> }>();
const page = usePage();
const business = computed(() => page.props.business);
</script>

<template>
    <SeoHead :seo="seo" />
    <section class="page-intro">
        <div
            class="site-container grid gap-12 py-16 lg:grid-cols-[1.15fr_0.85fr] lg:items-center lg:py-24"
        >
            <div>
                <p class="section-kicker">About {{ business.name }}</p>
                <h1 class="display-title mt-5">
                    Technology with<br />a practical purpose.
                </h1>
                <p class="body-copy mt-7 max-w-xl">
                    We build websites and mobile apps, extend WHMCS and
                    WordPress, and help businesses look after the technology
                    they use every day.
                </p>
                <Link href="/services" class="button-secondary mt-8"
                    >Explore our services <ArrowUpRight class="size-4"
                /></Link>
            </div>
            <div
                class="relative overflow-hidden rounded-3xl bg-[#172c2c] p-8 text-white md:p-12"
            >
                <div
                    class="flex items-center justify-between border-b border-white/15 pb-7"
                >
                    <span class="text-sm font-medium text-teal-200"
                        >DEVELOPMENT + TECHNICAL CARE</span
                    ><span
                        class="size-2.5 rounded-full bg-teal-300"
                        aria-hidden="true"
                    ></span>
                </div>
                <div class="py-10">
                    <span class="block font-mono text-sm text-slate-400"
                        >&lt;built_with_purpose&gt;</span
                    >
                    <p
                        class="my-6 text-4xl leading-tight font-semibold tracking-tight md:text-5xl"
                    >
                        Build.<br /><span class="text-teal-200">Extend.</span
                        ><br />Maintain.
                    </p>
                    <span class="block font-mono text-sm text-slate-400"
                        >&lt;/built_with_purpose&gt;</span
                    >
                </div>
                <div
                    class="flex items-center gap-3 border-t border-white/15 pt-7 text-sm text-slate-300"
                >
                    <MapPin class="size-4 text-teal-200" /> Purbadhala,
                    Netrokona, Bangladesh
                </div>
            </div>
        </div>
    </section>
    <section class="border-y border-border/70 bg-card">
        <div
            class="site-container grid gap-10 py-16 md:grid-cols-[0.7fr_1.3fr] md:py-20"
        >
            <div>
                <p class="section-kicker">The business</p>
                <h2 class="section-title mt-4">Meet ASR Tech.</h2>
            </div>
            <div>
                <p
                    class="text-2xl leading-relaxed font-medium tracking-tight text-foreground"
                >
                    ASR Tech brings custom development, software products, and
                    technical maintenance into one place.
                </p>
                <p
                    class="mt-6 max-w-2xl text-base leading-8 text-muted-foreground"
                >
                    The business is owned and founded by
                    {{ business.owner }} and is based in Purbadhala, Netrokona,
                    Bangladesh. Our work covers new development as well as the
                    practical needs of existing websites, applications, and
                    servers.
                </p>
                <div class="mt-9 flex items-center gap-4">
                    <div
                        class="flex size-14 items-center justify-center rounded-2xl bg-accent text-lg font-semibold text-primary"
                        aria-hidden="true"
                    >
                        AA
                    </div>
                    <div>
                        <p class="font-semibold text-foreground">
                            {{ business.owner }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Owner and Founder
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="site-container grid gap-8 py-16 md:grid-cols-2 md:py-20">
        <div class="surface-card p-8">
            <Code2 class="size-7 text-primary" />
            <h2 class="mt-6 text-2xl font-semibold">A place to build</h2>
            <p class="mt-4 leading-7 text-muted-foreground">
                Web and mobile development for new ideas. WHMCS customisation,
                modules and templates, and WordPress plugins for the platforms
                you already use.
            </p>
            <Link
                href="/services"
                class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-primary"
                >See development services <ArrowUpRight class="size-4"
            /></Link>
        </div>
        <div class="surface-card p-8">
            <Wrench class="size-7 text-primary" />
            <h2 class="mt-6 text-2xl font-semibold">A place to get help</h2>
            <p class="mt-4 leading-7 text-muted-foreground">
                WordPress and WHMCS management, server maintenance,
                troubleshooting, and technical issue resolution. Support starts
                with understanding your setup.
            </p>
            <Link
                href="/services/technical-support"
                class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-primary"
                >Explore technical support <ArrowUpRight class="size-4"
            /></Link>
        </div>
    </section>
    <section class="site-container pb-16 md:pb-24">
        <div
            class="grid gap-8 rounded-3xl bg-accent p-8 md:grid-cols-[1.1fr_0.9fr] md:p-12"
        >
            <div>
                <p class="section-kicker">Based in Bangladesh</p>
                <h2 class="section-title mt-4">
                    Let’s talk about your next step.
                </h2>
                <Link href="/contact" class="button-primary mt-7"
                    >Discuss Your Project <ArrowUpRight class="size-4"
                /></Link>
            </div>
            <address
                class="self-center border-l-2 border-teal-700/20 pl-6 text-lg leading-8 text-muted-foreground not-italic"
            >
                {{ business.address }}
            </address>
        </div>
    </section>
</template>
