<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, FileText, KeyRound, LifeBuoy } from '@lucide/vue';
withDefaults(
    defineProps<{
        eyebrow?: string;
        title?: string;
        description?: string;
        features?: boolean;
    }>(),
    {
        features: true,
        eyebrow: 'Client area',
        title: 'Your work, in one place.',
        description:
            'Manage your ASR Tech products, licenses, billing, and support from your account.',
    },
);
</script>

<template>
    <section class="bg-[var(--client-surface-soft)] py-12 sm:py-20">
        <div
            class="site-container grid items-center gap-10 lg:grid-cols-[1fr_1fr] lg:gap-24"
        >
            <div class="max-w-lg">
                <Link
                    href="/"
                    class="mb-10 inline-flex items-center gap-2 text-sm font-semibold text-[var(--client-muted)]"
                    >ASR Tech <ArrowUpRight class="size-4"
                /></Link>
                <p class="section-kicker">{{ eyebrow }}</p>
                <h1
                    class="mt-4 text-4xl leading-[1.1] font-semibold tracking-tight text-[var(--client-ink)] sm:text-5xl"
                >
                    {{ title }}
                </h1>
                <p class="mt-5 text-base leading-7 text-[var(--client-muted)]">
                    {{ description }}
                </p>
                <div
                    v-if="features"
                    class="mt-8 hidden space-y-4 border-t border-[var(--client-border)] pt-8 sm:block"
                >
                    <p
                        class="flex items-center gap-3 text-sm text-[var(--client-muted)]"
                    >
                        <KeyRound class="size-5 text-[var(--client-accent)]" />
                        Product licenses and available downloads
                    </p>
                    <p
                        class="flex items-center gap-3 text-sm text-[var(--client-muted)]"
                    >
                        <FileText class="size-5 text-[var(--client-accent)]" />
                        Orders, invoices, and subscriptions
                    </p>
                    <p
                        class="flex items-center gap-3 text-sm text-[var(--client-muted)]"
                    >
                        <LifeBuoy class="size-5 text-[var(--client-accent)]" />
                        Your support conversations
                    </p>
                </div>
            </div>
            <div class="w-full max-w-lg lg:justify-self-end"><slot /></div>
        </div>
    </section>
</template>
