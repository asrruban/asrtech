<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    CircleHelp,
    Headphones,
    MessageSquare,
    Search,
    Sparkles,
    TicketCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface Department {
    id: number;
    name: string;
    description: string | null;
}

interface DocumentationProduct {
    name: string;
    slug: string;
    documentation_title: string | null;
    documentation_path: string;
}

const props = defineProps<{
    departments: Department[];
    documentationProducts: DocumentationProduct[];
    seo: Record<string, unknown>;
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user);
const query = ref('');

const actions = computed(() => [
    {
        title: 'My support tickets',
        description: user.value
            ? 'Review conversations and reply to your open requests.'
            : 'Sign in to review your support conversations.',
        href: user.value ? '/client-area/tickets' : '/login',
        icon: TicketCheck,
        color: 'from-emerald-400 to-emerald-600',
    },
    {
        title: 'Product guides',
        description:
            'Installation and configuration guidance for ASRTech products.',
        href: '#documentation',
        icon: BookOpen,
        color: 'from-sky-400 to-blue-600',
    },
    {
        title: 'Software project help',
        description:
            'Talk through integrations, automation, or a custom platform.',
        href: '/software-development',
        icon: Sparkles,
        color: 'from-violet-400 to-purple-600',
    },
    {
        title: 'Open a ticket',
        description:
            'Choose the right department and send your request securely.',
        href: '/support/ticket',
        icon: MessageSquare,
        color: 'from-orange-400 to-rose-500',
    },
]);

const questions = [
    {
        question: 'Where can I download a purchased product?',
        answer: 'Sign in to the client area, open Products, and select the active license to access its available downloads and license details.',
    },
    {
        question: 'What should I include in a technical support ticket?',
        answer: 'Include the product version, WHMCS and PHP versions, the steps that reproduce the issue, and any relevant error message. Never include passwords or secret keys.',
    },
    {
        question: 'Can ASRTech install or configure a product for me?',
        answer: 'Yes. Choose the sales or general support department and describe your environment so we can recommend the appropriate installation service.',
    },
    {
        question: 'How do I request custom software development?',
        answer: 'Open the software development page, review our capabilities, and submit a ticket with your workflow, integration targets, and expected outcome.',
    },
];

const normalizedQuery = computed(() => query.value.trim().toLowerCase());
const filteredQuestions = computed(() =>
    questions.filter((item) =>
        `${item.question} ${item.answer}`
            .toLowerCase()
            .includes(normalizedQuery.value),
    ),
);
const filteredDocumentation = computed(() =>
    props.documentationProducts.filter((product) =>
        `${product.name} ${product.documentation_title ?? ''}`
            .toLowerCase()
            .includes(normalizedQuery.value),
    ),
);
</script>

