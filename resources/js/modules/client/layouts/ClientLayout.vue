<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowRight,
    ArrowUpRight,
    Bell,
    Menu,
    ShoppingBag,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import MobileTabBar from '@/modules/client/components/MobileTabBar.vue';

const page = usePage();
const site = computed(() => page.props.site);
const business = computed(() => page.props.business);
const user = computed(() => page.props.auth?.user);
const cartCount = computed(() => page.props.cartState?.count ?? 0);
const unread = computed(
    () => page.props.clientBadges?.unreadNotifications ?? 0,
);
const impersonating = computed(() => page.props.auth?.impersonating === true);
const mobileOpen = ref(false);
const menuButton = ref<HTMLButtonElement | null>(null);
const navigation = [
    { label: 'Home', href: '/' },
    { label: 'Services', href: '/services' },
    { label: 'Products', href: '/products' },
    { label: 'About', href: '/about' },
    { label: 'Contact', href: '/contact' },
];
const showCatalogPreview = computed(
    () =>
        site.value.localPreview &&
        (page.url === '/' ||
            /^\/(products|categories|cart|checkout)(\/|\?|$)/.test(page.url)),
);
const isActive = (href: string) =>
    href === '/' ? page.url.split('?')[0] === '/' : page.url.startsWith(href);
function closeMenu() {
    mobileOpen.value = false;
    menuButton.value?.focus();
}
watch(
    () => page.url,
    () => {
        mobileOpen.value = false;
    },
);
</script>

