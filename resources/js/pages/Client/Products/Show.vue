<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    ChevronLeft,
    ChevronRight,
    ExternalLink,
    FileText,
    ShoppingCart,
    Star,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import ProductGalleryLightbox from '@/modules/client/components/ProductGalleryLightbox.vue';
import ProductVisual from '@/modules/client/components/ProductVisual.vue';
import RelatedProductCard from '@/modules/client/components/RelatedProductCard.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface MediaItem {
    url: string;
    alt_text?: string | null;
}

interface ProductPrice {
    id: number;
    billing_cycle: string;
    name?: string | null;
    description?: string | null;
    currency: string;
    price: string | number;
    sale_price?: string | number | null;
    setup_fee?: string | number | null;
    purchase_url?: string | null;
    features?: string[] | null;
    featured?: boolean;
    enabled: boolean;
}

interface FeatureGroup {
    title: string;
    description?: string | null;
    features: string[];
}

interface Requirement {
    label: string;
    value: string;
}

interface ChangelogRelease {
    version: string;
    released_at?: string | null;
    notes: string[];
}

interface ProductReview {
    id?: number;
    name: string;
    title?: string | null;
    rating: number;
    content: string;
    reviewed_at?: string | null;
    verified_purchase?: boolean;
}

interface ProductAddon {
    name: string;
    description?: string | null;
    currency: string;
    price?: string | number | null;
    sale_price?: string | number | null;
    purchase_url?: string | null;
}

interface RelatedProduct {
    name: string;
    slug: string;
    url: string;
    type: string;
    badge?: string | null;
    short_description?: string | null;
    featured_image?: string | null;
    category: { name: string; slug: string };
    prices: ProductPrice[];
}

interface Product {
    name: string;
    slug: string;
    url: string;
    documentation_path: string;
    type: string;
    badge?: string | null;
    version?: string | null;
    release_date?: string | null;
    compatibility?: string | null;
    php_compatibility?: string | null;
    compatibility_ranges?: {
        platform: 'whmcs' | 'wordpress' | 'php';
        minimum_version: string;
        maximum_version: string;
    }[];
    short_description?: string | null;
    description?: string | null;
    featured_image?: string | null;
    demo_url?: string | null;
    documentation_url?: string | null;
    purchase_url?: string | null;
    trial_url?: string | null;
    documentation_content?: string | null;
    category: { name: string };
    prices: ProductPrice[];
    gallery?: MediaItem[] | null;
    feature_groups?: FeatureGroup[] | null;
    requirements?: Requirement[] | null;
    changelog?: ChangelogRelease[] | null;
    reviews?: ProductReview[] | null;
    addons?: ProductAddon[] | null;
    seo?: Record<string, unknown> | null;
}

interface ReviewState {
    can_review: boolean;
    login_url: string;
    review?: {
        rating: number;
        title?: string | null;
        content: string;
        status: string;
    } | null;
}

const props = defineProps<{
    product: Product;
    relatedProducts?: RelatedProduct[];
    reviewState: ReviewState;
}>();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const activeTab = ref('overview');
const reviewForm = useForm({
    rating: props.reviewState.review?.rating ?? 5,
    title: props.reviewState.review?.title ?? '',
    content: props.reviewState.review?.content ?? '',
});
const submitReview = () =>
    reviewForm.post(`${props.product.url}/reviews`, {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = 'reviews';
        },
    });