<template>
    <SeoHead
        title="Support Center"
        description="Find product documentation, answers, and the right ASRTech support department."
        :seo="seo"
    />

    <section
        class="relative overflow-hidden bg-[radial-gradient(circle_at_75%_18%,rgba(78,184,255,0.3),transparent_30%),linear-gradient(135deg,#083b8a_0%,#075fc4_52%,#0792df_100%)] text-white"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#fff_1px,transparent_1px),linear-gradient(to_bottom,#fff_1px,transparent_1px)] bg-[size:46px_46px] opacity-[0.06]"
        ></div>
        <div
            class="relative mx-auto max-w-6xl px-4 pt-20 pb-44 sm:px-6 lg:px-8"
        >
            <div class="mx-auto max-w-3xl text-center">
                <p
                    class="text-xs font-extrabold tracking-[0.22em] text-cyan-200 uppercase"
                >
                    Help when you need it
                </p>
                <h1
                    class="mt-4 text-4xl font-extrabold tracking-tight sm:text-6xl"
                >
                    Support <span class="font-light">Center</span>
                </h1>
                <p
                    class="mx-auto mt-5 max-w-2xl text-base leading-7 text-blue-100/85"
                >
                    Search common questions, open product documentation, or
                    connect with the team best equipped to help.
                </p>
                <label
                    class="mx-auto mt-9 flex max-w-2xl items-center gap-3 rounded-xl bg-white px-5 py-4 text-slate-900 shadow-2xl ring-1 shadow-blue-950/25 ring-white/30"
                >
                    <Search class="size-5 text-blue-600" />
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Search for an answer or product guide..."
                        class="min-w-0 flex-1 border-0 bg-transparent text-sm outline-none placeholder:text-slate-400"
                    />
                    <span
                        class="hidden text-xs font-semibold text-slate-400 sm:inline"
                    >
                        Search support
                    </span>
                </label>
            </div>
        </div>
    </section>

    <section class="relative z-10 -mt-32 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <component
                :is="action.href.startsWith('#') ? 'a' : Link"
                v-for="action in actions"
                :key="action.title"
                :href="action.href"
                class="group relative min-h-64 overflow-hidden rounded-2xl bg-gradient-to-br p-6 text-white shadow-xl transition duration-300 hover:-translate-y-2 hover:shadow-2xl"
                :class="action.color"
            >
                <div
                    class="absolute -top-12 -right-10 size-44 rounded-full border-[28px] border-white/10"
                ></div>
                <component
                    :is="action.icon"
                    class="relative size-10 stroke-[1.6]"
                />
                <h2 class="relative mt-12 text-xl font-extrabold">
                    {{ action.title }}
                </h2>
                <p class="relative mt-3 text-sm leading-6 text-white/80">
                    {{ action.description }}
                </p>
                <ArrowRight
                    class="absolute right-6 bottom-6 size-5 transition-transform group-hover:translate-x-1"
                />
            </component>
        </div>
    </section>

    <section
        class="bg-slate-50 px-4 pt-18 pb-24 sm:px-6 lg:px-8 dark:bg-slate-950"
    >
        <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-2">
            <div
                class="overflow-hidden rounded-2xl border bg-white shadow-sm dark:bg-slate-900"
            >
                <div class="flex items-center gap-3 border-b px-6 py-5">
                    <span
                        class="rounded-xl bg-blue-50 p-2.5 text-blue-600 dark:bg-blue-500/10"
                    >
                        <CircleHelp class="size-5" />
                    </span>
                    <div>
                        <h2 class="font-extrabold">Common questions</h2>
                        <p class="text-xs text-muted-foreground">
                            Quick answers from our team
                        </p>
                    </div>
                </div>
                <div class="divide-y">
                    <details
                        v-for="item in filteredQuestions"
                        :key="item.question"
                        class="group px-6 py-4"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-bold"
                        >
                            {{ item.question }}
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-lg text-slate-500 transition group-open:rotate-45 dark:bg-white/5"
                                >+</span
                            >
                        </summary>
                        <p
                            class="mt-3 pr-8 text-sm leading-6 text-muted-foreground"
                        >
                            {{ item.answer }}
                        </p>
                    </details>
                    <p
                        v-if="filteredQuestions.length === 0"
                        class="px-6 py-10 text-center text-sm text-muted-foreground"
                    >
                        No common questions match your search.
                    </p>
                </div>
            </div>

            <div
                id="documentation"
                class="overflow-hidden rounded-2xl border bg-white shadow-sm dark:bg-slate-900"
            >
                <div class="flex items-center gap-3 border-b px-6 py-5">
                    <span
                        class="rounded-xl bg-cyan-50 p-2.5 text-cyan-600 dark:bg-cyan-500/10"
                    >
                        <BookOpen class="size-5" />
                    </span>
                    <div>
                        <h2 class="font-extrabold">Product documentation</h2>
                        <p class="text-xs text-muted-foreground">
                            Installation and usage guides
                        </p>
                    </div>
                </div>
                <div class="divide-y">
                    <Link
                        v-for="product in filteredDocumentation"
                        :key="product.slug"
                        :href="product.documentation_path"
                        class="group flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50 dark:hover:bg-white/[0.03]"
                    >
                        <div>
                            <p class="text-sm font-bold">{{ product.name }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    product.documentation_title ||
                                    'Product guide'
                                }}
                            </p>
                        </div>
                        <ArrowRight
                            class="size-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-600"
                        />
                    </Link>
                    <p
                        v-if="filteredDocumentation.length === 0"
                        class="px-6 py-10 text-center text-sm text-muted-foreground"
                    >
                        No product guides match your search.
                    </p>
                </div>
            </div>
        </div>

        <div
            class="mx-auto mt-16 flex max-w-6xl flex-col items-start justify-between gap-6 rounded-3xl bg-slate-950 px-7 py-9 text-white sm:flex-row sm:items-center sm:px-10"
        >
            <div class="flex items-start gap-4">
                <span class="rounded-2xl bg-white/10 p-3 text-cyan-300">
                    <Headphones class="size-7" />
                </span>
                <div>
                    <p class="text-sm font-bold text-cyan-300">
                        Still need help?
                    </p>
                    <h2 class="mt-1 text-2xl font-extrabold">
                        Choose the right support team.
                    </h2>
                    <p class="mt-2 text-sm text-slate-400">
                        {{ departments.length }} public department{{
                            departments.length === 1 ? '' : 's'
                        }}
                        available.
                    </p>
                </div>
            </div>
            <Link
                href="/support/ticket"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-extrabold transition hover:bg-blue-500"
            >
                View departments <ArrowRight class="size-4" />
            </Link>
        </div>
    </section>
</template>
