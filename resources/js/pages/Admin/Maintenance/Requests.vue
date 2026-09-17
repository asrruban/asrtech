<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { MaintenanceRequest } from '@/types/maintenance';
defineProps<{
    requests: {
        data: MaintenanceRequest[];
        prev_page_url: string | null;
        next_page_url: string | null;
        current_page: number;
        last_page: number;
        total: number;
    };
}>();
</script>
<template>
    <Head title="Maintenance requests" />
    <div class="space-y-7 p-4 md:p-8">
        <div>
            <p
                class="text-xs font-semibold tracking-widest text-primary uppercase"
            >
                Technical care
            </p>
            <h1 class="mt-2 text-3xl font-semibold">Maintenance requests</h1>
            <p class="mt-3 text-sm text-muted-foreground">
                Review scope, record updates and connect existing customer
                billing.
            </p>
        </div>
        <div v-if="!requests.data.length" class="rounded-xl border bg-card p-8">
            <h2 class="font-semibold">No maintenance requests yet</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                Customer requests will appear here after they submit a published
                plan.
            </p>
        </div>
        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="entry in requests.data"
                :key="entry.id"
                :href="`/admin/maintenance/requests/${entry.id}`"
                class="rounded-xl border bg-card p-5 transition hover:border-primary"
                ><div class="flex flex-wrap justify-between gap-3 text-xs">
                    <span>#{{ entry.id }}</span
                    ><span class="font-semibold capitalize">{{
                        entry.status.replaceAll('_', ' ')
                    }}</span>
                </div>
                <h2 class="mt-4 text-lg font-semibold">
                    {{ entry.plan.name }}
                </h2>
                <p class="mt-2 text-sm">{{ entry.user?.name }}</p>
                <p class="mt-1 truncate text-sm text-muted-foreground">
                    {{ entry.user?.email }}
                </p>
                <p class="mt-4 line-clamp-2 text-sm text-muted-foreground">
                    {{ entry.requirements }}
                </p></Link
            >
        </div>
        <nav
            v-if="requests.last_page > 1"
            aria-label="Request pages"
            class="flex items-center justify-between gap-4"
        >
            <Link
                v-if="requests.prev_page_url"
                :href="requests.prev_page_url"
                class="button-secondary"
                >Previous</Link
            ><span class="text-sm"
                >Page {{ requests.current_page }} of
                {{ requests.last_page }}</span
            ><Link
                v-if="requests.next_page_url"
                :href="requests.next_page_url"
                class="button-secondary"
                >Next</Link
            >
        </nav>
    </div>
</template>
