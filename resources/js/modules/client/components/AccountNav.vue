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
]);

const isActive = (tab: { prefixes: string[]; exact: boolean }) =>
    tab.exact
        ? page.url === tab.prefixes[0]
        : tab.prefixes.some(
              (prefix) =>
                  page.url === prefix || page.url.startsWith(`${prefix}/`),
          );

const logout = () => router.post('/logout');
</script>

<template>
    <nav
        class="-mx-4 flex items-center gap-1 overflow-x-auto border-b border-white/15 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            class="inline-flex shrink-0 items-center gap-1.5 border-b-[3px] px-3 py-3.5 text-sm font-bold tracking-wide whitespace-nowrap transition sm:px-4"
            :class="
                isActive(tab)
                    ? 'border-[#7ed957] text-white'
                    : 'border-transparent text-white/75 hover:text-white'
            "
        >
            {{ tab.label }}
            <span
                v-if="tab.count !== null"
                class="inline-flex size-5 items-center justify-center rounded-full text-[11px] font-bold"
                :class="
                    tab.alert
                        ? 'bg-orange-500 text-white'
                        : 'bg-white/90 text-slate-700'
                "
            >
                {{ tab.count }}
            </span>
        </Link>
        <button
            type="button"
            class="ml-auto inline-flex shrink-0 items-center gap-1.5 px-3 py-3.5 text-sm font-bold whitespace-nowrap text-white/75 transition hover:text-white"
            @click="logout"
        >
            <Power class="size-4" /> Log Out
        </button>
    </nav>
</template>
