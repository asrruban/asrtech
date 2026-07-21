<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    Bot,
    Braces,
    CheckCircle2,
    CloudCog,
    Code2,
    Database,
    Gauge,
    GitBranch,
    Layers3,
    Network,
    Rocket,
    ShieldCheck,
    Workflow,
} from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

defineProps<{ seo: Record<string, unknown> }>();

const services = [
    {
        title: 'Business platforms',
        description:
            'Purpose-built portals, dashboards, internal tools, and SaaS products that fit your real operating model.',
        icon: Layers3,
    },
    {
        title: 'WHMCS engineering',
        description:
            'Secure modules, hooks, provisioning workflows, billing extensions, and customer experience improvements.',
        icon: CloudCog,
    },
    {
        title: 'API integrations',
        description:
            'Reliable connections between payment, hosting, CRM, analytics, support, and infrastructure services.',
        icon: Network,
    },
    {
        title: 'Workflow automation',
        description:
            'Replace manual handoffs with observable, maintainable automation and thoughtful exception handling.',
        icon: Workflow,
    },
    {
        title: 'Modernization',
        description:
            'Untangle legacy systems, improve performance, and migrate incrementally without disrupting your team.',
        icon: GitBranch,
    },
    {
        title: 'Performance and security',
        description:
            'Architecture reviews, query optimization, access control, audit trails, and production hardening.',
        icon: ShieldCheck,
    },
];

const platforms = [
    'WHMCS',
    'Laravel',
    'Vue.js',
    'REST APIs',
    'Stripe',
    'cPanel',
    'Plesk',
    'Proxmox',
    'AWS',
    'Docker',
    'MySQL',
    'Redis',
];

const process = [
    {
        number: '01',
        title: 'Discover',
        description:
            'We map goals, users, constraints, integrations, and the outcome that matters.',
    },
    {
        number: '02',
        title: 'Design',
        description:
            'You see the architecture, milestones, interface direction, and delivery plan before build-out.',
    },
    {
        number: '03',
        title: 'Build',
        description:
            'We deliver in reviewable increments with tests, clear communication, and production readiness.',
    },
    {
        number: '04',
        title: 'Evolve',
        description:
            'After launch, we monitor, improve, and extend the platform as the business grows.',
    },
];
</script>

