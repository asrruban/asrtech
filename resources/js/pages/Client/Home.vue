<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowRight,
    ArrowUpRight,
    Blocks,
    Code2,
    Plus,
    Server,
    Sparkles,
} from '@lucide/vue';
import { computed } from 'vue';
import ProductCard from '@/modules/client/components/ProductCard.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
import TechnicalScene from '@/modules/client/components/TechnicalScene.vue';

defineProps(['featuredProducts', 'navigationPages']);
const page = usePage();
const business = computed(() => page.props.business);
const offerings = [
    {
        number: '01',
        label: 'From the first idea',
        title: 'Web & mobile development',
        text: 'Websites, web applications, and mobile apps shaped around the way your business works.',
        icon: Code2,
        href: '/services/web-development',
        tags: ['Websites & applications', 'Mobile experiences'],
        class: 'development',
    },
    {
        number: '02',
        label: 'A better hosting workflow',
        title: 'WHMCS solutions',
        text: 'Customisation, modules, and templates that help your WHMCS installation do what you need.',
        icon: Blocks,
        href: '/services/whmcs-customisation',
        tags: ['Modules & templates', 'Custom integrations'],
        class: 'whmcs',
    },
    {
        number: '03',
        label: 'Extend what is possible',
        title: 'WordPress solutions',
        text: 'Purposeful plugins and hands-on management for your WordPress website.',
        icon: Sparkles,
        href: '/services/wordpress-plugins',
        tags: ['Plugin development', 'Website management'],
        class: 'wordpress',
    },
    {
        number: '04',
        label: 'Keep things moving',
        title: 'Technical care & support',
        text: 'Server maintenance, troubleshooting, and issue resolution for the systems you depend on.',
        icon: Server,
        href: '/services/server-management',
        tags: ['Server management', 'Troubleshooting'],
        class: 'maintenance',
    },
];
const steps = [
    {
        number: '01',
        title: 'Tell us what you need.',
        text: 'Share your idea, your current setup, or the issue you want to solve. A little context goes a long way.',
    },
    {
        number: '02',
        title: 'Agree on the approach.',
        text: 'We discuss the scope, requirements, and next steps before work begins.',
    },
    {
        number: '03',
        title: 'Build, improve, or resolve.',
        text: 'Move forward with a clear plan for the work and any ongoing care you need.',
    },
];
const questions = [
    {
        question: 'Can you work on an existing website or application?',
        answer: 'Yes. ASR Tech works on new projects and existing systems. Tell us what you use today, what needs to change, and any technical constraints so we can discuss a suitable approach.',
    },
    {
        question: 'Do you offer custom WHMCS development?',
        answer: 'Yes. Our services include WHMCS customisation and modification, modules, templates, and ongoing management. Share your WHMCS version and the workflow you want to improve with your inquiry.',
    },
    {
        question: 'How is maintenance different from a development project?',
        answer: 'Development focuses on building or changing something with an agreed scope. Maintenance focuses on managing existing websites, WHMCS installations, or servers. We discuss the responsibilities and arrangements for each separately.',
    },
    {
        question:
            'Where can I find product compatibility and licensing details?',
        answer: 'Open the individual product page to see its available technical requirements, license options, documentation, and prices. If a detail you need is missing, contact us before purchasing.',
    },
    {
        question: 'How do I get help with a product I already own?',
        answer: 'Sign in to the Client Area to access your products, downloads, invoices, and support tickets. Use the support center to choose the appropriate department for your request.',
    },
];
</script>

