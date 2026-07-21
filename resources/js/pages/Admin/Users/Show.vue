<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    CreditCard,
    Download,
    FileText,
    Gift,
    KeyRound,
    LogIn,
    NotebookPen,
    ReceiptText,
    ShoppingCart,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface PriceOption {
    id: number;
    name?: string | null;
    billing_cycle: string;
    currency: string;
    price: string | number;
    sale_price?: string | number | null;
    enabled: boolean;
}

interface ProductOption {
    id: number;
    name: string;
    price: string | number;
    prices: PriceOption[];
}

const props = defineProps<{
    user: Record<string, any>;
    activeTab: string;
    invoices: Record<string, any>[];
    billing: Record<string, number>;
    orders: Record<string, any>[];
    licenses: Record<string, any>[];
    products: ProductOption[];
    paymentMethods: Record<string, any>[];
}>();

const tabs = [
    { value: 'summary', label: 'Summary' },
    { value: 'profile', label: 'Profile' },
    { value: 'services', label: 'Products/Services' },
    { value: 'invoices', label: 'Invoices' },
    { value: 'orders', label: 'Orders' },
];

const activeTab = computed(() => props.activeTab);

const tabHref = (tab: string) =>
    tab === 'summary'
        ? `/admin/users/${props.user.id}`
        : `/admin/users/${props.user.id}/${tab}`;

const orderDialogOpen = ref(false);
const assignDialogOpen = ref(false);

const orderForm = useForm({
    product_id: null as number | null,
    product_price_id: null as number | null,
    mark_paid: true,
    complimentary: false,
});

const assignForm = useForm({
    product_id: null as number | null,
    product_price_id: null as number | null,
    mark_paid: true,
    complimentary: true,
});

const profileForm = useForm({
    name: props.user.name,
    company_name: props.user.company_name ?? '',
    email: props.user.email,
    phone: props.user.phone ?? '',
    address_1: props.user.address_1 ?? '',
    address_2: props.user.address_2 ?? '',
    city: props.user.city ?? '',
    state: props.user.state ?? '',
    postcode: props.user.postcode ?? '',
    country: props.user.country ?? '',
    password: '',
    verified: Boolean(props.user.email_verified_at),
});

const notesForm = useForm({
    admin_notes: props.user.admin_notes ?? '',
});

const orderProduct = computed(() =>
    props.products.find((product) => product.id === orderForm.product_id),
);
const assignProduct = computed(() =>
    props.products.find((product) => product.id === assignForm.product_id),
);

const submitOrder = () =>
    orderForm.post(`/admin/users/${props.user.id}/orders`, {
        onSuccess: () => {
            orderDialogOpen.value = false;
            orderForm.reset();
        },
    });

const submitAssign = () =>
    assignForm.post(`/admin/users/${props.user.id}/orders`, {
        onSuccess: () => {
            assignDialogOpen.value = false;
            assignForm.reset();
        },
    });

const submitProfile = () =>
    profileForm.patch(`/admin/users/${props.user.id}`, {
        preserveScroll: true,
        onSuccess: () => profileForm.reset('password'),
    });

const submitNotes = () =>
    notesForm.patch(`/admin/users/${props.user.id}/notes`, {
        preserveScroll: true,
    });

const siteCurrency = 'USD';

