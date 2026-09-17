<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import { date, label } from '@/modules/projects/types';
defineProps<{
    projects: {
        data: {
            id: number;
            title: string;
            description: string;
            status: string;
            target_date: string | null;
            milestones_count: number;
            pending_approvals_count: number;
        }[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();
</script>
<template>
    <Head title="My projects" /><ClientAreaHero
        title="Your projects"
        subtitle="Follow milestones, share files and keep decisions together in one place."
    />
    <section class="site-container py-10">
        <div
            v-if="!projects.data.length"
            class="surface-card p-8 text-center sm:p-14"
        >
            <h2 class="text-xl font-semibold">
                Your next project starts with a conversation.
            </h2>
            <p
                class="mx-auto mt-3 max-w-lg text-sm leading-7 text-muted-foreground"
            >
                When your project is ready, ASR Tech will create a workspace
                here for its scope, milestones and updates.
            </p>
            <Link href="/contact" class="button-primary mt-6"
                >Discuss your project</Link
            >
        </div>
        <div v-else class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="project in projects.data"
                :key="project.id"
                :href="`/client-area/projects/${project.id}`"
                class="surface-card flex min-w-0 flex-col p-6 transition hover:border-primary"
                ><span
                    class="self-start rounded-full bg-muted px-3 py-1 text-xs font-semibold capitalize"
                    >{{ label(project.status) }}</span
                >
                <h2 class="mt-5 text-xl font-semibold break-words">
                    {{ project.title }}
                </h2>
                <p
                    class="mt-3 line-clamp-3 text-sm leading-6 break-words text-muted-foreground"
                >
                    {{ project.description }}
                </p>
                <p class="mt-6 text-xs text-muted-foreground">
                    {{ project.milestones_count }} milestones · Target
                    {{ date(project.target_date) }}
                </p>
                <p
                    v-if="project.pending_approvals_count"
                    class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800"
                >
                    {{ project.pending_approvals_count }} approval request(s)
                    awaiting your response
                </p>
                <span class="mt-5 text-sm font-semibold text-primary"
                    >Open workspace →</span
                ></Link
            >
        </div>
        <nav
            v-if="projects.prev_page_url || projects.next_page_url"
            aria-label="Project pagination"
            class="mt-7 flex justify-between gap-4"
        >
            <Link
                v-if="projects.prev_page_url"
                :href="projects.prev_page_url"
                class="button-secondary"
                >Previous</Link
            ><Link
                v-if="projects.next_page_url"
                :href="projects.next_page_url"
                class="button-secondary"
                >Next</Link
            >
        </nav>
    </section>
</template>