<template>
    <SeoHead
        title="Web development, WHMCS & technical support"
        description="ASR Tech builds websites, mobile apps, WHMCS solutions, and WordPress plugins, with server management and technical support from Purbadhala, Bangladesh."
    />
    <section class="home-hero">
        <div
            class="site-container grid items-center gap-10 py-14 md:py-18 lg:grid-cols-[1.05fr_1fr] lg:gap-12 lg:py-20"
        >
            <div class="relative z-10">
                <p class="section-kicker">Development & technical care</p>
                <h1 class="display-title mt-7">
                    Good technology.<br />Built for
                    <span class="text-primary"
                        >your<br class="hidden lg:block" />
                        next step.</span
                    >
                </h1>
                <p class="body-copy mt-7 max-w-lg">
                    We build websites, mobile apps, and practical solutions for
                    WHMCS and WordPress. And we help keep the technology behind
                    your business working.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link href="/contact" class="button-primary"
                        >Discuss Your Project
                        <ArrowUpRight class="size-4" /></Link
                    ><Link href="/products" class="button-secondary"
                        >Explore Products <ArrowRight class="size-4"
                    /></Link>
                </div>
                <div
                    class="mt-10 flex items-center gap-4 text-xs text-muted-foreground"
                >
                    <span class="h-px w-9 bg-border"></span
                    ><span>Independent thinking. Practical solutions.</span>
                </div>
            </div>
            <TechnicalScene />
        </div>
        <div class="border-t border-border/80">
            <div
                class="site-container flex flex-wrap items-center justify-between gap-x-8 gap-y-4 py-6"
            >
                <p
                    class="text-[10px] font-semibold tracking-[.15em] text-muted-foreground uppercase"
                >
                    Build. Connect. Maintain.
                </p>
                <div
                    class="flex flex-wrap items-center gap-x-7 gap-y-3 text-sm font-medium text-foreground/75 sm:gap-x-10"
                >
                    <span>Web & Mobile</span><span>WHMCS</span
                    ><span>WordPress</span><span>Server Care</span>
                </div>
                <a
                    href="#what-we-do"
                    class="hidden items-center gap-2 text-xs text-muted-foreground lg:inline-flex"
                    >Take a closer look <ArrowDown class="size-3"
                /></a>
            </div>
        </div>
    </section>

    <section id="what-we-do" class="site-container py-20 sm:py-26">
        <div
            class="flex flex-col justify-between gap-6 md:flex-row md:items-end"
        >
            <div class="max-w-2xl">
                <p class="section-kicker">What we do</p>
                <h2 class="section-title mt-5">
                    The right expertise.<br />Where you need it.
                </h2>
            </div>
            <Link
                href="/services"
                class="inline-flex items-center gap-5 text-sm font-semibold text-primary"
                >Explore all services <ArrowUpRight class="size-5"
            /></Link>
        </div>
        <div class="mt-12 grid gap-5 md:grid-cols-2">
            <Link
                v-for="offering in offerings"
                :key="offering.number"
                :href="offering.href"
                class="offering-card group"
                :class="offering.class"
            >
                <div class="flex items-center justify-between">
                    <span class="offering-icon"
                        ><component :is="offering.icon" class="size-6" /></span
                    ><span class="text-xs tracking-widest text-muted-foreground"
                        >/ {{ offering.number }}</span
                    >
                </div>
                <div class="mt-8">
                    <p
                        class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        {{ offering.label }}
                    </p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ offering.title }}
                    </h3>
                    <p
                        class="mt-3 max-w-md text-sm leading-7 text-muted-foreground"
                    >
                        {{ offering.text }}
                    </p>
                </div>
                <div class="mt-8 flex items-end justify-between gap-4">
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="tag in offering.tags"
                            :key="tag"
                            class="rounded-md border border-current/10 px-2.5 py-1 text-[10px] font-medium text-muted-foreground"
                            >{{ tag }}</span
                        >
                    </div>
                    <ArrowUpRight
                        class="size-5 shrink-0 text-primary transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                    />
                </div>
            </Link>
        </div>
    </section>

    <section class="border-y bg-muted/40 py-20 sm:py-24">
        <div class="site-container">
            <div
                class="flex flex-col justify-between gap-6 md:flex-row md:items-end"
            >
                <div>
                    <p class="section-kicker">The product shelf</p>
                    <h2 class="section-title mt-5">
                        Small additions.<br />Useful possibilities.
                    </h2>
                </div>
                <p class="max-w-sm text-sm leading-7 text-muted-foreground">
                    Explore the catalog for product details, available licenses,
                    and documentation. Find the right fit for your setup.
                </p>
            </div>
            <div
                v-if="featuredProducts.length"
                class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <ProductCard
                    v-for="product in featuredProducts"
                    :key="product.slug"
                    :product="product"
                    :heading-level="3"
                />
            </div>
            <div
                v-else
                class="surface-card mt-10 flex flex-col items-start justify-between gap-6 p-8 sm:flex-row sm:items-center"
            >
                <div>
                    <h3 class="text-xl font-semibold">
                        Looking for a particular solution?
                    </h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Explore the current catalog or talk to us about a custom
                        requirement.
                    </p>
                </div>
                <Link href="/contact" class="button-secondary"
                    >Tell us what you need <ArrowUpRight class="size-4"
                /></Link>
            </div>
            <div class="mt-10 text-center">
                <Link href="/products" class="button-secondary"
                    >Explore All Products <ArrowRight class="size-4"
                /></Link>
            </div>
        </div>
    </section>

    <section
        class="site-container grid gap-12 py-20 sm:py-26 lg:grid-cols-[.8fr_1.2fr] lg:gap-24"
    >
        <div>
            <p class="section-kicker">A clear way forward</p>
            <h2 class="section-title mt-5">
                Let's take it<br />one step at a time.
            </h2>
            <p class="body-copy mt-6 max-w-sm">
                You don't need a finished specification to start a conversation.
                Bring the problem. We'll work through the possibilities.
            </p>
            <Link
                href="/contact"
                class="mt-8 inline-flex items-center gap-5 text-sm font-semibold text-primary"
                >Start a conversation <ArrowUpRight class="size-5"
            /></Link>
        </div>
        <div>
            <div
                v-for="step in steps"
                :key="step.number"
                class="grid grid-cols-[3rem_1fr] gap-5 border-t py-7 first:pt-5 sm:gap-8"
            >
                <span class="pt-1 font-mono text-xs text-primary"
                    >{{ step.number }} /</span
                >
                <div>
                    <h3 class="text-xl font-semibold tracking-tight">
                        {{ step.title }}
                    </h3>
                    <p class="mt-3 text-sm leading-7 text-muted-foreground">
                        {{ step.text }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y bg-muted/40 py-16">
        <div
            class="site-container grid items-center gap-8 md:grid-cols-[1fr_1.1fr] md:gap-20"
        >
            <div>
                <p class="section-kicker">People behind the technology</p>
                <h2
                    class="mt-4 text-3xl leading-tight font-semibold tracking-tight"
                >
                    Based in Purbadhala.<br />Focused on your business.
                </h2>
            </div>
            <div>
                <p class="body-copy">
                    ASR Tech is led by {{ business.owner }}, Owner and Founder,
                    in Netrokona, Bangladesh. We bring development and technical
                    support together so you have a place to turn for your next
                    build and the work that follows.
                </p>
                <Link
                    href="/about"
                    class="mt-5 inline-flex items-center gap-4 text-sm font-semibold text-primary"
                    >Get to know ASR Tech <ArrowUpRight class="size-4"
                /></Link>
            </div>
        </div>
    </section>

    <section
        class="site-container grid gap-12 py-20 sm:py-26 lg:grid-cols-[.7fr_1.3fr] lg:gap-20"
    >
        <div>
            <p class="section-kicker">A few things to know</p>
            <h2 class="section-title mt-5">Questions,<br />answered.</h2>
            <p class="mt-6 text-sm leading-7 text-muted-foreground">
                Something else on your mind?<br /><Link
                    href="/contact"
                    class="font-semibold text-primary underline underline-offset-4"
                    >We'd like to hear it.</Link
                >
            </p>
        </div>
        <div>
            <details
                v-for="question in questions"
                :key="question.question"
                class="faq-row group border-b first:border-t"
            >
                <summary
                    class="flex list-none items-start justify-between gap-5 py-6 text-sm font-semibold sm:text-base"
                >
                    {{ question.question
                    }}<Plus
                        class="mt-0.5 size-4 shrink-0 text-primary transition-transform group-open:rotate-45"
                    />
                </summary>
                <p
                    class="max-w-xl pb-6 text-sm leading-7 text-muted-foreground"
                >
                    {{ question.answer }}
                </p>
            </details>
        </div>
    </section>
    <div
        v-if="navigationPages.length"
        class="site-container flex flex-wrap gap-x-6 gap-y-3 border-t py-6 text-xs text-muted-foreground"
    >
        <span class="font-semibold">More resources</span
        ><Link
            v-for="managedPage in navigationPages"
            :key="managedPage.slug"
            :href="`/pages/${managedPage.slug}`"
            class="hover:text-primary"
            >{{ managedPage.title }}</Link
        >
    </div>
</template>

<style scoped>
.home-hero {
    overflow: clip;
    background: var(--client-surface-soft);
}
.offering-card {
    padding: 2rem;
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    background: var(--background);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}
.offering-card:hover {
    border-color: #72ac9f;
    box-shadow: 0 8px 28px #172c2c06;
}
.offering-icon {
    display: grid;
    place-items: center;
    width: 3rem;
    height: 3rem;
    color: #087f75;
    background: #e8f4ed;
    border: 1px solid #d3e8dc;
    border-radius: 0.7rem;
}
.whmcs {
    background: #f2f6ef;
}
.wordpress {
    background: #f1f6f8;
}
.maintenance {
    background: #f8f7f2;
}
.dark .whmcs,
.dark .wordpress,
.dark .maintenance {
    background: var(--card);
}
.faq-row summary::-webkit-details-marker {
    display: none;
}
@media (max-width: 639px) {
    .offering-card {
        padding: 1.5rem;
    }
}
</style>