const buying = ref(false);
const addingToCart = ref(false);
const purchaseError = ref('');
const sendToCart = (stayOnProduct: boolean) => {
    if (!selectedPrice.value || buying.value || addingToCart.value) {
        return;
    }

    purchaseError.value = '';
    router.post(
        `/cart/${props.product.slug}/prices/${selectedPrice.value.id}`,
        { stay_on_product: stayOnProduct },
        {
            preserveScroll: stayOnProduct,
            onStart: () => {
                if (stayOnProduct) {
                    addingToCart.value = true;
                } else {
                    buying.value = true;
                }
            },
            onError: (errors) => {
                purchaseError.value = Object.values(errors).join(' ');
            },
            onFinish: () => {
                buying.value = false;
                addingToCart.value = false;
            },
        },
    );
};
const enabledPrices = computed(() =>
    (props.product.prices ?? []).filter((price) => price.enabled),
);
const initialPriceIndex = enabledPrices.value.findIndex(
    (price) => price.featured,
);
const selectedPriceIndex = ref(initialPriceIndex >= 0 ? initialPriceIndex : 0);
const selectedPrice = computed(
    () => enabledPrices.value[selectedPriceIndex.value] ?? null,
);
const media = computed<MediaItem[]>(() => {
    const gallery = props.product.gallery ?? [];

    return props.product.featured_image
        ? [
              {
                  url: props.product.featured_image,
                  alt_text: props.product.name,
              },
              ...gallery.filter(
                  (image) => image.url !== props.product.featured_image,
              ),
          ]
        : gallery;
});
const currentImageIndex = ref(0);
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
const openLightbox = (index: number) => {
    if (!media.value.length) {
        return;
    }

    lightboxIndex.value = index;
    lightboxOpen.value = true;
};
const changeImage = (direction: number) => {
    if (media.value.length) {
        currentImageIndex.value =
            (currentImageIndex.value + direction + media.value.length) %
            media.value.length;
    }
};
const touchStartX = ref(0);
const handleTouchStart = (event: TouchEvent) => {
    touchStartX.value = event.changedTouches[0].screenX;
};
const handleTouchEnd = (event: TouchEvent) => {
    const distance = event.changedTouches[0].screenX - touchStartX.value;

    if (Math.abs(distance) > 50) {
        changeImage(distance < 0 ? 1 : -1);
    }
};
const documentationHref = computed(() =>
    props.product.documentation_content
        ? props.product.documentation_path
        : props.product.documentation_url,
);
const documentationIsExternal = computed(
    () =>
        !props.product.documentation_content &&
        Boolean(props.product.documentation_url),
);
const tabs = computed(() =>
    [
        { value: 'overview', label: 'Overview', show: true },
        {
            value: 'features',
            label: 'Features',
            show: Boolean(props.product.feature_groups?.length),
        },
        {
            value: 'screenshots',
            label: 'Screenshots',
            show: media.value.length > 0,
        },
        {
            value: 'changelog',
            label: 'Changelog',
            show: Boolean(props.product.changelog?.length),
        },
        {
            value: 'documentation',
            label: 'Documentation',
            show: Boolean(documentationHref.value),
        },
        { value: 'reviews', label: 'Reviews', show: true },
    ].filter((tab) => tab.show),
);
const publicReviews = computed(() =>
    (props.product.reviews ?? []).filter(
        (review) => review.verified_purchase === true,
    ),
);
const averageRating = computed(() => {
    const reviews = publicReviews.value;

    return reviews.length
        ? (
              reviews.reduce(
                  (total, review) => total + Number(review.rating),
                  0,
              ) / reviews.length
          ).toFixed(1)
        : null;
});
const productInformation = computed(() =>
    [
        { label: 'Category', value: props.product.category.name },
        { label: 'Version', value: props.product.version },
        { label: 'Compatibility', value: props.product.compatibility },
        { label: 'PHP compatibility', value: props.product.php_compatibility },
        {
            label: 'Last updated',
            value: props.product.release_date
                ? formatDate(props.product.release_date)
                : null,
        },
    ].filter((item) => item.value),
);
const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
const money = (currency: string, amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));
const cycleLabel = (cycle: string) =>
    cycle === 'monthly'
        ? 'Billed monthly'
        : cycle === 'yearly'
          ? 'Billed annually'
          : 'One-time payment';
const purchaseUrl = () =>
    selectedPrice.value?.purchase_url ||
    props.product.purchase_url ||
    '/contact';
const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));
</script>

