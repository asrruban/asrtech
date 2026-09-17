<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    BookOpen,
    ChartColumn,
    ChevronRight,
    ExternalLink,
    FileSignature,
    Handshake,
    KeyRound,
    Layers3,
    LayoutDashboard,
    LifeBuoy,
    MessageSquareText,
    Megaphone,
    Package,
    PanelsTopLeft,
    ReceiptText,
    RefreshCw,
    Settings,
    Search,
    ShieldCheck,
    Tags,
    Percent,
    ReceiptCent,
    RotateCcw,
    Users,
    Webhook,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppearanceMenu from '@/components/AppearanceMenu.vue';
import AppLogo from '@/components/AppLogo.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import AdminCommandPalette from '@/modules/admin/components/AdminCommandPalette.vue';
import AdminNavUser from '@/modules/admin/components/AdminNavUser.vue';

const page = usePage();
const commandPaletteRef = ref<{ openPalette: () => void } | null>(null);

const breadcrumbNavigation = [
    { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    {
        title: 'Project inquiries',
        href: '/admin/inquiries',
        icon: MessageSquareText,
    },
    { title: 'Projects', href: '/admin/projects', icon: PanelsTopLeft },
    {
        title: 'Maintenance plans',
        href: '/admin/maintenance/plans',
        icon: ShieldCheck,
    },
    {
        title: 'Maintenance requests',
        href: '/admin/maintenance/requests',
        icon: LifeBuoy,
    },
    {
        title: 'Inquiry notifications',
        href: '/admin/settings/inquiry-notifications',
        icon: MessageSquareText,
    },
    { title: 'All Products', href: '/admin/products', icon: Package },
    {
        title: 'Product Reviews',
        href: '/admin/product-reviews',
        icon: MessageSquareText,
    },
    { title: 'Product Types', href: '/admin/product-types', icon: Layers3 },
    { title: 'Categories', href: '/admin/categories', icon: Tags },
    {
        title: 'Subcategories',
        href: '/admin/subcategories',
        icon: Layers3,
    },
    { title: 'Users', href: '/admin/users', icon: Users },
    { title: 'Payment Reliability', href: '/admin/payments', icon: Activity },
    { title: 'Reports', href: '/admin/reports', icon: ChartColumn },
    { title: 'Invoices', href: '/admin/invoices', icon: ReceiptText },
    { title: 'Quotes', href: '/admin/quotes', icon: FileSignature },
    { title: 'Affiliates', href: '/admin/affiliates', icon: Handshake },
    {
        title: 'Refund Requests',
        href: '/admin/refund-requests',
        icon: RotateCcw,
    },
    { title: 'Subscriptions', href: '/admin/subscriptions', icon: RefreshCw },
    { title: 'Promotions', href: '/admin/promotions', icon: Percent },
    { title: 'Tax Rates', href: '/admin/tax-rates', icon: ReceiptCent },
    { title: 'Pages', href: '/admin/pages', icon: PanelsTopLeft },
    { title: 'Announcements', href: '/admin/announcements', icon: Megaphone },
    { title: 'Docs', href: '/admin/docs', icon: BookOpen },
    { title: 'Security', href: '/admin/security', icon: ShieldCheck },
    {
        title: 'Support Tickets',
        href: '/admin/support/tickets',
        icon: LifeBuoy,
    },
    {
        title: 'Ticket Departments',
        href: '/admin/support/departments',
        icon: Settings,
    },
    {
        title: 'General Configuration',
        href: '/admin/settings/general',
        icon: Settings,
    },
    {
        title: 'Payment Gateways',
        href: '/admin/settings/gateways',
        icon: Settings,
    },
    {
        title: 'Email Templates',
        href: '/admin/settings/emailtemplates',
        icon: Settings,
    },
    {
        title: 'Storage Settings',
        href: '/admin/settings/storage',
        icon: Settings,
    },
    {
        title: 'Global SEO',
        href: '/admin/settings/seo',
        icon: Settings,
    },
    {
        title: 'API Tokens',
        href: '/admin/settings/api-tokens',
        icon: KeyRound,
    },
    {
        title: 'Webhooks',
        href: '/admin/settings/webhooks',
        icon: Webhook,
    },
];

const isActive = (href: string) =>
    page.url === href || page.url.startsWith(`${href}/`);
const adminPermissions = computed<string[]>(
    () => (page.props.adminPermissions as string[] | undefined) ?? [],
);
const can = (permission: string) =>
    adminPermissions.value.includes('*') ||
    adminPermissions.value.includes(permission);
const canUseSettings = () =>
    ['settings.manage', 'billing.manage', 'support.manage'].some(can);
const isProductsActive = () =>
    [
        '/admin/products',
        '/admin/product-types',
        '/admin/categories',
        '/admin/subcategories',
        '/admin/product-reviews',
    ].some(isActive);
const isSettingsActive = () =>
    isActive('/admin/settings') || isActive('/admin/support/departments');

const unansweredTickets = computed(
    () => page.props.adminBadges?.unansweredTickets ?? 0,
);
const pendingRefundRequests = computed(
    () => page.props.adminBadges?.pendingRefundRequests ?? 0,
);
const pendingProductReviews = computed(
    () => page.props.adminBadges?.pendingProductReviews ?? 0,
);

const currentNavigation = computed(
    () =>
        breadcrumbNavigation.find((item) => isActive(item.href)) ??
        breadcrumbNavigation[0],
);

const breadcrumbs = computed(() => [
    {
        title: currentNavigation.value.title,
        href: currentNavigation.value.href,
    },
]);
</script>

<template>
    <Head>
        <meta head-key="robots" name="robots" content="noindex,nofollow" />
    </Head>

    <div class="admin-shell">
        <a
            href="#admin-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:text-[#06655e]"
            >Skip to admin content</a
        >
        <AppShell variant="sidebar">
            <Sidebar collapsible="icon" variant="inset">
                <SidebarHeader>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" as-child>
                                <Link href="/admin/dashboard">
                                    <AppLogo />
                                    <span
                                        class="ml-auto rounded-sm bg-sidebar-accent px-1.5 py-0.5 text-[10px] font-semibold tracking-wide text-sidebar-accent-foreground uppercase group-data-[collapsible=icon]:hidden"
                                    >
                                        Admin
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>

                <SidebarContent>
                    <SidebarGroup class="px-2 py-0">
                        <SidebarGroupLabel>Administration</SidebarGroupLabel>
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/dashboard')"
                                    tooltip="Dashboard"
                                >
                                    <Link href="/admin/dashboard">
                                        <LayoutDashboard />
                                        <span>Dashboard</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <Collapsible
                                v-if="can('catalog.manage')"
                                as-child
                                :default-open="isProductsActive()"
                                class="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger as-child>
                                        <SidebarMenuButton
                                            :is-active="isProductsActive()"
                                            tooltip="Products"
                                        >
                                            <Package />
                                            <span>Products</span>
                                            <ChevronRight
                                                class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                            />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <SidebarMenuSub>
                                            <SidebarMenuSubItem>
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/product-types',
                                                        )
                                                    "
                                                >
                                                    <Link
                                                        href="/admin/product-types"
                                                    >
                                                        <span
                                                            >Product Types</span
                                                        >
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                            <SidebarMenuSubItem>
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/product-reviews',
                                                        )
                                                    "
                                                >
                                                    <Link
                                                        href="/admin/product-reviews"
                                                    >
                                                        <span>Reviews</span>
                                                        <span
                                                            v-if="
                                                                pendingProductReviews
                                                            "
                                                            class="ml-auto rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800"
                                                        >
                                                            {{
                                                                pendingProductReviews
                                                            }}
                                                        </span>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                            <SidebarMenuSubItem>
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/products',
                                                        )
                                                    "
                                                >
                                                    <Link
                                                        href="/admin/products"
                                                    >
                                                        <span
                                                            >All Products</span
                                                        >
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                            <SidebarMenuSubItem>
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/categories',
                                                        )
                                                    "
                                                >
                                                    <Link
                                                        href="/admin/categories"
                                                    >
                                                        <span>Categories</span>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                            <SidebarMenuSubItem>
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/subcategories',
                                                        )
                                                    "
                                                >
                                                    <Link
                                                        href="/admin/subcategories"
                                                    >
                                                        <span
                                                            >Subcategories</span
                                                        >
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>

                            <SidebarMenuItem v-if="can('users.view')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="
                                        isActive('/admin/users') ||
                                        isActive('/admin/licenses')
                                    "
                                    tooltip="Users"
                                >
                                    <Link href="/admin/users">
                                        <Users />
                                        <span>Users</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('billing.manage')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/payments')"
                                    tooltip="Payments"
                                >
                                    <Link href="/admin/payments">
                                        <Activity />
                                        <span>Payments</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('billing.manage')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/invoices')"
                                    tooltip="Invoices"
                                >
                                    <Link href="/admin/invoices">
                                        <ReceiptText />
                                        <span>Invoices</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('billing.manage')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="
                                        isActive('/admin/subscriptions')
                                    "
                                    tooltip="Subscriptions"
                                >
                                    <Link href="/admin/subscriptions">
                                        <RefreshCw />
                                        <span>Subscriptions</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('billing.manage')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="
                                        isActive('/admin/refund-requests')
                                    "
                                    tooltip="Refund Requests"
                                >
                                    <Link href="/admin/refund-requests">
                                        <RotateCcw />
                                        <span>Refund Requests</span>
                                        <SidebarMenuBadge
                                            v-if="pendingRefundRequests"
                                        >
                                            {{ pendingRefundRequests }}
                                        </SidebarMenuBadge>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('billing.manage')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/promotions')"
                                    tooltip="Promotions"
                                >
                                    <Link href="/admin/promotions">
                                        <Percent />
                                        <span>Promotions</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('billing.manage')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/tax-rates')"
                                    tooltip="Tax Rates"
                                >
                                    <Link href="/admin/tax-rates">
                                        <ReceiptCent />
                                        <span>Tax Rates</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('content.manage')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/pages')"
                                    tooltip="Pages"
                                >
                                    <Link href="/admin/pages">
                                        <PanelsTopLeft />
                                        <span>Pages</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem>
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/security')"
                                    tooltip="Security"
                                >
                                    <Link href="/admin/security">
                                        <ShieldCheck />
                                        <span>Security</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem>
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/docs')"
                                    tooltip="Docs"
                                >
                                    <Link href="/admin/docs">
                                        <BookOpen />
                                        <span>Docs</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('support.manage')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="
                                        isActive('/admin/support/tickets')
                                    "
                                    tooltip="Support"
                                >
                                    <Link href="/admin/support/tickets">
                                        <LifeBuoy />
                                        <span>Support</span>
                                    </Link>
                                </SidebarMenuButton>
                                <SidebarMenuBadge
                                    v-if="unansweredTickets > 0"
                                    class="rounded-full bg-red-600 px-1.5 text-white"
                                >
                                    {{ unansweredTickets }}
                                </SidebarMenuBadge>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('support.manage')">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/inquiries')"
                                    tooltip="Project inquiries"
                                >
                                    <Link href="/admin/inquiries"
                                        ><MessageSquareText /><span
                                            >Project inquiries</span
                                        ></Link
                                    >
                                </SidebarMenuButton>
                            </SidebarMenuItem>

                            <SidebarMenuItem v-if="can('support.manage')"
                                ><SidebarMenuButton
                                    as-child
                                    :is-active="isActive('/admin/projects')"
                                    tooltip="Projects"
                                    ><Link href="/admin/projects"
                                        ><PanelsTopLeft /><span
                                            >Projects</span
                                        ></Link
                                    ></SidebarMenuButton
                                ></SidebarMenuItem
                            >
                            <SidebarMenuItem v-if="can('billing.manage')"
                                ><SidebarMenuButton
                                    as-child
                                    :is-active="
                                        isActive('/admin/maintenance/plans')
                                    "
                                    tooltip="Maintenance plans"
                                    ><Link href="/admin/maintenance/plans"
                                        ><ShieldCheck /><span
                                            >Maintenance plans</span
                                        ></Link
                                    ></SidebarMenuButton
                                ></SidebarMenuItem
                            >
                            <SidebarMenuItem v-if="can('support.manage')"
                                ><SidebarMenuButton
                                    as-child
                                    :is-active="
                                        isActive('/admin/maintenance/requests')
                                    "
                                    tooltip="Maintenance requests"
                                    ><Link href="/admin/maintenance/requests"
                                        ><LifeBuoy /><span
                                            >Maintenance requests</span
                                        ></Link
                                    ></SidebarMenuButton
                                ></SidebarMenuItem
                            >
                            <SidebarMenuItem v-if="can('settings.manage')"
                                ><SidebarMenuButton
                                    as-child
                                    :is-active="
                                        isActive(
                                            '/admin/settings/inquiry-notifications',
                                        )
                                    "
                                    tooltip="Inquiry notifications"
                                    ><Link
                                        href="/admin/settings/inquiry-notifications"
                                        ><MessageSquareText /><span
                                            >Inquiry notifications</span
                                        ></Link
                                    ></SidebarMenuButton
                                ></SidebarMenuItem
                            >
                            <Collapsible
                                v-if="canUseSettings()"
                                as-child
                                :default-open="isSettingsActive()"
                                class="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger as-child>
                                        <SidebarMenuButton
                                            :is-active="isSettingsActive()"
                                            tooltip="Settings"
                                        >
                                            <Settings />
                                            <span>Settings</span>
                                            <ChevronRight
                                                class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                            />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <SidebarMenuSub>
                                            <SidebarMenuSubItem
                                                v-if="can('settings.manage')"
                                            >
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/settings/general',
                                                        )
                                                    "
                                                    class="font-medium"
                                                >
                                                    <Link
                                                        href="/admin/settings/general"
                                                    >
                                                        <span
                                                            >General
                                                            Configuration</span
                                                        >
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                            <SidebarMenuSubItem
                                                v-if="can('billing.manage')"
                                            >
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/settings/gateways',
                                                        )
                                                    "
                                                    class="font-medium"
                                                >
                                                    <Link
                                                        href="/admin/settings/gateways"
                                                    >
                                                        <span
                                                            >Payment
                                                            Gateways</span
                                                        >
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                            <SidebarMenuSubItem
                                                v-if="can('settings.manage')"
                                            >
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/settings/emailtemplates',
                                                        )
                                                    "
                                                    class="font-medium"
                                                >
                                                    <Link
                                                        href="/admin/settings/emailtemplates"
                                                    >
                                                        <span
                                                            >Email
                                                            Templates</span
                                                        >
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                            <SidebarMenuSubItem
                                                v-if="can('settings.manage')"
                                            >
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/settings/storage',
                                                        )
                                                    "
                                                    class="font-medium"
                                                >
                                                    <Link
                                                        href="/admin/settings/storage"
                                                    >
                                                        <span
                                                            >Storage
                                                            Settings</span
                                                        >
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                            <SidebarMenuSubItem
                                                v-if="can('settings.manage')"
                                            >
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/settings/seo',
                                                        )
                                                    "
                                                    class="font-medium"
                                                >
                                                    <Link
                                                        href="/admin/settings/seo"
                                                    >
                                                        <span>Global SEO</span>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                            <SidebarMenuSubItem
                                                v-if="can('support.manage')"
                                            >
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isActive(
                                                            '/admin/support/departments',
                                                        )
                                                    "
                                                    class="font-medium"
                                                >
                                                    <Link
                                                        href="/admin/support/departments"
                                                    >
                                                        <span
                                                            >Ticket
                                                            Departments</span
                                                        >
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>
                        </SidebarMenu>
                    </SidebarGroup>
                </SidebarContent>

                <SidebarFooter>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child tooltip="View website">
                                <a
                                    href="/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <ExternalLink />
                                    <span>View website</span>
                                </a>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <AdminNavUser />
                    </SidebarMenu>
                </SidebarFooter>
            </Sidebar>

            <AppContent variant="sidebar" class="min-w-0 overflow-x-hidden">
                <div
                    class="flex items-center justify-between border-b bg-card pr-4"
                >
                    <AppSidebarHeader
                        :breadcrumbs="breadcrumbs"
                        class="min-w-0 border-b-0"
                    />
                    <div class="flex shrink-0 items-center gap-2">
                        <AppearanceMenu />
                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted"
                            aria-label="Search administration"
                            aria-keyshortcuts="Meta+K Control+K"
                            @click="commandPaletteRef?.openPalette()"
                        >
                            <Search class="size-4" /><span
                                class="hidden sm:inline"
                                >Search</span
                            ><kbd
                                class="hidden rounded border px-1 text-[10px] sm:inline"
                                >⌘ K</kbd
                            >
                        </button>
                    </div>
                </div>
                <div
                    id="admin-content"
                    tabindex="-1"
                    class="admin-content min-w-0 flex-1"
                >
                    <slot />
                </div>
            </AppContent>

            <Toaster />
            <AdminCommandPalette ref="commandPaletteRef" />
        </AppShell>
    </div>
