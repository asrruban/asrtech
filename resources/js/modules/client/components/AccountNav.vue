<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Power } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();

const badges = computed(
    () =>
        (page.props.clientBadges ?? {}) as {
            products?: number;
            subscriptions?: number;
            tickets?: number;
            unpaidInvoices?: number;
        },
);

const tabs = computed(() => [
    {
        label: 'Dashboard',
        href: '/client-area',
        prefixes: ['/client-area'],
        exact: true,
        count: null as number | null,
        alert: false,
    },
    {
        label: 'Products',
        href: '/client-area/products',
        prefixes: ['/client-area/products', '/client-area/product'],
        exact: false,
        count: badges.value.products ?? 0,
        alert: false,
    },
    {
        label: 'Subscriptions',
        href: '/client-area/subscriptions',
        prefixes: ['/client-area/subscriptions'],
        exact: false,
        count: badges.value.subscriptions ?? 0,
        alert: false,
    },
    {
        label: 'Tickets',
        href: '/client-area/tickets',
        prefixes: ['/client-area/tickets', '/client-area/ticket'],
        exact: false,
        count: badges.value.tickets ?? 0,
        alert: false,
    },
    {
        label: 'Invoices',
        href: '/client-area/invoices',
        prefixes: ['/client-area/invoices', '/client-area/invoice'],
        exact: false,
        count: badges.value.unpaidInvoices ?? 0,
        alert: (badges.value.unpaidInvoices ?? 0) > 0,
    },
    {
        label: 'Quotes',
        href: '/client-area/quotes',
        prefixes: ['/client-area/quotes'],
        exact: false,
        count: null as number | null,
        alert: false,
    },
    {
        label: 'Projects',
        href: '/client-area/projects',
        prefixes: ['/client-area/projects'],
        exact: false,
        count: null as number | null,
        alert: false,
    },
    {
        label: 'Maintenance',
        href: '/client-area/maintenance',
        prefixes: ['/client-area/maintenance'],
        exact: false,
        count: null as number | null,
        alert: false,
    },
    {
        label: 'Account',
        href: '/client-area/account-details',
        prefixes: [
            '/client-area/account-details',
            '/client-area/change-password',
            '/client-area/security',
        ],
        exact: false,
        count: null as number | null,
        alert: false,
    },
    {
        label: 'Notifications',
        href: '/client-area/notifications',
        prefixes: ['/client-area/notifications'],
        exact: false,
        count: null as number | null,
        alert: false,
    },
    {
        label: 'Affiliate',
        href: '/client-area/affiliate',
        prefixes: ['/client-area/affiliate'],
        exact: false,
        count: null as number | null,
        alert: false,
    },
]);

const isActive = (tab: { prefixes: string[]; exact: boolean }) =>
    tab.exact
        ? page.url.split('?')[0] === tab.prefixes[0]
        : tab.prefixes.some(
              (prefix) =>
                  page.url.split('?')[0] === prefix ||
                  page.url.split('?')[0].startsWith(`${prefix}/`),
          );

const logout = () => router.post('/logout');
</script>

<template>
    <nav
        aria-label="Account navigation"
        class="-mx-4 flex items-center gap-1 overflow-x-auto border-b border-[var(--client-border)] px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            :aria-current="isActive(tab) ? 'page' : undefined"
            class="inline-flex shrink-0 items-center gap-1.5 border-b-[3px] px-3 py-3.5 text-sm font-semibold whitespace-nowrap transition sm:px-4"
            :class="
                isActive(tab)
                    ? 'border-[#087f75] text-[var(--client-accent-dark)]'
                    : 'border-transparent text-[var(--client-muted)] hover:text-[var(--client-ink)]'
            "
        >
            {{ tab.label }}
            <span
                v-if="tab.count !== null"
                class="inline-flex size-5 items-center justify-center rounded-full text-[11px] font-bold"
                :class="
                    tab.alert
                        ? 'bg-orange-500 text-white'
                        : 'bg-[var(--client-surface)] text-[var(--client-muted)]'
                "
            >
                {{ tab.count }}
            </span>
        </Link>
        <button
            type="button"
            class="ml-auto inline-flex shrink-0 items-center gap-1.5 px-3 py-3.5 text-sm font-semibold whitespace-nowrap text-[var(--client-muted)] transition hover:text-[var(--client-ink)]"
            @click="logout"
        >
            <Power class="size-4" /> Log Out
        </button>
    </nav>
</template>
