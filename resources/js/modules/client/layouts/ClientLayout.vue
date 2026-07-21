<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    ExternalLink,
    Mail,
    Menu,
    ShoppingCart,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
const page = usePage();
const site = computed(() => page.props.site);
const user = computed(() => page.props.auth?.user);
const cartCount = computed(() => page.props.cartState?.count ?? 0);
const impersonating = computed(
    () => Boolean(page.props.auth?.admin) && Boolean(page.props.auth?.user),
);
const mobileOpen = ref(false);
const navigation = computed(() => [
    { label: 'Home', href: '/' },
    { label: 'Products', href: '/products' },
    { label: 'Software Development', href: '/software-development' },
    { label: 'Support', href: '/support' },
    ...(user.value ? [{ label: 'Client Area', href: '/client-area' }] : []),
]);
const isActive = (href) =>
    href === '/' ? page.url === '/' : page.url.startsWith(href);
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <div
            v-if="impersonating"
            class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 bg-amber-500 px-4 py-2 text-center text-sm font-semibold text-amber-950"
        >
            <span> Admin view — you are browsing as {{ user?.name }}. </span>
            <Link
                href="/impersonation/leave"
                method="post"
                as="button"
                class="underline underline-offset-2 hover:no-underline"
            >
                Return to admin
            </Link>
        </div>
        <header
            class="sticky top-0 z-40 border-b bg-background/90 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <Link
                    href="/"
                    class="flex items-center gap-3 font-bold tracking-tight"
                >
                    <template v-if="site.logoDarkUrl && site.logoLightUrl">
                        <img
                            :src="site.logoDarkUrl"
                            :alt="site.companyName"
                            class="h-9 w-auto dark:hidden"
                        />
                        <img
                            :src="site.logoLightUrl"
                            :alt="site.companyName"
                            class="hidden h-9 w-auto dark:block"
                        />
                    </template>
                    <img
                        v-else-if="site.logoUrl"
                        :src="site.logoUrl"
                        :alt="site.companyName"
                        class="h-9 w-auto"
                    />
                    <span
                        v-else
                        class="flex size-9 items-center justify-center rounded-lg bg-slate-950 text-sm text-white"
                        >ASR</span
                    >
                    <span class="text-lg">{{ site.companyName }}</span>
                </Link>

                <nav class="hidden items-center gap-7 md:flex">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        class="text-sm font-medium transition-colors hover:text-primary"
                        :class="
                            isActive(item.href)
                                ? 'text-foreground'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ item.label }}
                    </Link>
                    <Link
                        href="/cart"
                        class="relative inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-slate-100 hover:text-primary dark:hover:bg-white/5"
                        aria-label="Shopping cart"
                    >
                        <ShoppingCart class="size-4" />
                        <span
                            v-if="cartCount > 0"
                            class="absolute -top-1 -right-1 flex min-w-4 items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] leading-4 font-bold text-white"
                        >
                            {{ cartCount > 99 ? '99+' : cartCount }}
                        </span>
                    </Link>
                    <Link
                        v-if="!user"
                        href="/login"
                        class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
                    >
                        Sign in
                    </Link>
                    <Link
                        v-else
                        href="/logout"
                        method="post"
                        as="button"
                        class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
                    >
                        Sign out
                    </Link>
                    <Button as-child size="sm">
                        <a :href="`mailto:${site.supportEmail || ''}`"
                            >Contact us <ArrowRight class="size-4"
                        /></a>
                    </Button>
                </nav>

                <button
                    class="rounded-md p-2 md:hidden"
                    aria-label="Toggle menu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <X v-if="mobileOpen" class="size-5" />
                    <Menu v-else class="size-5" />
                </button>
            </div>
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm transition-opacity md:hidden"
                @click="mobileOpen = false"
            ></div>

            <div
                class="fixed top-0 bottom-0 left-0 z-50 w-4/5 max-w-sm transform bg-background p-6 shadow-2xl transition-transform duration-300 ease-in-out md:hidden"
                :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="mb-8 flex items-center justify-between">
                    <Link
                        href="/"
                        class="flex items-center gap-3 font-bold tracking-tight"
                        @click="mobileOpen = false"
                    >
                        <template v-if="site.logoDarkUrl && site.logoLightUrl">
                            <img
                                :src="site.logoDarkUrl"
                                :alt="site.companyName"
                                class="h-8 w-auto dark:hidden"
                            />
                            <img
                                :src="site.logoLightUrl"
                                :alt="site.companyName"
                                class="hidden h-8 w-auto dark:block"
                            />
                        </template>
                        <img
                            v-else-if="site.logoUrl"
                            :src="site.logoUrl"
                            :alt="site.companyName"
                            class="h-8 w-auto"
                        />
                        <span
                            v-else
                            class="flex size-8 items-center justify-center rounded-lg bg-slate-950 text-xs text-white"
                            >ASR</span
                        >
                        <span class="text-base">{{ site.companyName }}</span>
                    </Link>
                    <button
                        class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-white"
                        aria-label="Close menu"
                        @click="mobileOpen = false"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <nav class="space-y-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        class="block rounded-md px-4 py-3 text-sm font-medium transition-colors hover:bg-slate-100 hover:text-primary dark:hover:bg-white/5"
                        :class="
                            isActive(item.href)
                                ? 'bg-slate-100 text-primary dark:bg-white/10 dark:text-white'
                                : 'text-foreground'
                        "
                        @click="mobileOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div
                    class="mt-8 border-t border-slate-100 pt-6 dark:border-white/10"
                >
                    <Link
                        href="/cart"
                        class="flex items-center justify-between rounded-md px-4 py-3 text-sm font-medium text-foreground transition-colors hover:bg-slate-100 hover:text-primary dark:hover:bg-white/5"
                        @click="mobileOpen = false"
                    >
                        <span class="flex items-center gap-2">
                            <ShoppingCart class="size-4" /> Shopping cart
                        </span>
                        <span
                            v-if="cartCount > 0"
                            class="rounded-full bg-blue-600 px-2 py-0.5 text-xs font-bold text-white"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>
                    <Link
                        v-if="!user"
                        href="/login"
                        class="block rounded-md px-4 py-3 text-sm font-medium text-foreground transition-colors hover:bg-slate-100 hover:text-primary dark:hover:bg-white/5"
                        @click="mobileOpen = false"
                    >
                        Sign in
                    </Link>
                    <Link
                        v-else
                        href="/logout"
                        method="post"
                        as="button"
                        class="block w-full rounded-md px-4 py-3 text-left text-sm font-medium text-foreground transition-colors hover:bg-slate-100 hover:text-primary dark:hover:bg-white/5"
                        @click="mobileOpen = false"
                    >
                        Sign out
                    </Link>

                    <Button as-child class="mt-4 w-full">
                        <a :href="`mailto:${site.supportEmail || ''}`">
                            Contact us <ArrowRight class="ml-2 size-4" />
                        </a>
                    </Button>
                </div>
            </div>
        </header>

        <main><slot /></main>

        <footer class="border-t bg-slate-950 text-slate-300">
            <div
                class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:grid-cols-2 sm:px-6 lg:grid-cols-[1.2fr_.8fr_.8fr_.9fr] lg:px-8"
            >
                <div>
                    <p class="text-lg font-semibold text-white">
                        {{ site.companyName }}
                    </p>
                    <p class="mt-3 max-w-sm text-sm leading-6 text-slate-400">
                        {{ site.tagline }}
                    </p>
                </div>
                <div>
                    <p class="font-semibold text-white">Explore</p>
                    <div class="mt-3 space-y-2 text-sm">
                        <Link class="block hover:text-white" href="/products"
                            >All products</Link
                        >
                        <Link
                            class="block hover:text-white"
                            href="/products?type=whmcs_module"
                            >WHMCS modules</Link
                        >
                        <Link
                            class="block hover:text-white"
                            href="/products?type=template"
                            >Templates</Link
                        >
                        <Link
                            class="block hover:text-white"
                            href="/software-development"
                            >Software development</Link
                        >
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-white">Support & legal</p>
                    <div class="mt-3 space-y-2 text-sm">
                        <Link class="block hover:text-white" href="/support"
                            >Support center</Link
                        >
                        <Link
                            class="block hover:text-white"
                            href="/support/ticket"
                            >Open a ticket</Link
                        >
                        <Link
                            class="block hover:text-white"
                            href="/terms-of-service"
                            >Terms of Service</Link
                        >
                        <Link
                            class="block hover:text-white"
                            href="/privacy-policy"
                            >Privacy Policy</Link
                        >
                        <Link
                            class="block hover:text-white"
                            href="/refund-policy"
                            >Refund Policy</Link
                        >
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-white">Contact</p>
                    <a
                        v-if="site.supportEmail"
                        :href="`mailto:${site.supportEmail}`"
                        class="mt-3 flex items-center gap-2 text-sm hover:text-white"
                        ><Mail class="size-4" /> {{ site.supportEmail }}</a
                    >
                    <div class="mt-4 flex gap-3">
                        <a
                            v-if="site.social.linkedin"
                            :href="site.social.linkedin"
                            aria-label="LinkedIn"
                            ><ExternalLink class="size-5"
                        /></a>
                        <a
                            v-if="site.social.github"
                            :href="site.social.github"
                            aria-label="GitHub"
                            ><ExternalLink class="size-5"
                        /></a>
                    </div>
                </div>
            </div>
            <div
                class="border-t border-white/10 py-5 text-center text-xs text-slate-500"
            >
                © {{ new Date().getFullYear() }} {{ site.companyName }}. All
                rights reserved.
            </div>
        </footer>
    </div>
</template>