<template>
    <SeoHead
        :title="product.name"
        :description="product.short_description"
        :image="product.featured_image"
        :seo="product.seo"
        type="product"
    />
    <div
        class="bg-[var(--client-canvas)] pb-16 [overflow-wrap:anywhere] text-foreground"
    >
        <header class="border-b border-border bg-card">
            <div class="site-container py-8 sm:py-12">
                <nav
                    aria-label="Breadcrumb"
                    class="mb-8 flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                >
                    <Link href="/products" class="hover:text-primary"
                        >Products</Link
                    ><ChevronRight class="size-3" aria-hidden="true" /><span>{{
                        product.category.name
                    }}</span
                    ><ChevronRight class="size-3" aria-hidden="true" /><span
                        aria-current="page"
                        >{{ product.name }}</span
                    >
                </nav>
                <div class="grid gap-10 lg:grid-cols-[1fr_1.05fr] lg:gap-14">
                    <div class="min-w-0">
                        <div
                            class="relative overflow-hidden rounded-2xl border border-border bg-muted"
                            @touchstart="handleTouchStart"
                            @touchend="handleTouchEnd"
                        >
                            <button
                                v-if="media.length"
                                type="button"
                                class="flex aspect-[16/11] w-full items-center justify-center p-6 sm:p-10"
                                :aria-label="`Enlarge ${product.name} image ${currentImageIndex + 1}`"
                                @click="openLightbox(currentImageIndex)"
                            >
                                <img
                                    :src="media[currentImageIndex].url"
                                    :alt="
                                        media[currentImageIndex].alt_text ||
                                        product.name
                                    "
                                    decoding="async"
                                    class="max-h-96 max-w-full rounded-lg object-contain"
                                />
                            </button>
                            <ProductVisual
                                v-else
                                :name="product.name"
                                :type="product.type"
                            />
                            <span
                                v-if="product.badge"
                                class="absolute top-4 left-4 rounded-full border border-border bg-card px-3 py-1.5 text-xs font-semibold text-primary"
                                >{{ product.badge }}</span
                            >
                            <div
                                v-if="media.length > 1"
                                class="absolute right-3 bottom-3 flex items-center gap-2 rounded-full border border-border bg-card p-1"
                            >
                                <button
                                    type="button"
                                    class="grid size-9 place-items-center rounded-full hover:bg-muted"
                                    aria-label="Previous product image"
                                    @click="changeImage(-1)"
                                >
                                    <ChevronLeft class="size-4" />
                                </button>
                                <span
                                    class="min-w-9 text-center text-xs"
                                    aria-live="polite"
                                    >{{ currentImageIndex + 1 }} /
                                    {{ media.length }}</span
                                >
                                <button
                                    type="button"
                                    class="grid size-9 place-items-center rounded-full hover:bg-muted"
                                    aria-label="Next product image"
                                    @click="changeImage(1)"
                                >
                                    <ChevronRight class="size-4" />
                                </button>
                            </div>
                        </div>
                        <div
                            v-if="media.length > 1"
                            class="mt-3 flex gap-3 overflow-x-auto pb-2"
                            aria-label="Product image thumbnails"
                        >
                            <button
                                v-for="(image, index) in media"
                                :key="image.url"
                                type="button"
                                class="shrink-0 overflow-hidden rounded-lg border-2 bg-card p-1"
                                :class="
                                    index === currentImageIndex
                                        ? 'border-[#087f75]'
                                        : 'border-border'
                                "
                                :aria-label="`Show image ${index + 1}`"
                                :aria-pressed="index === currentImageIndex"
                                @click="currentImageIndex = index"
                            >
                                <img
                                    :src="image.url"
                                    alt=""
                                    loading="lazy"
                                    class="h-14 w-20 object-contain"
                                />
                            </button>
                        </div>
                        <div
                            v-if="product.demo_url || documentationHref"
                            class="mt-5 flex flex-wrap gap-3"
                        >
                            <a
                                v-if="product.demo_url"
                                :href="product.demo_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="button-secondary"
                                >View demo <ExternalLink class="size-4"
                            /></a>
                            <a
                                v-if="documentationHref"
                                :href="documentationHref"
                                :target="
                                    documentationIsExternal
                                        ? '_blank'
                                        : undefined
                                "
                                :rel="
                                    documentationIsExternal
                                        ? 'noopener noreferrer'
                                        : undefined
                                "
                                class="button-secondary"
                                ><FileText class="size-4" /> Documentation</a
                            >
                        </div>
                    </div>
                    <div>
                        <p class="section-kicker">
                            {{ product.category.name }}
                        </p>
                        <h1
                            class="mt-3 text-3xl leading-tight font-semibold tracking-[-0.035em] sm:text-4xl lg:text-5xl"
                        >
                            {{ product.name }}
                        </h1>
                        <p
                            v-if="product.short_description"
                            class="body-copy mt-5"
                        >
                            {{ product.short_description }}
                        </p>
                        <div
                            class="mt-5 flex flex-wrap items-center gap-3 text-xs text-muted-foreground"
                        >
                            <span
                                v-if="product.version"
                                class="rounded-md bg-muted px-2.5 py-1.5"
                                >Version {{ product.version }}</span
                            >
                            <span
                                v-if="product.compatibility"
                                class="rounded-md bg-muted px-2.5 py-1.5"
                                >{{ product.compatibility }}</span
                            >
                            <a
                                v-if="averageRating"
                                href="#product-details"
                                class="inline-flex items-center gap-1.5 text-primary"
                                @click="activeTab = 'reviews'"
                                ><Star class="size-4" /> {{ averageRating }} / 5
                                · {{ publicReviews.length }} reviews</a
                            >
                        </div>
                        <div
                            class="mt-7 rounded-2xl border border-border p-5 sm:p-6"
                        >
                            <fieldset v-if="enabledPrices.length" class="mb-6">
                                <legend class="mb-3 text-sm font-semibold">
                                    Choose your plan
                                </legend>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="(price, index) in enabledPrices"
                                        :key="price.id"
                                        class="relative cursor-pointer rounded-lg border px-4 py-3 text-sm font-medium focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[#087f75]"
                                        :class="
                                            selectedPriceIndex === index
                                                ? 'border-[#087f75] bg-accent text-primary'
                                                : 'border-border text-muted-foreground'
                                        "
                                    >
                                        <input
                                            v-model="selectedPriceIndex"
                                            type="radio"
                                            name="product-plan"
                                            :value="index"
                                            class="sr-only"
                                        />{{
                                            price.name ||
                                            label(price.billing_cycle)
                                        }}
                                    </label>
                                </div>
                            </fieldset>
                            <template v-if="selectedPrice">
                                <div
                                    class="flex flex-wrap items-baseline gap-x-3 gap-y-1"
                                >
                                    <p
                                        class="text-4xl font-semibold tracking-tight"
                                    >
                                        {{
                                            money(
                                                selectedPrice.currency,
                                                selectedPrice.sale_price ??
                                                    selectedPrice.price,
                                            )
                                        }}
                                    </p>
                                    <del
                                        v-if="selectedPrice.sale_price != null"
                                        class="text-lg text-muted-foreground"
                                        >{{
                                            money(
                                                selectedPrice.currency,
                                                selectedPrice.price,
                                            )
                                        }}</del
                                    >
                                </div>
                                <p class="mt-2 text-sm text-muted-foreground">
                                    {{
                                        cycleLabel(selectedPrice.billing_cycle)
                                    }}
                                </p>
                                <p
                                    v-if="Number(selectedPrice.setup_fee) > 0"
                                    class="mt-2 text-sm text-muted-foreground"
                                >
                                    Plus
                                    {{
                                        money(
                                            selectedPrice.currency,
                                            selectedPrice.setup_fee || 0,
                                        )
                                    }}
                                    setup fee
                                </p>
                                <p
                                    v-if="selectedPrice.description"
                                    class="mt-4 text-sm leading-6 text-muted-foreground"
                                >
                                    {{ selectedPrice.description }}
                                </p>
                                <ul
                                    v-if="selectedPrice.features?.length"
                                    class="mt-5 space-y-2 border-t border-border pt-5"
                                >
                                    <li
                                        v-for="feature in selectedPrice.features"
                                        :key="feature"
                                        class="flex gap-2 text-sm leading-6 text-muted-foreground"
                                    >
                                        <Check
                                            class="mt-1 size-4 shrink-0 text-primary"
                                        /><span>{{ feature }}</span>
                                    </li>
                                </ul>
                                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                    <button
                                        type="button"
                                        class="button-primary"
                                        :disabled="buying || addingToCart"
                                        @click="sendToCart(false)"
                                    >
                                        {{ buying ? 'Adding…' : 'Buy now'
                                        }}<ArrowRight class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="button-secondary"
                                        :disabled="buying || addingToCart"
                                        @click="sendToCart(true)"
                                    >
                                        <ShoppingCart class="size-4" />{{
                                            addingToCart
                                                ? 'Adding…'
                                                : 'Add to cart'
                                        }}
                                    </button>
                                </div>
                            </template>
                            <template v-else
                                ><h2 class="text-xl font-semibold">
                                    Let's discuss your requirements.
                                </h2>
                                <p
                                    class="mt-2 text-sm leading-6 text-muted-foreground"
                                >
                                    Contact ASR Tech for availability, scope,
                                    and pricing.
                                </p>
                                <a
                                    :href="purchaseUrl()"
                                    class="button-primary mt-5"
                                    >{{
                                        product.purchase_url
                                            ? 'View purchase options'
                                            : 'Request information'
                                    }}<ArrowRight class="size-4" /></a
                            ></template>
                            <p
                                v-if="purchaseError"
                                role="alert"
                                class="mt-4 text-sm text-red-700"
                            >
                                {{ purchaseError }}
                            </p>
                            <a
                                v-if="product.trial_url"
                                :href="product.trial_url"
                                class="mt-4 inline-flex text-sm font-medium text-primary underline underline-offset-4"
                                >View trial details</a
                            >
                        </div>
                        <p class="mt-4 text-sm leading-6 text-muted-foreground">
                            Need to check compatibility or support terms?
                            <Link
                                href="/contact"
                                class="font-semibold text-primary underline underline-offset-4"
                                >Ask before ordering.</Link
                            >
                        </p>
                    </div>
                </div>
            </div>
        </header>
        <Tabs
            id="product-details"
            v-model="activeTab"
            class="scroll-mt-24 gap-0"
        >
            <div class="border-b border-border bg-card">
                <div class="site-container overflow-x-auto">
                    <TabsList
                        aria-label="Product details"
                        class="h-auto min-w-max justify-start gap-1 rounded-none bg-transparent py-3"
                        ><TabsTrigger
                            v-for="tab in tabs"
                            :key="tab.value"
                            :value="tab.value"
                            class="h-11 rounded-lg px-4 text-sm font-medium text-muted-foreground data-[state=active]:bg-accent data-[state=active]:text-primary data-[state=active]:shadow-none"
                            >{{ tab.label }}</TabsTrigger
                        ></TabsList
                    >
                </div>
            </div>
            <div class="site-container pt-8 sm:pt-12">
                <TabsContent
                    value="overview"
                    class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]"
                >
                    <div class="space-y-8">
                        <article class="surface-card p-6 sm:p-9">
                            <p class="section-kicker">The details</p>
                            <h2
                                class="mt-3 text-2xl font-semibold tracking-tight"
                            >
                                About this product
                            </h2>
                            <p
                                class="mt-5 text-base leading-8 whitespace-pre-line text-muted-foreground"
                            >
                                {{
                                    product.description ||
                                    product.short_description ||
                                    'Contact ASR Tech for more information about this product.'
                                }}
                            </p>
                        </article>
                        <section
                            v-if="product.addons?.length"
                            class="surface-card p-6 sm:p-9"
                        >
                            <h2 class="text-2xl font-semibold">
                                Optional services
                            </h2>
                            <div class="mt-5 divide-y divide-border">
                                <article
                                    v-for="addon in product.addons"
                                    :key="addon.name"
                                    class="flex flex-wrap items-start justify-between gap-4 py-5"
                                >
                                    <div class="max-w-md">
                                        <h3 class="font-semibold">
                                            {{ addon.name }}
                                        </h3>
                                        <p
                                            v-if="addon.description"
                                            class="mt-2 text-sm leading-6 text-muted-foreground"
                                        >
                                            {{ addon.description }}
                                        </p>
                                    </div>
                                    <div>
                                        <p
                                            v-if="addon.price != null"
                                            class="font-semibold"
                                        >
                                            {{
                                                money(
                                                    addon.currency,
                                                    addon.sale_price ??
                                                        addon.price,
                                                )
                                            }}
                                        </p>
                                        <a
                                            :href="
                                                addon.purchase_url || '/contact'
                                            "
                                            class="mt-2 inline-flex text-sm font-medium text-primary underline underline-offset-4"
                                            >{{
                                                addon.purchase_url
                                                    ? 'View service'
                                                    : 'Ask about this service'
                                            }}</a
                                        >
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                    <aside class="surface-card self-start p-6">
                        <h2 class="text-lg font-semibold">
                            Product information
                        </h2>
                        <dl class="mt-5 divide-y divide-border">
                            <div
                                v-for="item in productInformation"
                                :key="item.label"
                                class="py-3"
                            >
                                <dt class="text-xs text-muted-foreground">
                                    {{ item.label }}
                                </dt>
                                <dd class="mt-1 text-sm font-medium">
                                    {{ item.value }}
                                </dd>
                            </div>
                        </dl>
                        <section
                            class="mt-6 border-t border-border pt-5"
                            aria-label="Verified compatibility"
                        >
                            <h3 class="font-semibold">
                                Verified compatibility
                            </h3>
                            <dl
                                v-if="product.compatibility_ranges?.length"
                                class="mt-3 space-y-3"
                            >
                                <div
                                    v-for="(
                                        range, index
                                    ) in product.compatibility_ranges"
                                    :key="index"
                                >
                                    <dt class="text-xs text-muted-foreground">
                                        {{
                                            {
                                                whmcs: 'WHMCS',
                                                wordpress: 'WordPress',
                                                php: 'PHP',
                                            }[range.platform]
                                        }}
                                    </dt>
                                    <dd class="mt-1 text-sm font-medium">
                                        {{ range.minimum_version
                                        }}<template
                                            v-if="
                                                range.minimum_version !==
                                                range.maximum_version
                                            "
                                            >–{{
                                                range.maximum_version
                                            }}
                                            (inclusive)</template
                                        >
                                    </dd>
                                </div>
                            </dl>
                            <p
                                v-else
                                class="mt-3 text-sm leading-6 text-muted-foreground"
                            >
                                Verified version ranges have not been published
                                yet.
                                <Link
                                    href="/contact"
                                    class="font-medium text-primary underline underline-offset-4"
                                    >Ask about your setup</Link
                                >
                                before purchasing.
                            </p>
                        </section>
                        <template v-if="product.requirements?.length"
                            ><h3
                                class="mt-6 border-t border-border pt-5 font-semibold"
                            >
                                Requirements
                            </h3>
                            <dl class="mt-3 space-y-3">
                                <div
                                    v-for="requirement in product.requirements"
                                    :key="requirement.label"
                                >
                                    <dt class="text-xs text-muted-foreground">
                                        {{ requirement.label }}
                                    </dt>
                                    <dd class="mt-1 text-sm leading-6">
                                        {{ requirement.value }}
                                    </dd>
                                </div>
                            </dl></template
                        >
                    </aside>
                </TabsContent>
                <TabsContent value="features" class="surface-card p-6 sm:p-9"
                    ><h2 class="text-2xl font-semibold">What it can do</h2>
                    <div class="mt-7 grid gap-6 md:grid-cols-2">
                        <article
                            v-for="group in product.feature_groups"
                            :key="group.title"
                            class="rounded-xl border border-border p-6"
                        >
                            <h3 class="text-lg font-semibold">
                                {{ group.title }}
                            </h3>
                            <p
                                v-if="group.description"
                                class="mt-2 text-sm leading-6 text-muted-foreground"
                            >
                                {{ group.description }}
                            </p>
                            <ul class="mt-5 space-y-3">
                                <li
                                    v-for="feature in group.features"
                                    :key="feature"
                                    class="flex gap-3 text-sm leading-6 text-muted-foreground"
                                >
                                    <Check
                                        class="mt-1 size-4 shrink-0 text-primary"
                                    />{{ feature }}
                                </li>
                            </ul>
                        </article>
                    </div></TabsContent
                >
                <TabsContent value="screenshots" class="surface-card p-6 sm:p-9"
                    ><h2 class="text-2xl font-semibold">A closer look</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Select an image to view it at full size.
                    </p>
                    <div class="mt-7 grid gap-6 md:grid-cols-2">
                        <button
                            v-for="(image, index) in media"
                            :key="image.url"
                            type="button"
                            class="overflow-hidden rounded-xl border border-border bg-[var(--client-canvas)] text-left"
                            @click="openLightbox(index)"
                        >
                            <img
                                :src="image.url"
                                :alt="
                                    image.alt_text ||
                                    `${product.name} image ${index + 1}`
                                "
                                loading="lazy"
                                class="aspect-[16/10] w-full object-contain p-4"
                            /><span
                                class="block border-t border-border bg-card p-4 text-sm text-muted-foreground"
                                >{{
                                    image.alt_text || `Image ${index + 1}`
                                }}</span
                            >
                        </button>
                    </div></TabsContent
                >
                <TabsContent value="changelog" class="surface-card p-6 sm:p-9"
                    ><h2 class="text-2xl font-semibold">Release notes</h2>
                    <div class="mt-7 divide-y divide-border">
                        <article
                            v-for="release in product.changelog"
                            :key="`${release.version}-${release.released_at}`"
                            class="grid gap-5 py-7 first:pt-0 sm:grid-cols-[180px_1fr]"
                        >
                            <div>
                                <h3 class="font-semibold">
                                    Version {{ release.version }}
                                </h3>
                                <time
                                    v-if="release.released_at"
                                    :datetime="release.released_at"
                                    class="mt-2 block text-sm text-muted-foreground"
                                    >{{ formatDate(release.released_at) }}</time
                                >
                            </div>
                            <ul class="space-y-3">
                                <li
                                    v-for="note in release.notes"
                                    :key="note"
                                    class="flex gap-3 text-sm leading-6 text-muted-foreground"
                                >
                                    <Check
                                        class="mt-1 size-4 shrink-0 text-primary"
                                    />{{ note }}
                                </li>
                            </ul>
                        </article>
                    </div></TabsContent
                >
                <TabsContent
                    value="documentation"
                    class="surface-card p-6 sm:p-9"
                    ><div
                        class="flex flex-wrap items-center justify-between gap-5"
                    >
                        <h2 class="text-2xl font-semibold">Documentation</h2>
                        <a
                            v-if="documentationHref"
                            :href="documentationHref"
                            :target="
                                documentationIsExternal ? '_blank' : undefined
                            "
                            :rel="
                                documentationIsExternal
                                    ? 'noopener noreferrer'
                                    : undefined
                            "
                            class="button-secondary"
                            >Open documentation <ArrowRight class="size-4"
                        /></a>
                    </div>
                    <p
                        v-if="product.documentation_content"
                        class="mt-7 text-sm leading-8 whitespace-pre-line text-muted-foreground"
                    >
                        {{ product.documentation_content }}
                    </p></TabsContent
                >
                <TabsContent value="reviews" class="surface-card p-6 sm:p-9">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <h2 class="text-2xl font-semibold">Customer reviews</h2>
                        <span
                            v-if="averageRating"
                            class="inline-flex items-center gap-2 rounded-full bg-accent px-4 py-2 text-sm font-medium text-primary"
                            ><Star class="size-4" />{{ averageRating }} out of
                            5</span
                        >
                    </div>
                    <form
                        v-if="reviewState.can_review"
                        class="mt-6 space-y-5 rounded-xl border border-border bg-[var(--client-canvas)] p-5 sm:p-6"
                        @submit.prevent="submitReview"
                    >
                        <div>
                            <h3 class="text-lg font-semibold">
                                {{
                                    reviewState.review
                                        ? 'Update your review'
                                        : 'Share your experience'
                                }}
                            </h3>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Your name will appear with your review.
                            </p>
                            <p
                                v-if="reviewState.review?.status === 'pending'"
                                class="mt-2 text-sm text-amber-800"
                            >
                                Your review is awaiting moderation.
                            </p>
                            <p
                                v-else-if="
                                    reviewState.review?.status === 'hidden'
                                "
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                Your review is hidden. Updating it will return
                                it to moderation.
                            </p>
                        </div>
                        <fieldset>
                            <legend class="mb-2 text-sm font-semibold">
                                Rating
                            </legend>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="number in 5"
                                    :key="number"
                                    class="cursor-pointer rounded-lg border px-3 py-2 text-sm focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[#087f75]"
                                    :class="
                                        reviewForm.rating === number
                                            ? 'border-[#087f75] bg-accent text-primary'
                                            : 'border-border bg-card'
                                    "
                                    ><input
                                        v-model="reviewForm.rating"
                                        type="radio"
                                        name="review-rating"
                                        :value="number"
                                        class="sr-only"
                                        :aria-label="`${number} ${number === 1 ? 'star' : 'stars'}`"
                                    />{{ number }}
                                    <span aria-hidden="true">★</span></label
                                >
                            </div>
                            <p
                                v-if="reviewForm.errors.rating"
                                role="alert"
                                class="mt-2 text-sm text-red-700"
                            >
                                {{ reviewForm.errors.rating }}
                            </p>
                        </fieldset>
                        <div>
                            <label
                                for="review-title"
                                class="mb-2 block text-sm font-semibold"
                                >Review title</label
                            ><input
                                id="review-title"
                                v-model="reviewForm.title"
                                maxlength="255"
                                class="h-11 w-full rounded-lg border border-border bg-card px-3 text-sm"
                                :aria-invalid="Boolean(reviewForm.errors.title)"
                                :aria-describedby="
                                    reviewForm.errors.title
                                        ? 'review-title-error'
                                        : undefined
                                "
                            />
                            <p
                                v-if="reviewForm.errors.title"
                                id="review-title-error"
                                role="alert"
                                class="mt-2 text-sm text-red-700"
                            >
                                {{ reviewForm.errors.title }}
                            </p>
                        </div>
                        <div>
                            <label
                                for="review-content"
                                class="mb-2 block text-sm font-semibold"
                                >Your experience</label
                            ><textarea
                                id="review-content"
                                v-model="reviewForm.content"
                                rows="5"
                                maxlength="5000"
                                required
                                class="w-full rounded-lg border border-border bg-card p-3 text-sm"
                                :aria-invalid="
                                    Boolean(reviewForm.errors.content)
                                "
                                :aria-describedby="
                                    reviewForm.errors.content
                                        ? 'review-content-error'
                                        : undefined
                                "
                            />
                            <p
                                v-if="reviewForm.errors.content"
                                id="review-content-error"
                                role="alert"
                                class="mt-2 text-sm text-red-700"
                            >
                                {{ reviewForm.errors.content }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <button
                                type="submit"
                                class="button-primary"
                                :disabled="reviewForm.processing"
                            >
                                {{
                                    reviewForm.processing
                                        ? 'Saving…'
                                        : reviewState.review
                                          ? 'Update review'
                                          : 'Submit review'
                                }}
                            </button>
                            <p
                                v-if="reviewForm.recentlySuccessful"
                                role="status"
                                class="text-sm text-primary"
                            >
                                Your review has been saved.
                            </p>
                        </div>
                    </form>
                    <p
                        v-else-if="!user"
                        class="mt-6 rounded-xl bg-muted p-5 text-sm leading-6 text-muted-foreground"
                    >
                        Purchased this product?
                        <Link
                            :href="reviewState.login_url"
                            class="font-semibold text-primary underline underline-offset-4"
                            >Sign in to write a review.</Link
                        >
                    </p>
                    <p v-else class="mt-6 text-sm text-muted-foreground">
                        Customers who have purchased this product can submit a
                        review.
                    </p>
                    <div
                        v-if="publicReviews.length"
                        class="mt-8 grid gap-6 md:grid-cols-2"
                    >
                        <article
                            v-for="review in publicReviews"
                            :key="
                                review.id ??
                                `${review.name}-${review.reviewed_at}`
                            "
                            class="rounded-xl border border-border p-6"
                        >
                            <p class="text-sm font-semibold text-primary">
                                {{ review.rating }} / 5
                            </p>
                            <h3
                                v-if="review.title"
                                class="mt-3 text-lg font-semibold"
                            >
                                {{ review.title }}
                            </h3>
                            <p
                                class="mt-3 text-sm leading-7 text-muted-foreground"
                            >
                                {{ review.content }}
                            </p>
                            <div class="mt-5 border-t border-border pt-4">
                                <p class="text-sm font-semibold">
                                    {{ review.name }}
                                </p>
                                <p
                                    v-if="review.verified_purchase"
                                    class="mt-1 text-xs text-primary"
                                >
                                    Verified purchase
                                </p>
                                <time
                                    v-if="review.reviewed_at"
                                    :datetime="review.reviewed_at"
                                    class="mt-2 block text-xs text-muted-foreground"
                                    >{{ formatDate(review.reviewed_at) }}</time
                                >
                            </div>
                        </article>
                    </div>
                    <p v-else class="mt-8 py-5 text-sm text-muted-foreground">
                        No customer reviews yet.
                    </p>
                </TabsContent>
            </div>
        </Tabs>
        <section
            v-if="relatedProducts?.length"
            class="site-container pt-16"
            aria-labelledby="related-products-title"
        >
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="section-kicker">Keep exploring</p>
                    <h2 id="related-products-title" class="section-title mt-3">
                        More from the catalog
                    </h2>
                </div>
                <Link href="/products" class="button-secondary"
                    >All products <ArrowRight class="size-4"
                /></Link>
            </div>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <RelatedProductCard
                    v-for="related in relatedProducts"
                    :key="related.slug"
                    :product="related"
                />
            </div>
        </section>
        <section class="site-container pt-16">
            <div
                class="flex flex-col gap-6 rounded-2xl border border-border bg-accent p-7 sm:p-10 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <p class="section-kicker">Make it work for you</p>
                    <h2
                        class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        Have a question about this product?
                    </h2>
                    <p
                        class="mt-3 max-w-xl text-sm leading-6 text-muted-foreground"
                    >
                        Discuss compatibility, custom changes, installation, or
                        ongoing maintenance with ASR Tech.
                    </p>
                </div>
                <Link href="/contact" class="button-primary shrink-0"
                    >Discuss Your Project <ArrowRight class="size-4"
                /></Link>
            </div>
        </section>
        <ProductGalleryLightbox
            v-model:open="lightboxOpen"
            :images="media"
            :start-index="lightboxIndex"
        />
    </div>
</template>