<template>
    <div class="client-site min-h-screen bg-background text-foreground">
        <a class="skip-link" href="#main-content">Skip to content</a>
        <div
            v-if="impersonating"
            class="flex flex-wrap items-center justify-center gap-3 bg-amber-100 px-4 py-2 text-center text-sm text-amber-950"
        >
            <span>Admin view: browsing as {{ user?.name }}</span>
            <Link
                href="/impersonation/leave"
                method="post"
                as="button"
                class="font-semibold underline"
                >Return to admin</Link
            >
        </div>
        <header class="site-header" @keydown.esc="closeMenu">
            <div
                class="site-container flex h-22 items-center justify-between gap-5"
            >
                <Link
                    href="/"
                    class="brand-wordmark shrink-0"
                    aria-label="ASR Tech home"
                >
                    <template v-if="site.logoDarkUrl && site.logoLightUrl">
                        <img
                            :src="site.logoDarkUrl"
                            :alt="business.name"
                            class="h-10 max-w-44 object-contain dark:hidden"
                        />
                        <img
                            :src="site.logoLightUrl"
                            :alt="business.name"
                            class="hidden h-10 max-w-44 object-contain dark:block"
                        />
                    </template>
                    <img
                        v-else-if="site.logoUrl"
                        :src="site.logoUrl"
                        :alt="business.name"
                        class="h-10 max-w-44 object-contain"
                    />
                    <template v-else
                        ><span class="wordmark-symbol" aria-hidden="true"
                            ><span></span><span></span><span></span></span
                        ><span
                            >ASR<span class="font-normal"> Tech</span
                            ><span class="text-primary">.</span></span
                        ></template
                    >
                </Link>
                <nav
                    class="hidden items-center gap-7 lg:flex"
                    aria-label="Main navigation"
                >
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                        class="nav-link"
                        :class="{ 'is-active': isActive(item.href) }"
                        >{{ item.label }}</Link
                    >
                </nav>
                <div class="flex items-center gap-2 sm:gap-3">
                    <Link
                        href="/cart"
                        class="header-icon relative"
                        :aria-label="`Shopping cart, ${cartCount} items`"
                        ><ShoppingBag class="size-5" /><span
                            v-if="cartCount"
                            class="count-badge"
                            >{{ cartCount > 99 ? '99+' : cartCount }}</span
                        ></Link
                    >
                    <Link
                        v-if="user"
                        href="/client-area/notifications"
                        class="header-icon relative hidden sm:inline-flex"
                        :aria-label="`Notifications, ${unread} unread`"
                        ><Bell class="size-5" /><span
                            v-if="unread"
                            class="count-badge"
                            >{{ unread > 99 ? '99+' : unread }}</span
                        ></Link
                    >
                    <Link
                        :href="user ? '/client-area' : '/login'"
                        class="button-secondary hidden min-h-11 px-4 text-sm sm:inline-flex"
                        >Client Area <ArrowUpRight class="size-4"
                    /></Link>
                    <button
                        ref="menuButton"
                        class="header-icon lg:hidden"
                        :aria-expanded="mobileOpen"
                        aria-controls="mobile-navigation"
                        :aria-label="
                            mobileOpen ? 'Close navigation' : 'Open navigation'
                        "
                        @click="mobileOpen = !mobileOpen"
                    >
                        <X v-if="mobileOpen" class="size-5" /><Menu
                            v-else
                            class="size-5"
                        />
                    </button>
                </div>
            </div>
            <nav
                v-if="mobileOpen"
                id="mobile-navigation"
                class="site-container mobile-navigation lg:hidden"
                aria-label="Mobile navigation"
            >
                <Link
                    v-for="item in navigation"
                    :key="item.href"
                    :href="item.href"
                    :aria-current="isActive(item.href) ? 'page' : undefined"
                    class="flex items-center justify-between border-b py-3 font-medium"
                    :class="{ 'text-primary': isActive(item.href) }"
                    >{{ item.label }}<ArrowUpRight class="size-4"
                /></Link>
                <div class="flex flex-wrap gap-3 py-5">
                    <Link
                        :href="user ? '/client-area' : '/login'"
                        class="button-primary"
                        >Client Area <ArrowRight class="size-4" /></Link
                    ><Link href="/support" class="button-secondary"
                        >Support</Link
                    ><Link
                        v-if="user"
                        href="/logout"
                        method="post"
                        as="button"
                        class="button-secondary"
                        >Sign out</Link
                    >
                </div>
            </nav>
        </header>

        <main id="main-content" tabindex="-1">
            <div
                v-if="showCatalogPreview"
                class="border-b border-amber-200 bg-amber-50 px-4 py-2 text-center text-xs leading-5 text-amber-950"
                role="note"
            >
                Local preview includes sample catalog records. Product
                availability, pricing, and license terms need business approval
                before launch.
            </div>
            <slot />
        </main>
        <MobileTabBar />

        <footer class="site-footer">
            <div class="site-container">
                <div
                    class="grid gap-10 border-b border-white/15 py-14 md:grid-cols-[1.2fr_1fr] md:gap-20"
                >
                    <div>
                        <span class="section-kicker text-teal-300"
                            >Let's build something useful</span
                        >
                        <p
                            class="mt-4 max-w-xl text-3xl leading-tight font-semibold tracking-tight sm:text-4xl"
                        >
                            Your next idea.<br />Our next conversation.
                        </p>
                    </div>
                    <div class="flex flex-col items-start justify-end gap-5">
                        <p class="max-w-md text-sm leading-7 text-slate-300">
                            A new website, a better workflow, or a technical
                            issue that needs a closer look. Tell us where you'd
                            like to start.
                        </p>
                        <Link
                            href="/contact"
                            class="inline-flex items-center gap-8 border-b border-teal-300 pb-2 font-semibold text-teal-200"
                            >Discuss Your Project <ArrowUpRight class="size-5"
                        /></Link>
                    </div>
                </div>
                <div
                    class="grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.4fr_.8fr_.9fr_1.1fr]"
                >
                    <div>
                        <Link
                            href="/"
                            class="text-2xl font-semibold tracking-tight"
                            >ASR <span class="font-normal">Tech</span
                            ><span class="text-teal-300">.</span></Link
                        >
                        <p
                            class="mt-4 max-w-xs text-sm leading-7 text-slate-300"
                        >
                            Development, practical software, and technical care
                            for your business.
                        </p>
                        <p
                            class="mt-6 flex items-center gap-2 text-xs text-teal-200"
                        >
                            <ArrowDownRight class="size-4" /> Based in
                            Bangladesh. Built around your needs.
                        </p>
                    </div>
                    <div>
                        <p class="footer-label">Explore</p>
                        <nav class="footer-links" aria-label="Footer explore">
                            <Link href="/maintenance">Maintenance plans</Link
                            ><Link href="/services">Services</Link
                            ><Link href="/products">Products</Link
                            ><Link href="/about">About ASR Tech</Link
                            ><Link href="/contact">Contact</Link
                            ><Link href="/announcements">Announcements</Link>
                        </nav>
                    </div>
                    <div>
                        <p class="footer-label">Client resources</p>
                        <nav class="footer-links" aria-label="Client resources">
                            <Link href="/client-area/projects"
                                >Project workspace</Link
                            ><Link href="/client-area">Client Area</Link
                            ><Link href="/support">Support center</Link
                            ><Link href="/support/ticket">Open a ticket</Link
                            ><Link href="/client-area/products"
                                >Licenses & downloads</Link
                            ><Link href="/client-area/invoices">Invoices</Link
                            ><Link
                                v-if="user"
                                href="/logout"
                                method="post"
                                as="button"
                                class="text-left"
                                >Sign out</Link
                            >
                        </nav>
                    </div>
                    <div>
                        <p class="footer-label">Find us</p>
                        <address
                            class="mt-5 text-sm leading-7 text-slate-300 not-italic"
                        >
                            {{ business.address }}
                        </address>
                        <a
                            :href="business.facebook"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-4 inline-flex items-center gap-2 text-sm text-teal-200"
                            >Facebook <ArrowUpRight class="size-4" /><span
                                class="sr-only"
                            >
                                (opens in a new tab)</span
                            ></a
                        ><a
                            v-if="site.supportEmail"
                            :href="`mailto:${site.supportEmail}`"
                            class="mt-3 block text-sm break-all text-slate-300"
                            >{{ site.supportEmail }}</a
                        >
                    </div>
                </div>
                <div
                    class="flex flex-col justify-between gap-5 border-t border-white/15 py-6 text-xs text-slate-400 sm:flex-row"
                >
                    <p>
                        © {{ new Date().getFullYear() }} {{ business.name }}.
                        All rights reserved.
                    </p>
                    <nav
                        class="flex flex-wrap gap-x-6 gap-y-3"
                        aria-label="Legal"
                    >
                        <Link href="/terms-of-service">Terms of Service</Link
                        ><Link href="/privacy-policy">Privacy Policy</Link
                        ><Link href="/refund-policy">Refund Policy</Link>
                    </nav>
                </div>
            </div>
        </footer>
    </div>
</template>
