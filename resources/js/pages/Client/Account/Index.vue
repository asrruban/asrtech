<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    LifeBuoy,
    Package,
    ReceiptText,
    ShoppingCart,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AccountCard from '@/modules/client/components/AccountCard.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface DueInvoice {
    id: number;
    invoice_number: string;
    due_at: string | null;
    currency: string;
    total: string;
}

interface ActiveProduct {
    id: number;
    product: {
        name: string;
        slug: string;
        url: string;
        featured_image?: string | null;
    } | null;
    expires_at: string | null;
    billing_cycle: string | null;
}

interface TicketItem {
    id: number;
    ticket_number: string;
    subject: string;
    status: string;
    status_label: string;
    department: string | null;
    last_reply_at: string | null;
}

interface OrderItem {
    order_number: string;
    currency: string;
    amount: string | number;
    billing_cycle: string;
    status: string;
    created_at: string;
    product: { name: string; slug: string; url: string };
}

const props = defineProps<{
    account: {
        name: string;
        email: string;
        address: string[];
    };
    totalDue: string;
    currency: string;
    dueInvoices: DueInvoice[];
    activeProducts: ActiveProduct[];
    tickets: TicketItem[];
    orders: OrderItem[];
}>();

// Active Products card pagination, WHMCS-style "4/6" footer.
const PER_PAGE = 4;
const productPage = ref(0);
const productPages = computed(() =>
    Math.max(1, Math.ceil(props.activeProducts.length / PER_PAGE)),
);
const visibleProducts = computed(() =>
    props.activeProducts.slice(
        productPage.value * PER_PAGE,
        productPage.value * PER_PAGE + PER_PAGE,
    ),
);
const shownCount = computed(() =>
    Math.min((productPage.value + 1) * PER_PAGE, props.activeProducts.length),
);

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const cycleLabel = (cycle: string | null) =>
    cycle === null
        ? 'Lifetime'
        : cycle === 'one_time'
          ? 'Lifetime'
          : cycle === 'yearly'
            ? 'Annual'
            : label(cycle);

const ticketStatusClass = (status: string) =>
    ({
        open: 'bg-[#eff9ef] text-[#357e37] dark:bg-[#4fb250]/10 dark:text-[#84d780]',
        customer_reply:
            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        answered:
            'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        in_progress:
            'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
        on_hold:
            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    })[status] ?? 'bg-slate-100 text-slate-600';

const orderStatusClass = (status: string) =>
    ({
        paid: 'bg-[#eff9ef] text-[#357e37] dark:bg-[#4fb250]/10 dark:text-[#84d780]',
        pending:
            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        failed: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        refunded: 'bg-slate-100 text-slate-600 dark:bg-white/10',
    })[status] ?? 'bg-slate-100 text-slate-600';

// ModulesGarden-style "$285.36 USD" display.
const moneyUsd = (currency: string, amount: string | number) =>
    `${new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount))} ${currency}`;

