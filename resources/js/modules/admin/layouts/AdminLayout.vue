<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    BookOpen,
    ChevronRight,
    ExternalLink,
    Layers3,
    LayoutDashboard,
    LifeBuoy,
    MessageSquareText,
    Package,
    PanelsTopLeft,
    ReceiptText,
    RefreshCw,
    Settings,
    ShieldCheck,
    Tags,
    Percent,
    ReceiptCent,
    RotateCcw,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
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
import AdminNavUser from '@/modules/admin/components/AdminNavUser.vue';

const page = usePage();

const breadcrumbNavigation = [
    { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
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
    { title: 'Invoices', href: '/admin/invoices', icon: ReceiptText },
    {
        title: 'Refund Requests',
        href: '/admin/refund-requests',
        icon: RotateCcw,
    },
    { title: 'Subscriptions', href: '/admin/subscriptions', icon: RefreshCw },
    { title: 'Promotions', href: '/admin/promotions', icon: Percent },
    { title: 'Tax Rates', href: '/admin/tax-rates', icon: ReceiptCent },
    { title: 'Pages', href: '/admin/pages', icon: PanelsTopLeft },
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
];

const isActive = (href) => page.url === href || page.url.startsWith(`${href}/`);
const adminPermissions = computed<string[]>(
    () => (page.props.adminPermissions as string[] | undefined) ?? [],
);
const can = (permission) =>
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
                                                    <span>Product Types</span>
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
                                                    isActive('/admin/products')
                                                "
                                            >
                                                <Link href="/admin/products">
                                                    <span>All Products</span>
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
                                                <Link href="/admin/categories">
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
                                                    <span>Subcategories</span>
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
                                :is-active="isActive('/admin/subscriptions')"
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
                                :is-active="isActive('/admin/refund-requests')"
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
                                :is-active="isActive('/admin/support/tickets')"
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
                                                        >Payment Gateways</span
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
                                                    <span>Email Templates</span>
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
                                                        >Storage Settings</span
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
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>

        <Toaster />
    </AppShell>
</template>