const money = (currency: string, amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const priceLabel = (price: PriceOption) =>
    `${price.name || label(price.billing_cycle)} — ${money(price.currency, price.sale_price ?? price.price)}`;

const statusClass = (status: string) =>
    ({
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        pending:
            'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        issued: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        partially_refunded:
            'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        suspended:
            'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        expired: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        terminated:
            'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        void: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
    })[status] ?? 'bg-muted text-muted-foreground';

const activeLicenses = computed(
    () =>
        props.licenses.filter((license) => license.status === 'active').length,
);
</script>

<template>
    <Head :title="`Client: ${user.name}`" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Link
                    href="/admin/users"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-primary"
                >
                    <ArrowLeft class="size-3.5" /> All users
                </Link>
                <h1
                    class="mt-2 flex items-center gap-2 text-3xl font-semibold tracking-tight"
                >
                    {{ user.name }}
                    <BadgeCheck
                        v-if="user.email_verified_at"
                        class="size-6 text-emerald-600"
                    />
                </h1>
                <p class="mt-1 text-muted-foreground">
                    {{ user.email }} ·
                    {{
                        user.social_provider
                            ? `${label(user.social_provider)} sign in`
                            : 'Email sign in'
                    }}
                    · Client since {{ formatDate(user.created_at) }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <Link
                    :href="`/admin/users/${user.id}/impersonate`"
                    method="post"
                    as="button"
                    class="inline-flex h-9 items-center gap-2 rounded-md border px-4 text-sm font-medium shadow-xs transition hover:bg-muted"
                >
                    <LogIn class="size-4" /> Login as client
                </Link>

                <Dialog v-model:open="assignDialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline">
                            <Gift class="size-4" /> Assign product
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Assign a product</DialogTitle>
                            <DialogDescription>
                                Creates a complimentary zero-amount order and
                                generates a license immediately — nothing is
                                charged.
                            </DialogDescription>
                        </DialogHeader>
                        <form class="space-y-5" @submit.prevent="submitAssign">
                            <div class="space-y-2">
                                <Label>Product</Label>
                                <select
                                    v-model="assignForm.product_id"
                                    required
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                    @change="assignForm.product_price_id = null"
                                >
                                    <option :value="null" disabled>
                                        Choose a product
                                    </option>
                                    <option
                                        v-for="product in products"
                                        :key="product.id"
                                        :value="product.id"
                                    >
                                        {{ product.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="assignForm.errors.product_id"
                                />
                            </div>
                            <div
                                v-if="assignProduct?.prices.length"
                                class="space-y-2"
                            >
                                <Label>Plan (sets license duration)</Label>
                                <select
                                    v-model="assignForm.product_price_id"
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                >
                                    <option :value="null">
                                        One-time (lifetime license)
                                    </option>
                                    <option
                                        v-for="price in assignProduct.prices"
                                        :key="price.id"
                                        :value="price.id"
                                    >
                                        {{ priceLabel(price) }}
                                    </option>
                                </select>
                            </div>
                            <DialogFooter>
                                <Button
                                    type="submit"
                                    :disabled="assignForm.processing"
                                >
                                    <Gift class="size-4" />
                                    {{
                                        assignForm.processing
                                            ? 'Assigning…'
                                            : 'Assign product'
                                    }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <Dialog v-model:open="orderDialogOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <ShoppingCart class="size-4" /> Add order
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Add an order</DialogTitle>
                            <DialogDescription>
                                Create an order on this customer's behalf. Paid
                                orders generate a license and invoice
                                automatically.
                            </DialogDescription>
                        </DialogHeader>
                        <form class="space-y-5" @submit.prevent="submitOrder">
                            <div class="space-y-2">
                                <Label>Product</Label>
                                <select
                                    v-model="orderForm.product_id"
                                    required
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                    @change="orderForm.product_price_id = null"
                                >
                                    <option :value="null" disabled>
                                        Choose a product
                                    </option>
                                    <option
                                        v-for="product in products"
                                        :key="product.id"
                                        :value="product.id"
                                    >
                                        {{ product.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="orderForm.errors.product_id"
                                />
                            </div>
                            <div
                                v-if="orderProduct?.prices.length"
                                class="space-y-2"
                            >
                                <Label>Plan</Label>
                                <select
                                    v-model="orderForm.product_price_id"
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                >
                                    <option :value="null">
                                        Base price —
                                        {{
                                            orderProduct
                                                ? money(
                                                      'USD',
                                                      orderProduct.price,
                                                  )
                                                : ''
                                        }}
                                    </option>
                                    <option
                                        v-for="price in orderProduct.prices"
                                        :key="price.id"
                                        :value="price.id"
                                    >
                                        {{ priceLabel(price) }}
                                    </option>
                                </select>
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="orderForm.mark_paid"
                                    type="checkbox"
                                    class="size-4 rounded"
                                />
                                Mark as paid (creates license + invoice now)
                            </label>
                            <DialogFooter>
                                <Button
                                    type="submit"
                                    :disabled="orderForm.processing"
                                >
                                    <ShoppingCart class="size-4" />
                                    {{
                                        orderForm.processing
                                            ? 'Creating…'
                                            : 'Create order'
                                    }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        <div class="border-b">
            <nav class="-mb-px flex flex-wrap gap-1" role="tablist">
                <Link
                    v-for="tab in tabs"
                    :key="tab.value"
                    :href="tabHref(tab.value)"
                    preserve-scroll
                    role="tab"
                    :aria-selected="activeTab === tab.value"
                    class="border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors"
                    :class="
                        activeTab === tab.value
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.value === 'invoices'"
                        class="ml-1 rounded-full bg-muted px-1.5 text-xs"
                        >{{ props.invoices.length }}</span
                    >
                    <span
                        v-else-if="tab.value === 'services'"
                        class="ml-1 rounded-full bg-muted px-1.5 text-xs"
                        >{{ props.licenses.length }}</span
                    >
                    <span
                        v-else-if="tab.value === 'orders'"
                        class="ml-1 rounded-full bg-muted px-1.5 text-xs"
                        >{{ props.orders.length }}</span
                    >
                </Link>
            </nav>
        </div>

        <!-- ============ SUMMARY ============ -->
        <div v-show="activeTab === 'summary'" class="grid gap-6 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <UserRound class="size-5" />
                        <CardTitle>Client information</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Name</dt>
                            <dd class="font-semibold">{{ user.name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Email</dt>
                            <dd class="font-semibold">{{ user.email }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">
                                Email verified
                            </dt>
                            <dd class="font-semibold">
                                {{ user.email_verified_at ? 'Yes' : 'No' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Sign in</dt>
                            <dd class="font-semibold">
                                {{
                                    user.social_provider
                                        ? label(user.social_provider)
                                        : 'Email + password'
                                }}
                            </dd>
                        </div>
                        <div
                            v-if="user.company_name"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Company</dt>
                            <dd class="font-semibold">
                                {{ user.company_name }}
                            </dd>
                        </div>
                        <div
                            v-if="user.phone"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Phone</dt>
                            <dd class="font-semibold">{{ user.phone }}</dd>
                        </div>
                        <div
                            v-if="user.address_1"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Address</dt>
                            <dd class="text-right font-semibold">
                                {{ user.address_1 }}
                                <template v-if="user.address_2"
                                    ><br />{{ user.address_2 }}</template
                                >
                                <br />
                                {{
                                    [user.city, user.state, user.postcode]
                                        .filter(Boolean)
                                        .join(', ')
                                }}
                                <template v-if="user.country">
                                    {{ user.country }}</template
                                >
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Client since</dt>
                            <dd class="font-semibold">
                                {{ formatDate(user.created_at) }}
                            </dd>
                        </div>
                    </dl>
                    <Button
                        variant="outline"
                        size="sm"
                        class="mt-4"
                        @click="activeTab = 'profile'"
                    >
                        Edit profile
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <ReceiptText class="size-5" />
                        <CardTitle>Invoices / Billing</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Paid</dt>
                            <dd class="font-semibold text-emerald-600">
                                {{ billing.paid_count }} ({{
                                    money(siteCurrency, billing.paid_total)
                                }})
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Unpaid / Due</dt>
                            <dd class="font-semibold text-amber-600">
                                {{ billing.unpaid_count }} ({{
                                    money(siteCurrency, billing.unpaid_total)
                                }})
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Void</dt>
                            <dd class="font-semibold">
                                {{ billing.void_count }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t pt-3">
                            <dt class="text-muted-foreground">Gross revenue</dt>
                            <dd class="text-base font-bold">
                                {{ money(siteCurrency, billing.gross_revenue) }}
                            </dd>
                        </div>
                    </dl>
                    <Button
                        variant="outline"
                        size="sm"
                        class="mt-4"
                        @click="activeTab = 'invoices'"
                    >
                        View invoices
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <KeyRound class="size-5" />
                        <CardTitle>Products / Services</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Licenses</dt>
                            <dd class="font-semibold">
                                {{ activeLicenses }} active /
                                {{ licenses.length }} total
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Orders</dt>
                            <dd class="font-semibold">{{ orders.length }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="activeTab = 'services'"
                        >
                            View services
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="activeTab = 'orders'"
                        >
                            View orders
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <CreditCard class="size-5" />
                        <CardTitle>Pay methods</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="paymentMethods.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No pay methods stored.
                    </p>
                    <div v-else class="space-y-3">
                        <div
                            v-for="method in paymentMethods"
                            :key="method.id"
                            class="rounded-lg border p-3 text-sm"
                        >
                            <p class="font-semibold capitalize">
                                {{ method.card_brand || method.type }} ••••
                                {{ method.card_last_four }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Expires
                                {{
                                    String(method.card_expiry_month).padStart(
                                        2,
                                        '0',
                                    )
                                }}/{{ method.card_expiry_year }} · via
                                {{ method.gateway }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <NotebookPen class="size-5" />
                        <CardTitle>Admin notes</CardTitle>
                    </div>
                    <CardDescription>
                        Internal only — never shown to the customer.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-3" @submit.prevent="submitNotes">
                        <textarea
                            v-model="notesForm.admin_notes"
                            rows="4"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                            placeholder="Notes about this client…"
                        />
                        <InputError :message="notesForm.errors.admin_notes" />
                        <div class="flex items-center gap-3">
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="notesForm.processing"
                            >
                                {{
                                    notesForm.processing
                                        ? 'Saving…'
                                        : 'Save notes'
                                }}
                            </Button>
                            <p
                                v-if="notesForm.wasSuccessful"
                                class="text-xs font-medium text-emerald-600"
                            >
                                Saved.
                            </p>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Products/Services</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div
                        v-if="licenses.length === 0"
                        class="p-8 text-center text-sm text-muted-foreground"
                    >
                        No products or services yet.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    <th class="px-5 py-3.5">ID</th>
                                    <th class="px-5 py-3.5">Product/Service</th>
                                    <th class="px-5 py-3.5">Amount</th>
                                    <th class="px-5 py-3.5">Billing cycle</th>
                                    <th class="px-5 py-3.5">Signup date</th>
                                    <th class="px-5 py-3.5">Next due date</th>
                                    <th class="px-5 py-3.5">Status</th>
                                    <th class="px-5 py-3.5"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="license in licenses"
                                    :key="license.id"
                                    class="border-b last:border-b-0 hover:bg-muted/40"
                                >
                                    <td class="px-5 py-4 text-muted-foreground">
                                        {{ license.id }}
                                    </td>
                                    <td class="px-5 py-4 font-medium">
                                        {{ license.product.name }}
                                        <span
                                            v-if="license.domain"
                                            class="text-muted-foreground"
                                        >
                                            — {{ license.domain }}</span
                                        >
                                    </td>
                                    <td class="px-5 py-4 font-semibold">
                                        {{
                                            money(
                                                license.order.currency ?? 'USD',
                                                Number(
                                                    license.order.amount ?? 0,
                                                ) +
                                                    Number(
                                                        license.order
                                                            .setup_fee ?? 0,
                                                    ),
                                            )
                                        }}
                                    </td>
                                    <td class="px-5 py-4 text-muted-foreground">
                                        {{
                                            label(
                                                license.order.billing_cycle ??
                                                    'one_time',
                                            )
                                        }}
                                    </td>
                                    <td class="px-5 py-4 text-muted-foreground">
                                        {{ formatDate(license.created_at) }}
                                    </td>
                                    <td class="px-5 py-4 text-muted-foreground">
                                        {{
                                            license.expires_at
                                                ? formatDate(license.expires_at)
                                                : 'Never'
                                        }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-bold"
                                            :class="statusClass(license.status)"
                                        >
                                            {{ label(license.status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                :href="`/admin/licenses/${license.id}`"
                                            >
                                                Manage
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ============ PROFILE ============ -->
        <Card v-show="activeTab === 'profile'" class="max-w-2xl">
            <CardHeader>
                <CardTitle>Profile</CardTitle>
                <CardDescription>
                    Update the client's account details.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-5" @submit.prevent="submitProfile">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="profile-name">Full name</Label>
                            <Input
                                id="profile-name"
                                v-model="profileForm.name"
                                required
                            />
                            <InputError :message="profileForm.errors.name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="profile-company">Company name</Label>
                            <Input
                                id="profile-company"
                                v-model="profileForm.company_name"
                            />
                            <InputError
                                :message="profileForm.errors.company_name"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="profile-email">Email address</Label>
                            <Input
                                id="profile-email"
                                v-model="profileForm.email"
                                type="email"
                                required
                            />
                            <InputError :message="profileForm.errors.email" />
                        </div>
                        <div class="space-y-2">
                            <Label for="profile-phone">Phone number</Label>
                            <Input
                                id="profile-phone"
                                v-model="profileForm.phone"
                                placeholder="+8801000000000"
                            />
                            <InputError :message="profileForm.errors.phone" />
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="profile-address1">Address 1</Label>
                            <Input
                                id="profile-address1"
                                v-model="profileForm.address_1"
                            />
                            <InputError
                                :message="profileForm.errors.address_1"
                            />
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="profile-address2">Address 2</Label>
                            <Input
                                id="profile-address2"
                                v-model="profileForm.address_2"
                            />
                            <InputError
                                :message="profileForm.errors.address_2"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="profile-city">City</Label>
                            <Input
                                id="profile-city"
                                v-model="profileForm.city"
                            />
                            <InputError :message="profileForm.errors.city" />
                        </div>
                        <div class="space-y-2">
                            <Label for="profile-state">State / Region</Label>
                            <Input
                                id="profile-state"
                                v-model="profileForm.state"
                            />
                            <InputError :message="profileForm.errors.state" />
                        </div>
                        <div class="space-y-2">
                            <Label for="profile-postcode">Postcode</Label>
                            <Input
                                id="profile-postcode"
                                v-model="profileForm.postcode"
                            />
                            <InputError
                                :message="profileForm.errors.postcode"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="profile-country"
                                >Country (ISO code)</Label
                            >
                            <Input
                                id="profile-country"
                                v-model="profileForm.country"
                                maxlength="2"
                                class="uppercase"
                                placeholder="BD"
                            />
                            <InputError :message="profileForm.errors.country" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="profile-password">New password</Label>
                        <Input
                            id="profile-password"
                            v-model="profileForm.password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Leave blank to keep the current password"
                        />
                        <InputError :message="profileForm.errors.password" />
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="profileForm.verified"
                            type="checkbox"
                            class="size-4 rounded"
                        />
                        Email verified
                    </label>
                    <InputError :message="profileForm.errors.verified" />
                    <Button type="submit" :disabled="profileForm.processing">
                        {{
                            profileForm.processing ? 'Saving…' : 'Save profile'
                        }}
                    </Button>
                </form>
            </CardContent>
        </Card>

        <!-- ============ PRODUCTS / SERVICES ============ -->
        <Card v-show="activeTab === 'services'">
            <CardHeader><CardTitle>Licenses</CardTitle></CardHeader>
            <CardContent class="p-0">
                <div
                    v-if="licenses.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No licenses yet.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3.5">License key</th>
                                <th class="px-5 py-3.5">Product</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Expires</th>
                                <th class="px-5 py-3.5">Website</th>
                                <th class="px-5 py-3.5">IP address</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="license in licenses"
                                :key="license.id"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-5 py-4">
                                    <p class="font-mono text-xs font-semibold">
                                        {{ license.license_key }}
                                    </p>
                                    <p
                                        class="mt-1 font-mono text-[11px] text-muted-foreground"
                                    >
                                        {{ license.order.order_number }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 font-medium">
                                    {{ license.product.name }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="statusClass(license.status)"
                                    >
                                        {{ label(license.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{
                                        license.expires_at
                                            ? formatDate(license.expires_at)
                                            : 'Lifetime'
                                    }}
                                </td>
                                <td class="px-5 py-4">
                                    <template v-if="license.domain">
                                        <p class="font-medium">
                                            {{ license.domain }}
                                        </p>
                                        <p
                                            v-if="license.path"
                                            class="mt-0.5 font-mono text-[11px] text-muted-foreground"
                                        >
                                            {{ license.path }}
                                        </p>
                                    </template>
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                        >Not activated</span
                                    >
                                </td>
                                <td
                                    class="px-5 py-4 font-mono text-xs text-muted-foreground"
                                >
                                    {{ license.ip_address ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <Button
                                        as-child
                                        size="sm"
                                        variant="outline"
                                    >
                                        <Link
                                            :href="`/admin/licenses/${license.id}`"
                                        >
                                            <KeyRound class="size-3.5" />
                                            Manage
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- ============ INVOICES ============ -->
        <Card v-show="activeTab === 'invoices'">
            <CardHeader><CardTitle>Invoices</CardTitle></CardHeader>
            <CardContent class="p-0">
                <div
                    v-if="props.invoices.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No invoices yet. Create one from an order in the Orders tab.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3.5">Invoice #</th>
                                <th class="px-5 py-3.5">Invoice date</th>
                                <th class="px-5 py-3.5">Due date</th>
                                <th class="px-5 py-3.5">Date paid</th>
                                <th class="px-5 py-3.5">Total</th>
                                <th class="px-5 py-3.5">Payment method</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="invoice in props.invoices"
                                :key="invoice.id"
                                class="border-b last:border-b-0 hover:bg-muted/40"
                            >
                                <td class="px-5 py-4">
                                    <Link
                                        :href="`/admin/invoices/${invoice.id}`"
                                        class="font-mono text-xs font-semibold text-primary hover:underline"
                                    >
                                        {{ invoice.invoice_number }}
                                    </Link>
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ formatDate(invoice.issued_at) }}
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{
                                        invoice.due_at
                                            ? formatDate(invoice.due_at)
                                            : '—'
                                    }}
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{
                                        invoice.order.paid_at
                                            ? formatDate(invoice.order.paid_at)
                                            : '—'
                                    }}
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    {{
                                        money(
                                            invoice.order.currency,
                                            Number(invoice.order.amount) +
                                                Number(invoice.order.setup_fee),
                                        )
                                    }}
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{
                                        invoice.order.payment_method
                                            ? label(
                                                  invoice.order.payment_method,
                                              )
                                            : '—'
                                    }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="statusClass(invoice.status)"
                                    >
                                        {{ label(invoice.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-1.5">
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                :href="`/admin/invoices/${invoice.id}`"
                                            >
                                                <FileText class="size-3.5" />
                                                View
                                            </Link>
                                        </Button>
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <a
                                                :href="`/admin/invoices/${invoice.id}/download`"
                                            >
                                                <Download class="size-3.5" />
                                                PDF
                                            </a>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- ============ ORDERS ============ -->
        <Card v-show="activeTab === 'orders'">
            <CardHeader><CardTitle>Orders</CardTitle></CardHeader>
            <CardContent class="p-0">
                <div
                    v-if="orders.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No orders yet.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3.5">Order</th>
                                <th class="px-5 py-3.5">Product</th>
                                <th class="px-5 py-3.5">Amount</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Method</th>
                                <th class="px-5 py-3.5">Date</th>
                                <th class="px-5 py-3.5">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="order in orders"
                                :key="order.id"
                                class="border-b last:border-b-0"
                            >
                                <td
                                    class="px-5 py-4 font-mono text-xs font-semibold"
                                >
                                    {{ order.order_number }}
                                </td>
                                <td class="px-5 py-4 font-medium">
                                    {{ order.product.name }}
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    {{ money(order.currency, order.amount) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="statusClass(order.status)"
                                    >
                                        {{ label(order.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ label(order.payment_method ?? '—') }}
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ formatDate(order.created_at) }}
                                </td>
                                <td class="px-5 py-4">
                                    <Button
                                        v-if="order.invoice"
                                        as-child
                                        size="sm"
                                        variant="outline"
                                    >
                                        <Link
                                            :href="`/admin/invoices/${order.invoice.id}`"
                                        >
                                            <FileText class="size-4" />
                                            {{ order.invoice.invoice_number }}
                                        </Link>
                                    </Button>
                                    <Link
                                        v-else
                                        :href="`/admin/orders/${order.id}/invoice`"
                                        method="post"
                                        as="button"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                                    >
                                        <FileText class="size-3.5" /> Create
                                        invoice
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