const money = (currency: string, amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const formatDate = (date: string | null) =>
    date
        ? new Intl.DateTimeFormat('en', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(new Date(date))
        : '—';
</script>

<template>
    <SeoHead
        title="Client area"
        description="Your products, invoices, and support tickets."
    />

    <ClientAreaHero title="Dashboard" overlap />

    <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
        <div class="-mt-24 grid gap-6 lg:grid-cols-3">
            <AccountCard
                :account="props.account"
                :total-due="props.totalDue"
                :currency="props.currency"
            />

            <!-- Support tickets card (Active Projects slot in WHMCS) -->
            <div
                class="flex min-h-[420px] flex-col overflow-hidden rounded-xl bg-card shadow-lg"
            >
                <div class="border-b px-6 py-4">
                    <h2 class="font-bold tracking-tight">Support Tickets</h2>
                </div>
                <div
                    v-if="props.tickets.length === 0"
                    class="flex flex-1 flex-col items-center justify-center gap-5 p-8 text-center"
                >
                    <span
                        class="flex size-24 items-center justify-center rounded-full border-2 border-muted text-muted-foreground/40"
                    >
                        <LifeBuoy class="size-11" />
                    </span>
                    <p class="text-sm text-muted-foreground">
                        You have no open support tickets yet.
                    </p>
                    <Link
                        href="/client-area/tickets/create"
                        class="rounded-md bg-[#5cb85c] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4cae4c]"
                    >
                        Open New Ticket
                    </Link>
                </div>
                <template v-else>
                    <div class="flex-1 divide-y">
                        <Link
                            v-for="ticket in props.tickets"
                            :key="ticket.id"
                            :href="`/client-area/ticket/${ticket.id}`"
                            class="flex items-center justify-between gap-3 px-6 py-4 transition hover:bg-muted/40"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold">
                                    #{{ ticket.ticket_number }} —
                                    {{ ticket.subject }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ ticket.department ?? 'General' }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold"
                                :class="ticketStatusClass(ticket.status)"
                            >
                                {{ ticket.status_label }}
                            </span>
                        </Link>
                    </div>
                    <div class="border-t px-6 py-3 text-center">
                        <Link
                            href="/client-area/tickets"
                            class="text-sm font-bold text-[#4fb250] hover:underline"
                        >
                            View all tickets
                        </Link>
                    </div>
                </template>
            </div>

            <!-- Active products card -->
            <div
                class="flex min-h-[420px] flex-col overflow-hidden rounded-xl bg-card shadow-lg"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b px-6 py-4"
                >
                    <h2 class="font-bold tracking-tight">Active Products</h2>
                    <Link
                        href="/products"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-muted-foreground transition hover:text-[#4fb250]"
                    >
                        <ShoppingCart class="size-3.5" /> Order New Product
                    </Link>
                </div>
                <div
                    v-if="props.activeProducts.length === 0"
                    class="flex flex-1 flex-col items-center justify-center gap-5 p-8 text-center"
                >
                    <span
                        class="flex size-24 items-center justify-center rounded-full border-2 border-muted text-muted-foreground/40"
                    >
                        <Package class="size-11" />
                    </span>
                    <p class="text-sm text-muted-foreground">
                        You have no active products yet.
                    </p>
                    <Link
                        href="/products"
                        class="rounded-md bg-[#5cb85c] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4cae4c]"
                    >
                        Browse Products
                    </Link>
                </div>
                <template v-else>
                    <div class="flex-1 divide-y">
                        <Link
                            v-for="service in visibleProducts"
                            :key="service.id"
                            :href="`/client-area/product/${service.id}`"
                            class="flex items-center gap-3 px-6 py-4 transition hover:bg-muted/40"
                        >
                            <img
                                v-if="service.product?.featured_image"
                                :src="service.product.featured_image"
                                alt=""
                                class="size-11 shrink-0 rounded-lg border object-cover"
                            />
                            <span
                                v-else
                                class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-muted/60 text-muted-foreground"
                            >
                                <Package class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold">
                                    {{ service.product?.name }}
                                </p>
                                <p
                                    class="mt-0.5 flex flex-wrap justify-between gap-x-3 text-xs text-muted-foreground"
                                >
                                    <span>
                                        Expires on:
                                        {{
                                            service.expires_at
                                                ? formatDate(service.expires_at)
                                                : 'Never'
                                        }}
                                    </span>
                                    <span>
                                        License:
                                        {{ cycleLabel(service.billing_cycle) }}
                                    </span>
                                </p>
                            </div>
                        </Link>
                    </div>
                    <div
                        class="flex items-center justify-between border-t px-6 py-3"
                    >
                        <button
                            type="button"
                            class="text-muted-foreground transition hover:text-foreground disabled:opacity-30"
                            :disabled="productPage === 0"
                            aria-label="Previous products"
                            @click="productPage--"
                        >
                            <ArrowLeft class="size-4" />
                        </button>
                        <p class="text-sm font-semibold">
                            {{ shownCount
                            }}<span class="text-muted-foreground"
                                >/{{ props.activeProducts.length }}</span
                            >
                        </p>
                        <button
                            type="button"
                            class="text-muted-foreground transition hover:text-foreground disabled:opacity-30"
                            :disabled="productPage >= productPages - 1"
                            aria-label="Next products"
                            @click="productPage++"
                        >
                            <ArrowRight class="size-4" />
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Due invoices -->
        <div class="mt-8 overflow-hidden rounded-xl bg-card shadow-lg">
            <div
                class="flex items-center justify-between gap-3 border-b px-6 py-4"
            >
                <h2 class="font-bold tracking-tight">Due Invoices</h2>
                <Link
                    href="/client-area/invoices"
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-muted-foreground transition hover:text-[#4fb250]"
                >
                    <ReceiptText class="size-3.5" /> Pay All Invoices
                </Link>
            </div>
            <div
                v-if="props.dueInvoices.length === 0"
                class="p-10 text-center text-sm text-muted-foreground"
            >
                No due invoices — you're all paid up.
            </div>
            <template v-else>
                <div class="divide-y">
                    <Link
                        v-for="invoice in props.dueInvoices"
                        :key="invoice.id"
                        :href="`/client-area/invoice/${invoice.id}`"
                        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-6 py-4 transition hover:bg-muted/40"
                    >
                        <div>
                            <p class="font-mono text-sm font-bold">
                                #{{ invoice.invoice_number }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Due Date: {{ formatDate(invoice.due_at) }}
                            </p>
                        </div>
                        <p class="text-lg font-bold text-red-500">
                            {{ moneyUsd(invoice.currency, invoice.total) }}
                        </p>
                    </Link>
                </div>
                <div class="border-t px-6 py-3 text-center">
                    <p class="text-sm font-semibold">
                        {{ props.dueInvoices.length
                        }}<span class="text-muted-foreground"
                            >/{{ props.dueInvoices.length }}</span
                        >
                    </p>
                </div>
            </template>
        </div>

        <!-- Order history -->
        <div class="mt-10 flex items-center gap-2.5">
            <ReceiptText class="size-5 text-[#4fb250]" />
            <h2 class="text-xl font-bold tracking-tight sm:text-2xl">
                Order history
            </h2>
        </div>

        <div
            v-if="props.orders.length"
            class="mt-5 overflow-x-auto rounded-xl bg-card shadow-lg"
        >
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr
                        class="border-b text-xs font-bold tracking-wide text-muted-foreground uppercase"
                    >
                        <th class="px-5 py-3.5">Order</th>
                        <th class="px-5 py-3.5">Product</th>
                        <th class="px-5 py-3.5">Billing</th>
                        <th class="px-5 py-3.5">Amount</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="order in props.orders"
                        :key="order.order_number"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-5 py-4 font-mono text-xs font-semibold">
                            {{ order.order_number }}
                        </td>
                        <td class="px-5 py-4 font-medium">
                            <Link
                                :href="order.product.url"
                                class="hover:text-[#4fb250]"
                            >
                                {{ order.product.name }}
                            </Link>
                        </td>
                        <td class="px-5 py-4 text-muted-foreground">
                            {{ label(order.billing_cycle) }}
                        </td>
                        <td class="px-5 py-4 font-semibold">
                            {{ money(order.currency, order.amount) }}
                        </td>
                        <td class="px-5 py-4">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-bold"
                                :class="orderStatusClass(order.status)"
                            >
                                {{ label(order.status) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-muted-foreground">
                            {{ formatDate(order.created_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div
            v-else
            class="mt-5 rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground"
        >
            No orders yet.
        </div>
    </section>
</template>
