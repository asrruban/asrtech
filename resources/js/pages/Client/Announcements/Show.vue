<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Megaphone } from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

const props = defineProps<{
    announcement: {
        title: string;
        body: string;
        published_at: string | null;
    };
    more: { title: string; slug: string }[];
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
        :title="props.announcement.title"
        :description="props.announcement.title"
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
                <Megaphone class="size-3.5" />
                {{ formatDate(props.announcement.published_at) }}
            </span>
            <h1
                class="mt-6 max-w-4xl text-3xl font-semibold tracking-tight sm:text-4xl"
            >
                {{ props.announcement.title }}
            </h1>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
        <Link
            href="/announcements"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline"
        >
            <ArrowLeft class="size-4" /> All announcements
        </Link>

        <article
            class="prose prose-slate dark:prose-invert mt-8 max-w-none leading-7 whitespace-pre-wrap"
        >
            {{ props.announcement.body }}
        </article>

        <div v-if="props.more.length > 0" class="mt-14 border-t pt-8">
            <h2 class="text-lg font-bold">More announcements</h2>
            <ul class="mt-4 space-y-2">
                <li v-for="item in props.more" :key="item.slug">
                    <Link
                        :href="`/announcements/${item.slug}`"
                        class="text-sm font-semibold text-primary hover:underline"
                    >
                        {{ item.title }}
                    </Link>
                </li>
            </ul>
        </div>
    </section>
</template>
