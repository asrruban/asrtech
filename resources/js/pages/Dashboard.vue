<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    FileText,
    LifeBuoy,
    Package,
    Palette,
    ShieldCheck,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
const page = usePage();
const user = computed(() => page.props.auth.user);
const shortcuts = [
    {
        title: 'Your products',
        description: 'View licenses, product details, and available downloads.',
        href: '/client-area/products',
        icon: Package,
    },
    {
        title: 'Invoices & billing',
        description: 'Review invoices and manage your subscriptions.',
        href: '/client-area/invoices',
        icon: FileText,
    },
    {
        title: 'Support tickets',
        description: 'Continue a conversation or open a new request.',
        href: '/client-area/tickets',
        icon: LifeBuoy,
    },
    {
        title: 'Account details',
        description: 'Keep your profile and billing details up to date.',
        href: '/client-area/account-details',
        icon: UserRound,
    },
    {
        title: 'Account security',
        description: 'Manage two-factor authentication and recovery codes.',
        href: '/client-area/security',
        icon: ShieldCheck,
    },
    {
        title: 'Appearance',
        description:
            'Choose your display preference for supported account pages.',
        href: '/settings/appearance',
        icon: Palette,
    },
];
</script>
<template>
    <SeoHead
        title="Account dashboard"
        description="Your ASR Tech account, products, billing, and support."
    />
    <ClientAreaHero
        :title="`Welcome, ${user.name}`"
        subtitle="Everything you need to manage your work with ASR Tech."
    />
    <section class="site-container py-10 sm:py-14">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold tracking-tight">
                Your account at a glance
            </h2>
            <Link
                href="/client-area"
                class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--client-accent)]"
                >View account overview <ArrowRight class="size-4"
            /></Link>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="item in shortcuts"
                :key="item.href"
                :href="item.href"
                class="rounded-2xl border bg-[var(--client-surface)] p-7 transition hover:border-[#087f75]"
                ><component
                    :is="item.icon"
                    class="size-6 text-[var(--client-accent)]"
                />
                <h3
                    class="mt-6 flex items-center justify-between gap-3 text-lg font-semibold"
                >
                    {{ item.title }}<ArrowRight class="size-4" />
                </h3>
                <p class="mt-2 text-sm leading-7 text-[var(--client-muted)]">
                    {{ item.description }}
                </p></Link
            >
        </div>
    </section>
</template>