<template>
    <SeoHead
        title="Custom Software Development"
        description="Custom Laravel, Vue, WHMCS, API integration, and hosting software development."
        :seo="seo"
        type="website"
    />

    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_18%_24%,rgba(37,99,235,0.34),transparent_32%),radial-gradient(circle_at_82%_35%,rgba(6,182,212,0.2),transparent_30%)]"
        ></div>
        <div
            class="absolute inset-0 bg-[linear-gradient(to_right,#fff_1px,transparent_1px),linear-gradient(to_bottom,#fff_1px,transparent_1px)] bg-[size:56px_56px] opacity-[0.035]"
        ></div>
        <div
            class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 py-24 sm:px-6 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:py-32"
        >
            <div>
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-4 py-2 text-xs font-bold text-cyan-200"
                >
                    <Code2 class="size-4" /> Software built around your business
                </div>
                <h1
                    class="mt-7 text-5xl leading-[1.05] font-extrabold tracking-tight sm:text-6xl"
                >
                    Turn complex workflows into
                    <span
                        class="bg-gradient-to-r from-blue-400 to-cyan-300 bg-clip-text text-transparent"
                    >
                        clear software.
                    </span>
                </h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300">
                    We design and build dependable platforms, WHMCS extensions,
                    integrations, and automation for hosting companies and
                    modern service businesses.
                </p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <Link
                        href="/support/ticket"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-extrabold shadow-lg shadow-blue-600/20 transition hover:bg-blue-500"
                    >
                        Discuss your project <ArrowRight class="size-4" />
                    </Link>
                    <a
                        href="#capabilities"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-sm font-extrabold transition hover:bg-white/10"
                    >
                        Explore capabilities <ArrowUpRight class="size-4" />
                    </a>
                </div>
                <div
                    class="mt-10 flex flex-wrap gap-x-7 gap-y-3 text-sm text-slate-400"
                >
                    <span class="inline-flex items-center gap-2">
                        <CheckCircle2 class="size-4 text-emerald-400" /> Secure
                        by design
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <CheckCircle2 class="size-4 text-emerald-400" />
                        Milestone delivery
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <CheckCircle2 class="size-4 text-emerald-400" />
                        Long-term support
                    </span>
                </div>
            </div>

            <div class="relative">
                <div
                    class="absolute -inset-8 rounded-full bg-blue-500/20 blur-3xl"
                ></div>
                <div
                    class="relative overflow-hidden rounded-3xl border border-white/10 bg-white/[0.06] p-5 shadow-2xl backdrop-blur"
                >
                    <div
                        class="flex items-center justify-between border-b border-white/10 pb-4"
                    >
                        <div class="flex gap-1.5">
                            <span
                                class="size-2.5 rounded-full bg-rose-400"
                            ></span>
                            <span
                                class="size-2.5 rounded-full bg-amber-400"
                            ></span>
                            <span
                                class="size-2.5 rounded-full bg-emerald-400"
                            ></span>
                        </div>
                        <span
                            class="text-[11px] font-bold tracking-widest text-slate-500 uppercase"
                        >
                            Operations workspace
                        </span>
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-[.7fr_1.3fr]">
                        <div class="space-y-3">
                            <div class="rounded-2xl bg-blue-600 p-4">
                                <Bot class="size-6" />
                                <p class="mt-7 text-xs text-blue-100">
                                    Automations today
                                </p>
                                <p class="mt-1 text-3xl font-extrabold">
                                    1,248
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-slate-900/60 p-4"
                            >
                                <Gauge class="size-5 text-cyan-300" />
                                <p class="mt-4 text-xs text-slate-400">
                                    Success rate
                                </p>
                                <p class="mt-1 text-xl font-extrabold">
                                    99.97%
                                </p>
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-slate-900/60 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold">Live workflow</p>
                                <span
                                    class="rounded-full bg-emerald-400/10 px-2 py-1 text-[10px] font-bold text-emerald-300"
                                >
                                    Healthy
                                </span>
                            </div>
                            <div class="mt-6 space-y-3">
                                <div
                                    v-for="(label, index) in [
                                        'Order received',
                                        'Payment verified',
                                        'Service provisioned',
                                        'Customer notified',
                                    ]"
                                    :key="label"
                                    class="flex items-center gap-3"
                                >
                                    <span
                                        class="flex size-7 items-center justify-center rounded-lg text-[10px] font-extrabold"
                                        :class="
                                            index < 3
                                                ? 'bg-emerald-400/15 text-emerald-300'
                                                : 'bg-blue-400/15 text-blue-300'
                                        "
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <span
                                        class="flex-1 rounded-lg bg-white/[0.05] px-3 py-2 text-xs text-slate-300"
                                    >
                                        {{ label }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-6 flex h-20 items-end gap-1.5">
                                <span
                                    v-for="height in [
                                        36, 54, 43, 68, 58, 78, 64, 88, 73, 92,
                                    ]"
                                    :key="height"
                                    class="flex-1 rounded-t bg-gradient-to-t from-blue-600 to-cyan-300"
                                    :style="{ height: `${height}%` }"
                                ></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b bg-white dark:bg-slate-950">
        <div
            class="mx-auto grid max-w-7xl grid-cols-2 gap-px bg-slate-200 px-4 sm:grid-cols-4 sm:px-6 lg:px-8 dark:bg-white/10"
        >
            <div class="bg-white px-5 py-6 dark:bg-slate-950">
                <p class="text-2xl font-extrabold">Laravel + Vue</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Modern application engineering
                </p>
            </div>
            <div class="bg-white px-5 py-6 dark:bg-slate-950">
                <p class="text-2xl font-extrabold">API-first</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Integrations that stay maintainable
                </p>
            </div>
            <div class="bg-white px-5 py-6 dark:bg-slate-950">
                <p class="text-2xl font-extrabold">Tested</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Confidence through automation
                </p>
            </div>
            <div class="bg-white px-5 py-6 dark:bg-slate-950">
                <p class="text-2xl font-extrabold">Observable</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Clear logs, metrics, and audit trails
                </p>
            </div>
        </div>
    </section>

    <section id="capabilities" class="px-4 py-24 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="max-w-3xl">
                <p
                    class="text-xs font-extrabold tracking-[0.2em] text-blue-600 uppercase"
                >
                    What we build
                </p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-tight">
                    Deep technical work, translated into business progress.
                </h2>
                <p class="mt-5 text-base leading-7 text-muted-foreground">
                    From one critical integration to a complete operational
                    platform, the architecture stays clear, secure, and ready to
                    evolve.
                </p>
            </div>

            <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="service in services"
                    :key="service.title"
                    class="group rounded-2xl border bg-card p-6 transition duration-300 hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl hover:shadow-blue-950/5"
                >
                    <span
                        class="flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-500/10"
                    >
                        <component :is="service.icon" class="size-6" />
                    </span>
                    <h3 class="mt-6 text-lg font-extrabold">
                        {{ service.title }}
                    </h3>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">
                        {{ service.description }}
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section
        class="overflow-hidden bg-[#071c3a] px-4 py-24 text-white sm:px-6 lg:px-8"
    >
        <div class="mx-auto grid max-w-7xl items-center gap-14 lg:grid-cols-2">
            <div>
                <p
                    class="text-xs font-extrabold tracking-[0.2em] text-cyan-300 uppercase"
                >
                    Connected systems
                </p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-tight">
                    Your stack should work as one system.
                </h2>
                <p class="mt-5 max-w-xl text-base leading-7 text-slate-300">
                    We connect the tools your team already trusts, remove
                    fragile manual steps, and create a reliable operational
                    layer between them.
                </p>
                <div class="mt-8 flex flex-wrap gap-2">
                    <span
                        v-for="platform in platforms"
                        :key="platform"
                        class="rounded-lg border border-white/10 bg-white/[0.06] px-3 py-2 text-xs font-bold text-slate-300"
                    >
                        {{ platform }}
                    </span>
                </div>
            </div>
            <div
                class="relative min-h-96 rounded-3xl border border-white/10 bg-white/[0.04] p-7"
            >
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(37,99,235,.25),transparent_58%)]"
                ></div>
                <div
                    class="relative flex h-full min-h-80 items-center justify-center"
                >
                    <div
                        class="absolute top-7 left-7 rounded-2xl border border-white/10 bg-slate-950 p-4"
                    >
                        <Database class="size-6 text-cyan-300" />
                        <p class="mt-2 text-xs font-bold">Customer data</p>
                    </div>
                    <div
                        class="absolute top-7 right-7 rounded-2xl border border-white/10 bg-slate-950 p-4"
                    >
                        <Braces class="size-6 text-violet-300" />
                        <p class="mt-2 text-xs font-bold">External API</p>
                    </div>
                    <div
                        class="absolute bottom-7 left-7 rounded-2xl border border-white/10 bg-slate-950 p-4"
                    >
                        <CloudCog class="size-6 text-blue-300" />
                        <p class="mt-2 text-xs font-bold">Infrastructure</p>
                    </div>
                    <div
                        class="absolute right-7 bottom-7 rounded-2xl border border-white/10 bg-slate-950 p-4"
                    >
                        <Rocket class="size-6 text-emerald-300" />
                        <p class="mt-2 text-xs font-bold">Delivery</p>
                    </div>
                    <div
                        class="relative flex size-32 items-center justify-center rounded-full border border-blue-300/30 bg-blue-600 shadow-2xl shadow-blue-500/30"
                    >
                        <Workflow class="size-12" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-24 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="text-center">
                <p
                    class="text-xs font-extrabold tracking-[0.2em] text-blue-600 uppercase"
                >
                    Delivery process
                </p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-tight">
                    A clear path from idea to production.
                </h2>
            </div>
            <div class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="step in process"
                    :key="step.number"
                    class="relative overflow-hidden rounded-2xl border p-6"
                >
                    <span
                        class="text-5xl font-black text-slate-100 dark:text-white/[0.06]"
                    >
                        {{ step.number }}
                    </span>
                    <h3 class="mt-5 text-lg font-extrabold">
                        {{ step.title }}
                    </h3>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">
                        {{ step.description }}
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section class="px-4 pb-24 sm:px-6 lg:px-8">
        <div
            class="relative mx-auto max-w-7xl overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 to-cyan-600 px-7 py-14 text-white sm:px-12"
        >
            <div
                class="absolute -top-24 -right-20 size-72 rounded-full border-[48px] border-white/10"
            ></div>
            <div
                class="relative flex flex-col items-start justify-between gap-8 lg:flex-row lg:items-center"
            >
                <div class="max-w-2xl">
                    <p class="text-sm font-bold text-cyan-100">
                        Have a difficult workflow?
                    </p>
                    <h2 class="mt-2 text-3xl font-extrabold sm:text-4xl">
                        Let’s turn it into dependable software.
                    </h2>
                    <p class="mt-4 text-sm leading-6 text-blue-100">
                        Share the problem, the systems involved, and what
                        success looks like. We will help you define the next
                        practical step.
                    </p>
                </div>
                <Link
                    href="/support/ticket"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-white px-6 py-3.5 text-sm font-extrabold text-blue-700 shadow-xl transition hover:-translate-y-0.5"
                >
                    Start a conversation <ArrowRight class="size-4" />
                </Link>
            </div>
        </div>
    </section>
</template>
