<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Megaphone } from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

const props = defineProps<{
    announcements: {
        title: string;
        slug: string;
        excerpt: string;
        published_at: string | null;
    }[];
}>();

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          }).format(new Date(value))
        : '';
</script>

<template>
    <SeoHead
        title="Announcements"
        description="Product news, updates, and company announcements."
    />

    <section
        class="border-b bg-[var(--client-surface-soft)] py-14 text-[var(--client-ink)] sm:py-20"
    >
        <div
            class="absolute inset-0 bg-[linear-gradient(to_right,#fff_1px,transparent_1px),linear-gradient(to_bottom,#fff_1px,transparent_1px)] bg-[size:48px_48px] opacity-[0.035]"
        ></div>
        <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-[var(--client-border)] bg-[var(--client-surface)] px-3 py-1.5 text-xs font-bold text-[var(--client-accent-dark)]"
            >
                <Megaphone class="size-3.5" /> News
            </span>
            <h1 class="mt-6 text-4xl font-semibold tracking-tight sm:text-5xl">
                Announcements
            </h1>
            <p
                class="mt-5 max-w-3xl text-base leading-7 text-[var(--client-muted)]"
            >
                Product news, service updates, and company announcements.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
        <p
            v-if="props.announcements.length === 0"
            class="py-16 text-center text-sm text-muted-foreground"
        >
            No announcements yet — check back soon.
        </p>

        <div v-else class="space-y-4">
            <Link
                v-for="announcement in props.announcements"
                :key="announcement.slug"
                :href="`/announcements/${announcement.slug}`"
                class="block rounded-xl border bg-card p-6 transition hover:border-primary/40 hover:shadow-md"
            >
                <p class="text-xs font-semibold text-muted-foreground">
                    {{ formatDate(announcement.published_at) }}
                </p>
                <h2 class="mt-2 text-lg font-bold">{{ announcement.title }}</h2>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                    {{ announcement.excerpt }}
                </p>
                <span
                    class="mt-3 inline-block text-sm font-semibold text-primary"
                >
                    Read more →
                </span>
            </Link>
        </div>
    </section>
</template>
