<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowRight, Package, ShoppingCart, Tag, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import OrderTotals from '@/modules/client/components/OrderTotals.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface CartItem {
    id: number;
    billing_cycle: string;
    name?: string | null;
    description?: string | null;
    currency: string;
    price: string | number;
    sale_price?: string | number | null;
    setup_fee?: string | number | null;
    amount: string | number;
    product: {
        name: string;
        slug: string;
        url: string;
        featured_image?: string | null;
    };
}

interface CartSummary {
    items: CartItem[];
    currency?: string | null;
    subtotal: string;
    setup_fee: string;
    discount_amount: string;
    tax_amount: string;
    total: string;
    promotion?: { code: string; name: string } | null;
    promotion_error?: string | null;
    tax?: { name: string; rate: string | number } | null;
    tax_pending: boolean;
}

const props = defineProps<{ cart: CartSummary }>();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const promotionForm = useForm({ code: '' });
const removing = ref<number | null>(null);
const cartError = ref('');

const money = (amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.cart.currency || 'USD',
        maximumFractionDigits: 2,
    }).format(Number(amount));

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const removeItem = (item: CartItem) => {
    if (removing.value !== null) {
        return;
    }

    cartError.value = '';
    router.delete(`/cart/items/${item.id}`, {
        preserveScroll: true,
        onStart: () => {
            removing.value = item.id;
        },
        onError: (errors) => {
            cartError.value = Object.values(errors).join(' ');
        },
        onFinish: () => {
            removing.value = null;
        },
    });
};

const applyPromotion = () =>
    promotionForm.post('/cart/promotion', {
        preserveScroll: true,
        onSuccess: () => promotionForm.reset(),
    });

const removePromotion = () =>
    router.delete('/cart/promotion', { preserveScroll: true });
</script>

