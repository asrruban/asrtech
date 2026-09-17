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
            'Installation and configuration guidance for ASR Tech products.',
        href: '#documentation',
        icon: BookOpen,
        color: 'from-sky-400 to-[#087f75]',
    },
    {
        title: 'Project inquiries',
        description: 'Discuss a website, application, or custom integration.',
        href: '/services',
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
        question: 'Can ASR Tech install or configure a product for me?',
        answer: 'Describe your product and environment in a support ticket. We can review the work needed and discuss a suitable scope.',
    },
    {
        question: 'How do I request custom software development?',
        answer: 'Visit Services to explore our capabilities, then use the contact form to share your workflow, integrations, and expected outcome.',
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
        title="Support"
        description="Product guides, technical help, and support conversations with ASR Tech."
        :seo="seo"
    />
    <section class="border-b bg-[var(--client-surface-soft)] py-14 sm:py-20">
        <div
            class="site-container grid items-end gap-8 lg:grid-cols-[1.1fr_1fr]"
        >
            <div>
                <p class="section-kicker">Support &amp; resources</p>
                <h1
                    class="mt-4 text-4xl font-semibold tracking-tight text-[var(--client-ink)] sm:text-5xl"
                >
                    Keep moving forward.
                </h1>
                <p class="body-copy mt-5 max-w-xl">
                    Find product guidance, review a support conversation, or
                    tell us what needs attention.
                </p>
            </div>
            <div>
                <label
                    for="support-search"
                    class="mb-2 block text-sm font-semibold"
                    >Search questions and product guides</label
                >
                <div
                    class="flex items-center gap-3 rounded-xl border bg-[var(--client-surface)] px-4 py-4 focus-within:ring-2 focus-within:ring-[#087f75]"
                >
                    <Search
                        aria-hidden="true"
                        class="size-5 text-[var(--client-accent)]"
                    />
                    <input
                        id="support-search"
                        v-model="query"
                        type="search"
                        placeholder="Try installation, downloads, or a product…"
                        class="min-w-0 flex-1 bg-transparent text-sm outline-none"
                    />
                </div>
            </div>
        </div>
    </section>
    <section class="site-container py-10 sm:py-14">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <component
                :is="action.href.startsWith('#') ? 'a' : Link"
                v-for="action in actions"
                :key="action.title"
                :href="action.href"
                class="group rounded-2xl border bg-[var(--client-surface)] p-6 transition hover:border-[#087f75]"
            >
                <component
                    :is="action.icon"
                    aria-hidden="true"
                    class="size-6 text-[var(--client-accent)]"
                />
                <h2
                    class="mt-6 flex items-center justify-between gap-2 text-base font-semibold"
                >
                    {{ action.title }} <ArrowRight class="size-4 shrink-0" />
                </h2>
                <p class="mt-2 text-sm leading-6 text-[var(--client-muted)]">
                    {{ action.description }}
                </p>
            </component>
        </div>
        <div class="mt-14 grid items-start gap-10 lg:grid-cols-2 lg:gap-16">
            <div>
                <div class="mb-5 flex items-center gap-3">
                    <CircleHelp class="size-5 text-[var(--client-accent)]" />
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Common questions
                    </h2>
                </div>
                <div
                    class="divide-y rounded-2xl border bg-[var(--client-surface)] px-6"
                >
                    <details
                        v-for="item in filteredQuestions"
                        :key="item.question"
                        class="group py-5"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-5 text-sm font-semibold"
                        >
                            {{ item.question
                            }}<span
                                aria-hidden="true"
                                class="text-xl font-normal text-[var(--client-accent)] group-open:rotate-45"
                                >+</span
                            >
                        </summary>
                        <p
                            class="mt-3 pr-4 text-sm leading-7 text-[var(--client-muted)]"
                        >
                            {{ item.answer }}
                        </p>
                    </details>
                    <p
                        v-if="!filteredQuestions.length"
                        role="status"
                        class="py-10 text-sm text-[var(--client-muted)]"
                    >
                        No questions match “{{ query }}”. Try another search or
                        open a ticket.
                    </p>
                </div>
            </div>
            <div id="documentation" class="scroll-mt-24">
                <div class="mb-5 flex items-center gap-3">
                    <BookOpen class="size-5 text-[var(--client-accent)]" />
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Product documentation
                    </h2>
                </div>
                <div
                    class="divide-y rounded-2xl border bg-[var(--client-surface)]"
                >
                    <Link
                        v-for="product in filteredDocumentation"
                        :key="product.slug"
                        :href="product.documentation_path"
                        class="flex items-center justify-between gap-4 p-6 transition hover:bg-[var(--client-surface-soft)]"
                    >
                        <div>
                            <h3 class="text-sm font-semibold">
                                {{ product.name }}
                            </h3>
                            <p class="mt-1 text-sm text-[var(--client-muted)]">
                                {{
                                    product.documentation_title ||
                                    'Installation and usage guide'
                                }}
                            </p>
                        </div>
                        <ArrowRight
                            class="size-4 shrink-0 text-[var(--client-accent)]"
                        />
                    </Link>
                    <p
                        v-if="!filteredDocumentation.length"
                        role="status"
                        class="p-6 text-sm leading-7 text-[var(--client-muted)]"
                    >
                        {{
                            query
                                ? 'No guides match your search. Try another product name.'
                                : 'Published guides will appear here. Open a support ticket for help with a product.'
                        }}
                    </p>
                </div>
            </div>
        </div>
        <div
            class="mt-16 flex flex-col items-start justify-between gap-6 rounded-2xl bg-[var(--client-accent-soft)] p-7 sm:flex-row sm:items-center sm:p-10"
        >
            <div class="flex items-start gap-4">
                <Headphones
                    class="mt-1 size-7 shrink-0 text-[var(--client-accent)]"
                />
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Let’s work through it.
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-[var(--client-muted)]"
                    >
                        Describe the issue and include the details needed to
                        reproduce it.
                    </p>
                </div>
            </div>
            <Link
                :href="departments.length ? '/support/ticket' : '/contact'"
                class="button-primary shrink-0"
                >{{
                    departments.length
                        ? 'Open a support ticket'
                        : 'Contact ASR Tech'
                }}
                <ArrowRight class="size-4"
            /></Link>
        </div>
    </section>
</template>
