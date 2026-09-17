<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AccountNav from '@/modules/client/components/AccountNav.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
import type { MaintenanceRequest } from '@/types/maintenance';
defineProps<{ requests: MaintenanceRequest[] }>();
</script>
<template>
    <SeoHead
        title="My maintenance"
        description="Your maintenance scope, requests and billing."
    />
    <section class="site-container py-8">
        <AccountNav />
        <div class="flex flex-wrap items-end justify-between gap-5 py-10">
            <div>
                <p class="section-kicker">Client Area</p>
                <h1 class="section-title mt-3">Your maintenance</h1>
                <p class="body-copy mt-3">
                    Review service requests, agreed scope and billing.
                </p>
            </div>
            <Link href="/maintenance" class="button-secondary"
                >Explore plans</Link
            >
        </div>
        <div v-if="!requests.length" class="surface-card p-10">
            <h2 class="text-xl font-semibold">No maintenance requests yet</h2>
            <p class="body-copy mt-3">
                Explore available plans or contact us about ongoing technical
                care.
            </p>
            <Link href="/contact" class="button-primary mt-6"
                >Discuss maintenance</Link
            >
        </div>
        <div v-else class="grid gap-5 md:grid-cols-2">
            <Link
                v-for="entry in requests"
                :key="entry.id"
                :href="`/client-area/maintenance/${entry.id}`"
                class="surface-card p-6 transition hover:border-[var(--client-accent)]"
                ><div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="section-kicker">Request #{{ entry.id }}</p>
                    <span
                        class="rounded-full bg-[var(--client-surface-soft)] px-3 py-1 text-xs font-semibold capitalize"
                        >{{ entry.status.replaceAll('_', ' ') }}</span
                    >
                </div>
                <h2 class="mt-4 text-xl font-semibold">
                    {{ entry.plan.name }}
                </h2>
                <p class="body-copy mt-3 line-clamp-2">
                    {{ entry.requirements }}
                </p>
                <p
                    class="mt-5 text-sm font-semibold text-[var(--client-accent-dark)]"
                >
                    View scope and updates →
                </p></Link
            >
        </div>
    </section>
</template>