<template>
    <SeoHead
        title="Your cart"
        description="Review your selected ASR Tech products and plans before checkout."
        type="website"
        :seo="{ robots: 'noindex,follow' }"
    />
    <div class="min-h-[70vh] bg-[var(--client-canvas)] pb-16">
        <header class="border-b border-border bg-card">
            <div class="site-container py-12 sm:py-16">
                <p class="section-kicker">Your selections</p>
                <div
                    class="mt-4 flex flex-wrap items-end justify-between gap-6"
                >
                    <div>
                        <h1 class="display-title">Your cart.</h1>
                        <p class="body-copy mt-4">
                            Review your products and plans before the next step.
                        </p>
                    </div>
                    <ol
                        aria-label="Checkout progress"
                        class="flex items-center gap-4 text-sm"
                    >
                        <li
                            class="flex items-center gap-2 font-semibold text-primary"
                            aria-current="step"
                        >
                            <span
                                class="grid size-7 place-items-center rounded-full bg-accent"
                                >1</span
                            >
                            Cart
                        </li>
                        <li
                            aria-hidden="true"
                            class="h-px w-8 bg-[#cbdcd5]"
                        ></li>
                        <li
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <span
                                class="grid size-7 place-items-center rounded-full border border-border"
                                >2</span
                            >
                            Checkout
                        </li>
                    </ol>
                </div>
            </div>
        </header>
        <div class="site-container py-8 sm:py-12">
            <p
                v-if="cartError"
                role="alert"
                class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
            >
                {{ cartError }}
            </p>
            <div
                v-if="cart.items.length"
                class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]"
            >
                <section aria-label="Cart items">
                    <h2
                        class="mb-5 text-sm font-semibold text-muted-foreground"
                    >
                        {{ cart.items.length }}
                        {{ cart.items.length === 1 ? 'item' : 'items' }} in your
                        cart
                    </h2>
                    <div class="space-y-4">
                        <article
                            v-for="item in cart.items"
                            :key="item.id"
                            class="surface-card grid gap-4 p-5 sm:grid-cols-[80px_minmax(0,1fr)_auto] sm:items-center sm:p-6"
                        >
                            <Link
                                :href="item.product.url"
                                :aria-label="`View ${item.product.name}`"
                                class="flex size-20 items-center justify-center overflow-hidden rounded-xl border border-border bg-muted p-2"
                                ><img
                                    v-if="item.product.featured_image"
                                    :src="item.product.featured_image"
                                    :alt="item.product.name"
                                    loading="lazy"
                                    class="max-h-full max-w-full object-contain" /><Package
                                    v-else
                                    class="size-8 stroke-[1.25] text-primary"
                            /></Link>
                            <div class="min-w-0">
                                <h3 class="text-lg font-semibold">
                                    <Link
                                        :href="item.product.url"
                                        class="hover:text-primary"
                                        >{{ item.product.name }}</Link
                                    >
                                </h3>
                                <p class="mt-2 text-sm text-muted-foreground">
                                    {{ item.name || label(item.billing_cycle) }}
                                </p>
                                <p
                                    v-if="item.description"
                                    class="mt-2 text-sm leading-6 text-muted-foreground"
                                >
                                    {{ item.description }}
                                </p>
                                <p
                                    v-if="Number(item.setup_fee) > 0"
                                    class="mt-2 text-xs text-muted-foreground"
                                >
                                    Setup fee: {{ money(item.setup_fee || 0) }}
                                </p>
                            </div>
                            <div
                                class="flex items-center justify-between gap-5 sm:block sm:text-right"
                            >
                                <p class="text-xl font-semibold">
                                    {{ money(item.amount) }}
                                </p>
                                <button
                                    type="button"
                                    :disabled="removing !== null"
                                    class="inline-flex min-h-11 items-center gap-1.5 text-sm text-muted-foreground hover:text-red-700 disabled:opacity-50"
                                    :aria-label="`Remove ${item.product.name} from cart`"
                                    @click="removeItem(item)"
                                >
                                    <Trash2 class="size-4" />{{
                                        removing === item.id
                                            ? 'Removing…'
                                            : 'Remove'
                                    }}
                                </button>
                            </div>
                        </article>
                    </div>
                    <div
                        class="mt-6 flex flex-wrap items-center justify-between gap-3"
                    >
                        <Link href="/products" class="button-secondary"
                            >Continue shopping</Link
                        ><Link
                            href="/cart"
                            method="delete"
                            as="button"
                            class="min-h-11 px-3 text-sm text-muted-foreground underline underline-offset-4 hover:text-red-700"
                            >Clear cart</Link
                        >
                    </div>
                </section>
                <aside
                    class="surface-card self-start p-6 sm:p-7 lg:sticky lg:top-28"
                >
                    <h2 class="text-xl font-semibold">Order summary</h2>
                    <form
                        v-if="!cart.promotion"
                        class="mt-6"
                        @submit.prevent="applyPromotion"
                    >
                        <label
                            for="promotion-code"
                            class="mb-2 block text-sm font-medium"
                            >Promotion code</label
                        >
                        <div class="flex gap-2">
                            <input
                                id="promotion-code"
                                v-model="promotionForm.code"
                                type="text"
                                required
                                autocomplete="off"
                                placeholder="Enter code"
                                class="h-11 min-w-0 flex-1 rounded-lg border border-border px-3 text-sm uppercase"
                                :aria-invalid="
                                    Boolean(
                                        promotionForm.errors.code ||
                                        cart.promotion_error,
                                    )
                                "
                                :aria-describedby="
                                    promotionForm.errors.code ||
                                    cart.promotion_error
                                        ? 'promotion-error'
                                        : undefined
                                "
                            /><button
                                type="submit"
                                :disabled="promotionForm.processing"
                                class="button-secondary px-4"
                            >
                                {{
                                    promotionForm.processing
                                        ? 'Applying…'
                                        : 'Apply'
                                }}
                            </button>
                        </div>
                    </form>
                    <p
                        v-if="promotionForm.errors.code || cart.promotion_error"
                        id="promotion-error"
                        role="alert"
                        class="mt-2 text-sm text-red-700"
                    >
                        {{ promotionForm.errors.code || cart.promotion_error }}
                    </p>
                    <div
                        v-if="cart.promotion"
                        class="mt-5 flex items-center justify-between gap-2 rounded-lg bg-accent py-1 pr-1 pl-3 text-sm text-primary"
                    >
                        <span class="flex items-center gap-2 font-medium"
                            ><Tag class="size-4" />{{
                                cart.promotion.code
                            }}</span
                        ><button
                            type="button"
                            class="grid size-10 place-items-center rounded-lg hover:bg-muted"
                            aria-label="Remove promotion"
                            @click="removePromotion"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                    <OrderTotals :summary="cart" class="mt-7" />
                    <Link href="/checkout" class="button-primary mt-7 w-full"
                        >{{
                            user
                                ? 'Continue to checkout'
                                : 'Sign in to checkout'
                        }}<ArrowRight class="size-4"
                    /></Link>
                    <p
                        class="mt-4 text-center text-xs leading-5 text-muted-foreground"
                    >
                        Choose your payment method at the next step.
                    </p>
                </aside>
            </div>
            <section
                v-else
                class="surface-card px-6 py-16 text-center sm:py-24"
            >
                <span
                    class="mx-auto grid size-20 place-items-center rounded-2xl border border-border bg-accent"
                    ><ShoppingCart class="size-8 stroke-[1.25] text-primary"
                /></span>
                <h2 class="mt-6 text-3xl font-semibold tracking-tight">
                    Your next project starts here.
                </h2>
                <p
                    class="mx-auto mt-4 max-w-md text-sm leading-7 text-muted-foreground"
                >
                    Your cart is empty. Explore the catalog and choose an
                    available plan to get started.
                </p>
                <Link href="/products" class="button-primary mt-7"
                    >Explore Products <ArrowRight class="size-4"
                /></Link>
            </section>
        </div>
    </div>
</template>
