<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const tabs = [
    { label: 'Edit Account Details', href: '/client-area/account-details' },
    { label: 'Change Password', href: '/client-area/change-password' },
];

const isActive = (href: string) => page.url === href;
</script>

<template>
    <div class="flex gap-7 px-1">
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            class="relative pb-4 text-sm font-bold tracking-wide whitespace-nowrap transition"
            :class="
                isActive(tab.href)
                    ? 'text-white'
                    : 'text-white/70 hover:text-white'
            "
        >
            {{ tab.label }}
            <span
                v-if="isActive(tab.href)"
                class="absolute bottom-0 left-1/2 -ml-2 size-0 border-x-8 border-b-8 border-x-transparent border-b-card"
            ></span>
        </Link>
    </div>
</template>