</template>

<style scoped>
.admin-shell {
    min-height: 100vh;
    background: var(--sidebar);
}
.admin-shell :deep([data-sidebar='menu-button']) {
    min-height: 2.5rem;
    border-radius: 0.55rem;
}
.admin-shell :deep([data-sidebar='menu-button'][data-active='true']) {
    font-weight: 600;
}
.admin-shell :deep([data-sidebar='group-label']) {
    letter-spacing: 0.12em;
    text-transform: uppercase;
    font-size: 0.65rem;
}
.admin-content {
    background: var(--background);
}
.admin-content :deep([data-slot='card']) {
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgb(23 44 44 / 3%);
}
.admin-content :deep(h1) {
    letter-spacing: -0.035em;
}
.admin-content :deep(th) {
    font-weight: 600;
}
.admin-content :deep(tbody tr) {
    transition: background-color 0.15s ease;
}
.admin-content :deep(tbody tr:hover) {
    background-color: color-mix(in srgb, var(--primary) 3%, transparent);
}
.admin-content :deep(input:not([type='checkbox']):not([type='radio'])),
.admin-content :deep(select) {
    min-height: 2.625rem;
}
.admin-content :deep(textarea) {
    line-height: 1.65;
}
@media (prefers-reduced-motion: reduce) {
    .admin-content :deep(*) {
        transition: none !important;
    }
}
</style>
