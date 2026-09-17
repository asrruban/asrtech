<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
const page = usePage();
const tabs = [
    { label: 'Account details', href: '/client-area/account-details' },
    { label: 'Password', href: '/client-area/change-password' },
    { label: 'Security', href: '/client-area/security' },
];
</script>
<template>
    <nav
        aria-label="Account settings"
        class="mb-4 flex flex-wrap gap-1 rounded-xl border bg-[var(--client-surface)] p-1.5"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            :aria-current="
                page.url.split('?')[0] === tab.href ? 'page' : undefined
            "
            class="flex-1 rounded-lg px-3 py-2.5 text-center text-sm font-semibold transition"
            :class="
                page.url.split('?')[0] === tab.href
                    ? 'bg-[var(--client-accent-soft)] text-[var(--client-accent-dark)]'
                    : 'text-[var(--client-muted)] hover:bg-[var(--client-surface-soft)]'
            "
            >{{ tab.label }}</Link
        >
    </nav>
</template>
