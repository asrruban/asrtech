<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    LifeBuoy,
    Package,
    ReceiptText,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth?.user);
const unpaid = computed(() => page.props.clientBadges?.unpaidInvoices ?? 0);

/** Show only inside the client area — storefront keeps its own nav. */
const visible = computed(
    () =>
        Boolean(user.value) &&
        page.url.split('?')[0].startsWith('/client-area'),
);

const tabs = computed(() => [
    {
        label: 'Home',
        href: '/client-area',
        icon: LayoutDashboard,
        active: page.url.split('?')[0] === '/client-area',
        badge: 0,
    },
    {
        label: 'Products',
        href: '/client-area/products',
        icon: Package,
        active:
            page.url.split('?')[0].startsWith('/client-area/products') ||
            page.url.split('?')[0].startsWith('/client-area/product'),
        badge: 0,
    },
    {
        label: 'Invoices',
        href: '/client-area/invoices',
        icon: ReceiptText,
        active:
            page.url.split('?')[0].startsWith('/client-area/invoices') ||
            page.url.split('?')[0].startsWith('/client-area/invoice'),
        badge: unpaid.value,
    },
    {
        label: 'Support',
        href: '/client-area/tickets',
        icon: LifeBuoy,
        active:
            page.url.split('?')[0].startsWith('/client-area/tickets') ||
            page.url.split('?')[0].startsWith('/client-area/ticket'),
        badge: 0,
    },
    {
        label: 'Account',
        href: '/client-area/account-details',
        icon: UserRound,
        active:
            page.url.split('?')[0].startsWith('/client-area/account-details') ||
            page.url.split('?')[0].startsWith('/client-area/change-password') ||
            page.url.split('?')[0].startsWith('/client-area/security') ||
            page.url.split('?')[0].startsWith('/client-area/notifications'),
        badge: 0,
    },
]);
</script>

<template>
    <template v-if="visible">
        <!-- Flow spacer so the fixed bar never covers content -->
        <div class="h-20 md:hidden" aria-hidden="true" />

        <nav
            class="fixed inset-x-0 bottom-0 z-50 border-t border-[var(--client-border)] bg-[var(--client-surface)]/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl md:hidden"
            aria-label="Client area"
        >
            <div class="grid grid-cols-5">
                <Link
                    v-for="tab in tabs"
                    :key="tab.href"
                    :href="tab.href"
                    :aria-current="tab.active ? 'page' : undefined"
                    class="relative flex flex-col items-center gap-1 py-2.5 text-[10px] font-bold tracking-wide transition"
                    :class="
                        tab.active
                            ? 'text-[var(--client-accent-dark)]'
                            : 'text-[var(--client-muted)] hover:text-[var(--client-ink)]'
                    "
                >
                    <span
                        v-if="tab.active"
                        class="absolute top-0 h-0.5 w-8 rounded-full bg-[#087f75]"
                    />
                    <span class="relative">
                        <component :is="tab.icon" class="size-5" />
                        <span
                            v-if="tab.badge > 0"
                            class="absolute -top-1.5 -right-2.5 flex min-w-4 items-center justify-center rounded-full bg-[#087f75] px-1 text-[9px] leading-4 font-extrabold text-white"
                        >
                            {{ tab.badge > 99 ? '99+' : tab.badge }}
                        </span>
                    </span>
                    {{ tab.label }}
                </Link>
            </div>
        </nav>
    </template>
</template>
