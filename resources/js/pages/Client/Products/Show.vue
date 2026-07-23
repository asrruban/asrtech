<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    Check,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Code2,
    ExternalLink,
    FileText,
    Info,
    Package,
    PackageCheck,
    ShieldCheck,
    ShoppingCart,
    Star,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    Tabs,
    TabsContent,
    TabsIndicator,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import ProductGalleryLightbox from '@/modules/client/components/ProductGalleryLightbox.vue';
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
const site = computed(() => page.props.site);
const user = computed(() => page.props.auth?.user);
const reviewForm = useForm({
    rating: props.reviewState.review?.rating ?? 5,
    title: props.reviewState.review?.title ?? '',
    content: props.reviewState.review?.content ?? '',
});
const submitReview = () => {
    reviewForm.post(`${props.product.url}/reviews`, {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = 'reviews';
        },
    });
};

const buying = ref(false);
const addingToCart = ref(false);

const sendToCart = (stayOnProduct: boolean) => {
    if (!selectedPrice.value || buying.value || addingToCart.value) {
        return;
    }

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
            onFinish: () => {
                buying.value = false;
                addingToCart.value = false;
            },
        },
    );
};

const buyNow = () => sendToCart(false);
const addToCart = () => sendToCart(true);

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

    if (!props.product.featured_image) {
        return gallery;
    }

    return [
        {
            url: props.product.featured_image,
            alt_text: props.product.name,
        },
        ...gallery.filter(
            (image) => image.url !== props.product.featured_image,
        ),
    ];
});

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

const openLightbox = (index: number) => {
    if (media.value.length === 0) {
        return;
    }

    lightboxIndex.value = index;
    lightboxOpen.value = true;
};

const activeTab = ref('overview');

const tabs = computed(() => [
    { value: 'overview', label: 'Overview', count: null, show: true },
    {
        value: 'features',
        label: 'Features',
        count: null,
        show: (props.product.feature_groups ?? []).length > 0,
    },
    {
        value: 'screenshots',
        label: 'Screenshots',
        count: media.value.length > 1 ? media.value.length : null,
        show: media.value.length > 1,
    },
    {
        value: 'changelog',
        label: 'Changelog',
        count: props.product.changelog?.length || null,
        show: (props.product.changelog ?? []).length > 0,
    },
    {
        value: 'reviews',
        label: 'Reviews',
        count: props.product.reviews?.length || null,
        show: true,
    },
    {
        value: 'documentation',
        label: 'Documentation',
        count: null,
        show:
            Boolean(props.product.documentation_content) ||
            Boolean(props.product.documentation_url),
    },
]);

const averageRating = computed(() => {
    const reviews = props.product.reviews ?? [];

    if (reviews.length === 0) {
        return null;
    }

    return (
        reviews.reduce((total, review) => total + Number(review.rating), 0) /
        reviews.length
    ).toFixed(1);
});

const starsFilled = computed(() => Math.round(Number(averageRating.value)));

const ribbon = computed(
    () =>
        props.product.badge ||
        props.product.compatibility ||
        (props.product.version ? `v${props.product.version}` : null),
);

const discountPercent = computed(() => {
    if (!selectedPrice.value?.sale_price) {
        return null;
    }

    const percent = Math.round(
        (1 -
            Number(selectedPrice.value.sale_price) /
                Number(selectedPrice.value.price)) *
            100,
    );

    return percent >= 5 ? percent : null;
});

const cardRibbon = computed(() => {
    if (props.product.trial_url) {
        return 'Free Trial';
    }

    return discountPercent.value ? `Save ${discountPercent.value}%` : null;
});

const includes = computed(() => {
    if (selectedPrice.value?.features?.length) {
        return selectedPrice.value.features;
    }

    return [
        'Instant license delivery after payment',
        'Free module updates',
        'Access to technical support',
        'Secure online ordering',
    ];
});

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

const cycleLabel = (cycle: string) => {
    if (cycle === 'monthly') {
        return 'Monthly';
    }

    if (cycle === 'yearly') {
        return 'Annually';
    }

    return 'One-Time Payment';
};

const purchaseUrl = (price: ProductPrice | null = selectedPrice.value) => {
    if (price?.purchase_url || props.product.purchase_url) {
        return price?.purchase_url || props.product.purchase_url;
    }

    if (site.value.supportEmail) {
        return `mailto:${site.value.supportEmail}?subject=${encodeURIComponent(`Order: ${props.product.name}`)}`;
    }

    return null;
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

const catalogTrail = computed(() => {
    if (props.product.type === 'whmcs_module') {
        return ['WHMCS', 'Extension Modules'];
    }

    if (props.product.type === 'template') {
        return ['Templates', 'Website Templates'];
    }

    return ['Services', 'Web Development'];
});

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));

// Gallery Slider Logic
const currentImageIndex = ref(0);

const nextImage = () => {
    if (media.value.length === 0) {
        return;
    }

    currentImageIndex.value =
        (currentImageIndex.value + 1) % media.value.length;
};

const prevImage = () => {
    if (media.value.length === 0) {
        return;
    }

    currentImageIndex.value =
        (currentImageIndex.value - 1 + media.value.length) % media.value.length;
};

const touchStartX = ref(0);
const touchEndX = ref(0);

const handleTouchStart = (e: TouchEvent) => {
    touchStartX.value = e.changedTouches[0].screenX;
};

const handleTouchEnd = (e: TouchEvent) => {
    touchEndX.value = e.changedTouches[0].screenX;
    handleSwipe();
};

const handleSwipe = () => {
    const swipeThreshold = 50;

    if (touchEndX.value < touchStartX.value - swipeThreshold) {
        nextImage();
    } else if (touchEndX.value > touchStartX.value + swipeThreshold) {
        prevImage();
    }
};
</script>

<template>
    <SeoHead
        :title="product.name"
        :description="product.short_description"
        :image="product.featured_image"
        :seo="product.seo"
        type="product"
    />

    <div class="bg-[#e9edf3] pb-20 font-raleway dark:bg-slate-950">
        <!-- ModulesGarden-inspired product stage, adapted to ASRTech branding. -->
        <section
            class="relative overflow-hidden bg-[radial-gradient(circle_at_78%_36%,rgba(43,174,255,0.24),transparent_28%),radial-gradient(circle_at_12%_80%,rgba(0,35,105,0.45),transparent_35%),linear-gradient(128deg,#0874df_0%,#075dbb_48%,#064296_100%)] pt-5 pb-8 text-white sm:pt-7 sm:pb-10 lg:pb-12"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#fff_1px,transparent_1px),linear-gradient(to_bottom,#fff_1px,transparent_1px)] bg-[size:42px_42px] opacity-[0.07]"
            ></div>
            <div
                class="pointer-events-none absolute top-24 -right-28 size-96 rounded-full border-[70px] border-white/5"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-56 -left-32 size-[34rem] rounded-full border-[90px] border-sky-300/5"
            ></div>

            <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumbs -->
                <nav
                    class="flex min-w-0 [scrollbar-width:none] items-center gap-2 overflow-x-auto pb-1 text-[11px] font-semibold whitespace-nowrap text-blue-100/75 sm:text-xs [&::-webkit-scrollbar]:hidden"
                    aria-label="Breadcrumb"
                >
                    <Link href="/products" class="transition hover:text-white">
                        {{ catalogTrail[0] }}
                    </Link>
                    <ChevronRight class="size-3 shrink-0 text-blue-200/40" />
                    <span>{{ catalogTrail[1] }}</span>
                    <ChevronRight class="size-3 shrink-0 text-blue-200/40" />
                    <span class="truncate text-white">{{
                        product.category.name
                    }}</span>
                </nav>

                <!-- 2-Column Hero Content Grid -->
                <div
                    class="mt-6 grid items-start gap-7 md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:mt-8 lg:gap-10"
                >
                    <!-- Left Column: Gallery & Screenshots -->
                    <!-- Slider Gallery (lightSlider look-alike) -->
                    <div class="hidden space-y-3 md:block">
                        <div
                            class="relative overflow-hidden rounded-sm border border-white/10 bg-[radial-gradient(circle_at_50%_44%,rgba(255,255,255,0.2),transparent_34%),linear-gradient(145deg,#2cb7ef,#187fd3_62%,#0d62b9)] shadow-2xl shadow-blue-950/25"
                            @touchstart="handleTouchStart"
                            @touchend="handleTouchEnd"
                        >
                            <!-- Custom Ribbon -->
                            <div
                                v-if="ribbon"
                                class="pointer-events-none absolute top-0 left-0 z-10 h-28 w-28 overflow-hidden"
                            >
                                <span
                                    class="absolute top-[22px] left-[-39px] w-36 -rotate-45 bg-[#b7ec37] py-1 text-center text-[9px] font-extrabold tracking-wider whitespace-nowrap text-[#0f3f68] uppercase shadow-md"
                                >
                                    {{ ribbon }}
                                </span>
                            </div>

                            <!-- Main Slider Item Display -->
                            <div
                                class="relative flex min-h-[330px] w-full items-center justify-center p-8 lg:min-h-[390px]"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                                    :aria-label="`View larger screenshot`"
                                    @click="openLightbox(currentImageIndex)"
                                >
                                    <img
                                        v-if="media.length > 0"
                                        :src="media[currentImageIndex].url"
                                        :alt="
                                            media[currentImageIndex].alt_text ||
                                            product.name
                                        "
                                        class="max-h-[310px] w-auto max-w-[92%] rounded-md bg-white/95 object-contain shadow-2xl shadow-blue-950/25 transition duration-500 hover:scale-[1.01] lg:max-h-[350px]"
                                    />
                                    <Package
                                        v-else
                                        class="size-24 text-white/40"
                                    />
                                </button>

                                <!-- Left Arrow -->
                                <button
                                    v-if="media.length > 1"
                                    type="button"
                                    class="absolute top-1/2 left-3 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-blue-950/45 text-white transition hover:bg-blue-950/70 focus-visible:outline-2 focus-visible:outline-white"
                                    aria-label="Previous image"
                                    @click="prevImage"
                                >
                                    <ChevronLeft class="size-6" />
                                </button>

                                <!-- Right Arrow -->
                                <button
                                    v-if="media.length > 1"
                                    type="button"
                                    class="absolute top-1/2 right-3 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-blue-950/45 text-white transition hover:bg-blue-950/70 focus-visible:outline-2 focus-visible:outline-white"
                                    aria-label="Next image"
                                    @click="nextImage"
                                >
                                    <ChevronRight class="size-6" />
                                </button>

                                <!-- Counter Badge (visible on mobile/tablet) -->
                                <div
                                    v-if="media.length > 1"
                                    class="absolute bottom-0 flex items-center justify-center rounded-full bg-black/40 px-3 py-1 text-[11px] font-bold text-white backdrop-blur-sm"
                                >
                                    {{ currentImageIndex + 1 }} /
                                    {{ media.length }}
                                </div>
                            </div>
                        </div>

                        <!-- Gallery Thumbnails (Click to update slide) -->
                        <div
                            v-if="media.length > 1"
                            class="flex [scrollbar-width:thin] gap-2 overflow-x-auto"
                        >
                            <button
                                v-for="(image, index) in media"
                                :key="image.url"
                                type="button"
                                class="shrink-0 overflow-hidden rounded-sm border-2 bg-white/10 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                                :class="
                                    index === currentImageIndex
                                        ? 'border-[#b7ec37] opacity-100 shadow-lg shadow-blue-950/20'
                                        : 'border-white/10 opacity-60 hover:border-white/40 hover:opacity-100'
                                "
                                :aria-label="`Slide to image ${index + 1}`"
                                @click="currentImageIndex = index"
                            >
                                <img
                                    :src="image.url"
                                    :alt="image.alt_text || product.name"
                                    class="h-14 w-20 object-cover lg:h-16 lg:w-24"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: Product Metadata & Pricing Card -->
                    <div class="space-y-6">
                        <!-- Product Icon, Title and Details -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-4 md:block">
                                <button
                                    type="button"
                                    class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-white/15 bg-sky-400/25 p-2 shadow-lg shadow-blue-950/20 md:hidden"
                                    aria-label="View product screenshots"
                                    @click="openLightbox(currentImageIndex)"
                                >
                                    <img
                                        v-if="media.length > 0"
                                        :src="media[0].url"
                                        :alt="product.name"
                                        class="max-h-full max-w-full rounded-sm bg-white object-contain"
                                    />
                                    <Package v-else class="size-9 text-white" />
                                </button>
                                <div class="min-w-0 space-y-1">
                                    <h1
                                        class="text-xl leading-tight font-extrabold tracking-tight text-white sm:text-3xl lg:text-[2.15rem]"
                                    >
                                        {{ product.name }}
                                    </h1>
                                    <p
                                        class="line-clamp-2 text-xs leading-5 font-medium text-blue-100/75 md:hidden"
                                    >
                                        {{ product.short_description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Rating and Badges -->
                            <div
                                class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] font-semibold text-blue-100/85 sm:text-xs"
                            >
                                <button
                                    v-if="averageRating"
                                    type="button"
                                    class="flex items-center gap-1.5 transition hover:text-white"
                                    @click="activeTab = 'reviews'"
                                >
                                    <span class="flex gap-0.5 text-[#ffb200]">
                                        <Star
                                            v-for="number in 5"
                                            :key="number"
                                            class="size-3 fill-current"
                                            :class="
                                                number <= starsFilled
                                                    ? 'text-[#ffb200]'
                                                    : 'text-white/20'
                                            "
                                        />
                                    </span>
                                    <span>{{ averageRating }}</span>
                                </button>
                                <span
                                    v-if="product.version"
                                    class="flex items-center gap-1"
                                >
                                    <Info class="size-3 text-blue-300" /> v{{
                                        product.version
                                    }}
                                </span>
                                <span
                                    v-if="product.release_date"
                                    class="flex items-center gap-1"
                                >
                                    <CalendarDays
                                        class="size-3 text-blue-300"
                                    />
                                    {{ formatDate(product.release_date) }}
                                </span>
                                <span
                                    v-if="product.compatibility"
                                    class="flex items-center gap-1"
                                >
                                    <PackageCheck
                                        class="size-3 text-blue-300"
                                    />
                                    {{ product.compatibility }}
                                </span>
                                <span
                                    v-if="product.php_compatibility"
                                    class="flex items-center gap-1"
                                >
                                    <Code2 class="size-3 text-blue-300" />
                                    {{ product.php_compatibility }}
                                </span>
                            </div>
                        </div>

                        <!-- Pricing Cycles Tab Row -->
                        <div class="space-y-0">
                            <div
                                class="flex [scrollbar-width:none] gap-1 overflow-x-auto [&::-webkit-scrollbar]:hidden"
                                role="tablist"
                                aria-label="License options"
                            >
                                <div
                                    v-for="(price, index) in enabledPrices"
                                    :key="price.id"
                                    class="relative"
                                >
                                    <button
                                        type="button"
                                        role="tab"
                                        :aria-selected="
                                            selectedPriceIndex === index
                                        "
                                        class="shrink-0 px-3 py-3 text-xs font-bold transition duration-200 focus-visible:outline-2 focus-visible:outline-white sm:px-4 sm:text-sm"
                                        :class="
                                            selectedPriceIndex === index
                                                ? 'text-white'
                                                : 'text-blue-100/70 hover:text-white'
                                        "
                                        @click="selectedPriceIndex = index"
                                    >
                                        {{
                                            price.name ||
                                            label(price.billing_cycle)
                                        }}
                                    </button>

                                    <!-- Pointer Arrow pointing to the Pricing Card -->
                                    <div
                                        v-if="selectedPriceIndex === index"
                                        class="absolute -bottom-px left-1/2 z-20 hidden h-0 w-0 -translate-x-1/2 border-r-[7px] border-b-[7px] border-l-[7px] border-r-transparent border-b-white border-l-transparent sm:block"
                                    ></div>
                                </div>

                                <a
                                    v-if="product.demo_url"
                                    :href="product.demo_url"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="flex shrink-0 items-center gap-1 px-3 py-3 text-xs font-bold text-blue-100/75 transition hover:text-white sm:px-4 sm:text-sm"
                                >
                                    Live Demo <ExternalLink class="size-3" />
                                </a>
                            </div>

                            <!-- Pricing Card Block (White Overlay Card) -->
                            <div
                                class="relative overflow-hidden rounded-sm bg-white text-slate-900 shadow-2xl shadow-blue-950/25 dark:bg-slate-900 dark:text-white"
                            >
                                <!-- Card Orange Banner / Ribbon -->
                                <div
                                    v-if="cardRibbon"
                                    class="absolute top-0 right-0 z-10 h-24 w-24 overflow-hidden"
                                >
                                    <span
                                        class="absolute top-[18px] right-[-44px] w-40 rotate-45 bg-[#f5842a] py-1 text-center text-[9px] font-extrabold tracking-wider whitespace-nowrap text-white uppercase shadow-md"
                                    >
                                        {{ cardRibbon }}
                                    </span>
                                </div>

                                <!-- Desktop layout: 2 columns; Mobile layout: stacks -->
                                <div
                                    class="grid divide-y divide-slate-100 sm:grid-cols-[0.85fr_1.15fr] sm:divide-x sm:divide-y-0 dark:divide-white/10"
                                >
                                    <!-- Price & Purchase Side -->
                                    <div
                                        class="flex flex-col justify-between p-5 sm:p-6"
                                    >
                                        <div>
                                            <p
                                                class="text-[10px] font-extrabold tracking-wider text-slate-400 uppercase"
                                            >
                                                Price
                                            </p>
                                            <template v-if="selectedPrice">
                                                <div
                                                    class="mt-2 flex items-baseline gap-2"
                                                >
                                                    <p
                                                        v-if="
                                                            selectedPrice.sale_price
                                                        "
                                                        class="text-sm font-bold text-slate-400 line-through"
                                                    >
                                                        {{
                                                            money(
                                                                selectedPrice.currency,
                                                                selectedPrice.price,
                                                            )
                                                        }}
                                                    </p>
                                                    <p
                                                        class="text-3xl leading-none font-light tracking-tight text-[#f5842a] sm:text-4xl"
                                                    >
                                                        {{
                                                            money(
                                                                selectedPrice.currency,
                                                                selectedPrice.sale_price ||
                                                                    selectedPrice.price,
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                                <p
                                                    class="mt-1 text-xs font-semibold text-slate-500"
                                                >
                                                    {{
                                                        cycleLabel(
                                                            selectedPrice.billing_cycle,
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    v-if="
                                                        Number(
                                                            selectedPrice.setup_fee,
                                                        ) > 0
                                                    "
                                                    class="mt-1 text-[11px] text-slate-400"
                                                >
                                                    +
                                                    {{
                                                        money(
                                                            selectedPrice.currency,
                                                            selectedPrice.setup_fee ||
                                                                0,
                                                        )
                                                    }}
                                                    setup fee
                                                </p>
                                            </template>
                                            <template v-else>
                                                <p
                                                    class="mt-2 text-xl font-extrabold text-slate-800 dark:text-white"
                                                >
                                                    Custom quote
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    Talk to our team
                                                </p>
                                            </template>
                                        </div>

                                        <!-- Mobile purchase buttons row -->
                                        <div
                                            class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-1"
                                        >
                                            <button
                                                v-if="selectedPrice"
                                                type="button"
                                                :disabled="buying"
                                                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#58c957] px-4 text-sm font-bold text-white shadow-lg shadow-[#58c957]/20 transition duration-200 hover:bg-[#45b944] disabled:opacity-50"
                                                @click="buyNow"
                                            >
                                                {{
                                                    buying
                                                        ? 'Adding…'
                                                        : 'Buy Now'
                                                }}
                                            </button>
                                            <a
                                                v-else-if="purchaseUrl()"
                                                :href="
                                                    purchaseUrl() || undefined
                                                "
                                                target="_blank"
                                                rel="noreferrer"
                                                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#58c957] px-4 text-sm font-bold text-white shadow-lg shadow-[#58c957]/20 transition duration-200 hover:bg-[#45b944]"
                                            >
                                                Buy Now
                                            </a>

                                            <button
                                                v-if="selectedPrice"
                                                type="button"
                                                :disabled="addingToCart"
                                                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 transition hover:border-blue-400 hover:text-blue-600 disabled:opacity-50"
                                                @click="addToCart"
                                            >
                                                <ShoppingCart class="size-4" />
                                                {{
                                                    addingToCart
                                                        ? 'Adding…'
                                                        : 'Add To Cart'
                                                }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Includes & Guarantee Side -->
                                    <div
                                        class="bg-slate-50/40 p-6 dark:bg-slate-900/20"
                                    >
                                        <Tabs
                                            default-value="includes"
                                            class="gap-4"
                                        >
                                            <TabsList
                                                class="relative h-auto w-full justify-start gap-4 rounded-none border-b border-slate-200 bg-transparent p-0 dark:border-white/10"
                                            >
                                                <TabsTrigger
                                                    value="includes"
                                                    class="h-auto flex-none rounded-none border-0 bg-transparent px-0 pt-0 pb-2 text-[10px] font-extrabold tracking-wider text-slate-400 uppercase shadow-none data-[state=active]:bg-transparent data-[state=active]:text-[#4fb250] data-[state=active]:shadow-none dark:data-[state=active]:bg-transparent dark:data-[state=active]:text-[#84d780]"
                                                >
                                                    Includes
                                                </TabsTrigger>
                                                <TabsTrigger
                                                    value="guarantee"
                                                    class="h-auto flex-none rounded-none border-0 bg-transparent px-0 pt-0 pb-2 text-[10px] font-extrabold tracking-wider text-slate-400 uppercase shadow-none data-[state=active]:bg-transparent data-[state=active]:text-[#4fb250] data-[state=active]:shadow-none dark:data-[state=active]:bg-transparent dark:data-[state=active]:text-[#84d780]"
                                                >
                                                    30-Day Guarantee
                                                </TabsTrigger>
                                                <TabsIndicator
                                                    class="bg-[#4fb250]"
                                                />
                                            </TabsList>
                                            <TabsContent
                                                value="includes"
                                                class="pt-4"
                                            >
                                                <ul class="space-y-2.5">
                                                    <li
                                                        v-for="item in includes"
                                                        :key="item"
                                                        class="flex items-start gap-2.5 text-xs leading-5 font-semibold text-slate-600 dark:text-slate-300"
                                                    >
                                                        <Check
                                                            class="mt-0.5 size-4 shrink-0 text-[#4fb250]"
                                                        />
                                                        <span>{{ item }}</span>
                                                    </li>
                                                </ul>
                                            </TabsContent>
                                            <TabsContent
                                                value="guarantee"
                                                class="pt-4"
                                            >
                                                <div
                                                    class="flex items-start gap-3"
                                                >
                                                    <ShieldCheck
                                                        class="mt-0.5 size-8 shrink-0 text-[#4fb250]"
                                                    />
                                                    <p
                                                        class="text-xs leading-5 font-semibold text-slate-600 dark:text-slate-300"
                                                    >
                                                        Order with confidence —
                                                        if the product doesn't
                                                        fit your project,
                                                        contact our support team
                                                        within 30 days of
                                                        purchase and we'll make
                                                        it right.
                                                    </p>
                                                </div>
                                            </TabsContent>
                                        </Tabs>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Product content navigation continues the blue catalog stage. -->
        <div
            class="relative z-30 bg-[#06479a] text-white shadow-[0_8px_22px_rgba(15,46,89,0.16)]"
        >
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <!-- Left: Scrollable Tabs -->
                    <div
                        class="flex [scrollbar-width:none] gap-1 overflow-x-auto [&::-webkit-scrollbar]:hidden"
                        role="tablist"
                    >
                        <div
                            v-for="tab in tabs.filter((item) => item.show)"
                            :key="tab.value"
                            class="relative"
                        >
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="activeTab === tab.value"
                                class="shrink-0 px-3 py-4 text-xs font-bold transition duration-200 sm:px-4 sm:text-sm"
                                :class="
                                    activeTab === tab.value
                                        ? 'text-white'
                                        : 'text-blue-100/60 hover:text-white'
                                "
                                @click="activeTab = tab.value"
                            >
                                {{ tab.label }}
                                <span
                                    v-if="tab.count"
                                    class="ml-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300"
                                >
                                    {{ tab.count }}
                                </span>
                            </button>

                            <!-- Pointer triangle pointing down -->
                            <div
                                v-if="activeTab === tab.value"
                                class="absolute bottom-0 left-1/2 z-20 h-0 w-0 -translate-x-1/2 border-r-[7px] border-b-[7px] border-l-[7px] border-r-transparent border-b-[#e9edf3] border-l-transparent dark:border-b-slate-950"
                            ></div>
                        </div>
                    </div>

                    <!-- Right: Documentation Button (Desktop only) -->
                    <a
                        v-if="documentationHref"
                        :href="documentationHref"
                        :target="documentationIsExternal ? '_blank' : undefined"
                        :rel="
                            documentationIsExternal ? 'noreferrer' : undefined
                        "
                        class="hidden h-9 items-center gap-1.5 border-l border-white/15 px-4 text-xs font-bold text-blue-100 transition hover:text-white sm:inline-flex"
                    >
                        <FileText class="size-4" /> Documentation
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Tab Content Container -->
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-8">
                <!-- White Content Card -->
                <div
                    class="overflow-hidden rounded-sm bg-white shadow-[0_18px_55px_rgba(40,55,82,0.12)] ring-1 ring-slate-200/60 dark:bg-slate-900 dark:ring-white/10"
                >
                    <Tabs v-model="activeTab" class="gap-0">
                        <TabsContent value="overview">
                            <div class="grid">
                                <article class="p-6 sm:p-8 lg:p-10">
                                    <h2
                                        class="text-xl font-extrabold tracking-tight text-slate-800 sm:text-2xl dark:text-white"
                                    >
                                        Overview
                                    </h2>
                                    <div
                                        class="mt-4 text-sm leading-7 font-medium whitespace-pre-line text-slate-600 sm:text-[15px] sm:leading-8 dark:text-slate-300"
                                    >
                                        {{
                                            product.description ||
                                            product.short_description
                                        }}
                                    </div>
                                </article>

                                <!-- Product Info Panel inside Overview tab -->
                                <aside
                                    class="border-t border-slate-100 bg-slate-50/50 p-6 sm:p-8 dark:border-white/10 dark:bg-slate-950/40"
                                >
                                    <h2
                                        class="text-xs font-extrabold tracking-wider text-slate-800 uppercase dark:text-slate-100"
                                    >
                                        Product information
                                    </h2>
                                    <dl
                                        class="mt-4 grid gap-4 divide-y divide-slate-200 text-sm sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-3 dark:divide-white/10"
                                    >
                                        <div
                                            v-if="product.version"
                                            class="flex flex-col gap-1 py-2"
                                        >
                                            <dt class="text-slate-500">
                                                Version
                                            </dt>
                                            <dd
                                                class="font-bold text-slate-800 dark:text-slate-200"
                                            >
                                                {{ product.version }}
                                            </dd>
                                        </div>
                                        <div class="flex flex-col gap-1 py-2">
                                            <dt class="text-slate-500">
                                                Category
                                            </dt>
                                            <dd
                                                class="font-bold text-slate-800 dark:text-slate-200"
                                            >
                                                {{ product.category.name }}
                                            </dd>
                                        </div>
                                        <div
                                            v-if="product.compatibility"
                                            class="flex flex-col gap-1 py-2"
                                        >
                                            <dt class="text-slate-500">
                                                Compatibility
                                            </dt>
                                            <dd
                                                class="font-bold text-slate-800 dark:text-slate-200"
                                            >
                                                {{ product.compatibility }}
                                            </dd>
                                        </div>
                                        <div
                                            v-if="product.php_compatibility"
                                            class="flex flex-col gap-1 py-2"
                                        >
                                            <dt class="text-slate-500">PHP</dt>
                                            <dd
                                                class="font-bold text-slate-800 dark:text-slate-200"
                                            >
                                                {{ product.php_compatibility }}
                                            </dd>
                                        </div>
                                        <div
                                            v-if="product.release_date"
                                            class="flex flex-col gap-1 py-2"
                                        >
                                            <dt class="text-slate-500">
                                                Last update
                                            </dt>
                                            <dd
                                                class="font-bold text-slate-800 dark:text-slate-200"
                                            >
                                                {{
                                                    formatDate(
                                                        product.release_date,
                                                    )
                                                }}
                                            </dd>
                                        </div>
                                    </dl>

                                    <!-- Requirements block -->
                                    <div
                                        v-if="product.requirements?.length"
                                        class="mt-6 border-t border-slate-200/60 pt-6 dark:border-white/10"
                                    >
                                        <h3
                                            class="text-xs font-extrabold tracking-wider text-slate-800 uppercase dark:text-slate-100"
                                        >
                                            Requirements
                                        </h3>
                                        <ul
                                            class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2"
                                        >
                                            <li
                                                v-for="requirement in product.requirements"
                                                :key="requirement.label"
                                                class="flex items-start gap-2 text-xs leading-5 text-slate-600 dark:text-slate-300"
                                            >
                                                <Check
                                                    class="mt-0.5 size-3.5 shrink-0 text-[#4fb250]"
                                                />
                                                <span
                                                    ><strong
                                                        >{{
                                                            requirement.label
                                                        }}:</strong
                                                    >
                                                    {{
                                                        requirement.value
                                                    }}</span
                                                >
                                            </li>
                                        </ul>
                                    </div>
                                </aside>
                            </div>

                            <!-- Optional Services / Addons inside Overview tab -->
                            <div
                                v-if="product.addons?.length"
                                class="border-t border-slate-100 bg-slate-50/50 p-6 sm:p-8 dark:border-white/10 dark:bg-slate-950/30"
                            >
                                <h2
                                    class="text-lg font-extrabold text-slate-800 sm:text-xl dark:text-white"
                                >
                                    Optional services
                                </h2>
                                <div class="mt-5 grid gap-4 md:grid-cols-2">
                                    <div
                                        v-for="addon in product.addons"
                                        :key="addon.name"
                                        class="flex flex-col justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center dark:border-white/10 dark:bg-slate-900"
                                    >
                                        <div>
                                            <h3
                                                class="font-bold text-slate-800 dark:text-white"
                                            >
                                                {{ addon.name }}
                                            </h3>
                                            <p
                                                v-if="addon.description"
                                                class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400"
                                            >
                                                {{ addon.description }}
                                            </p>
                                        </div>
                                        <div
                                            class="flex shrink-0 items-center justify-between gap-3 sm:block sm:text-right"
                                        >
                                            <p
                                                v-if="addon.price"
                                                class="font-extrabold text-[#f5842a]"
                                            >
                                                {{
                                                    money(
                                                        addon.currency,
                                                        addon.sale_price ||
                                                            addon.price,
                                                    )
                                                }}
                                            </p>
                                            <a
                                                v-if="
                                                    addon.purchase_url ||
                                                    purchaseUrl()
                                                "
                                                :href="
                                                    addon.purchase_url ||
                                                    purchaseUrl() ||
                                                    undefined
                                                "
                                                target="_blank"
                                                rel="noreferrer"
                                                class="mt-1 inline-flex text-xs font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                                >Add service →</a
                                            >
                                            <span
                                                v-else
                                                class="mt-1 inline-flex text-xs font-bold text-slate-400"
                                                >Contact sales</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabsContent>

                        <!-- Features Tab -->
                        <TabsContent
                            value="features"
                            class="p-6 sm:p-8 lg:p-10"
                        >
                            <h2
                                class="text-xl font-extrabold tracking-tight text-slate-800 sm:text-2xl dark:text-white"
                            >
                                Features
                            </h2>
                            <div class="mt-6 grid gap-6 md:grid-cols-2">
                                <div
                                    v-for="group in product.feature_groups"
                                    :key="group.title"
                                    class="rounded-xl border border-slate-200/80 p-5 sm:p-6 dark:border-white/10"
                                >
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#eff9ef] text-[#4fb250] dark:bg-[#4fb250]/10"
                                        >
                                            <Check class="size-4" />
                                        </span>
                                        <div>
                                            <h3
                                                class="font-extrabold text-slate-800 dark:text-white"
                                            >
                                                {{ group.title }}
                                            </h3>
                                            <p
                                                v-if="group.description"
                                                class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-slate-400"
                                            >
                                                {{ group.description }}
                                            </p>
                                        </div>
                                    </div>
                                    <ul
                                        class="mt-4 space-y-2.5 border-t border-slate-100 pt-4 dark:border-white/10"
                                    >
                                        <li
                                            v-for="feature in group.features"
                                            :key="feature"
                                            class="flex items-start gap-2.5 text-sm leading-6 font-medium text-slate-600 dark:text-slate-300"
                                        >
                                            <CheckCircle2
                                                class="mt-1 size-4 shrink-0 text-[#4fb250]"
                                            />
                                            <span>{{ feature }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </TabsContent>

                        <!-- Screenshots Tab -->
                        <TabsContent
                            value="screenshots"
                            class="p-6 sm:p-8 lg:p-10"
                        >
                            <h2
                                class="text-xl font-extrabold tracking-tight text-slate-800 sm:text-2xl dark:text-white"
                            >
                                Screenshots
                            </h2>
                            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                                <button
                                    v-for="(image, index) in media"
                                    :key="image.url"
                                    type="button"
                                    class="group overflow-hidden rounded-xl border border-slate-200 bg-[#f7f9fa] text-left transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#4fb250] dark:border-white/10 dark:bg-slate-950"
                                    @click="openLightbox(index)"
                                >
                                    <img
                                        :src="image.url"
                                        :alt="image.alt_text || product.name"
                                        class="aspect-[16/10] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                    />
                                    <span
                                        class="block border-t border-slate-200 px-4 py-3 text-xs font-semibold text-slate-600 dark:border-white/10 dark:text-slate-300"
                                        >{{
                                            image.alt_text || product.name
                                        }}</span
                                    >
                                </button>
                            </div>
                        </TabsContent>

                        <!-- Changelog Tab -->
                        <TabsContent
                            value="changelog"
                            class="p-6 sm:p-8 lg:p-10"
                        >
                            <h2
                                class="text-xl font-extrabold tracking-tight text-slate-800 sm:text-2xl dark:text-white"
                            >
                                Changelog
                            </h2>
                            <div class="mt-6 space-y-5">
                                <article
                                    v-for="release in product.changelog"
                                    :key="`${release.version}-${release.released_at}`"
                                    class="grid gap-4 rounded-xl border-l-4 border-[#4fb250] bg-[#f7f9fa] p-5 sm:grid-cols-[170px_minmax(0,1fr)] sm:p-6 dark:bg-slate-950/50"
                                >
                                    <div>
                                        <h3
                                            class="font-extrabold text-slate-900 dark:text-white"
                                        >
                                            Version {{ release.version }}
                                        </h3>
                                        <time
                                            v-if="release.released_at"
                                            class="mt-1 block text-xs text-slate-500"
                                            >{{
                                                formatDate(release.released_at)
                                            }}</time
                                        >
                                    </div>
                                    <ul class="space-y-2.5">
                                        <li
                                            v-for="note in release.notes"
                                            :key="note"
                                            class="flex items-start gap-2.5 text-sm leading-6 font-medium text-slate-600 dark:text-slate-300"
                                        >
                                            <Check
                                                class="mt-1 size-4 shrink-0 text-[#4fb250]"
                                            />
                                            <span>{{ note }}</span>
                                        </li>
                                    </ul>
                                </article>
                            </div>
                        </TabsContent>

                        <!-- Reviews Tab -->
                        <TabsContent
                            id="reviews"
                            value="reviews"
                            class="p-6 sm:p-8 lg:p-10"
                        >
                            <div
                                class="flex flex-wrap items-end justify-between gap-4"
                            >
                                <h2
                                    class="text-xl font-extrabold tracking-tight text-slate-800 sm:text-2xl dark:text-white"
                                >
                                    Reviews
                                </h2>
                                <div
                                    v-if="averageRating"
                                    class="flex items-center gap-2 rounded-md bg-[#eff9ef] px-3 py-2 text-sm font-extrabold text-[#357e37] dark:bg-[#4fb250]/10 dark:text-[#84d780]"
                                >
                                    <Star class="size-4 fill-current" />
                                    {{ averageRating }} out of 5
                                </div>
                            </div>

                            <form
                                v-if="reviewState.can_review"
                                class="mt-6 space-y-5 rounded-xl border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/5"
                                @submit.prevent="submitReview"
                            >
                                <div>
                                    <h3
                                        class="font-extrabold text-slate-900 dark:text-white"
                                    >
                                        {{
                                            reviewState.review
                                                ? 'Update your review'
                                                : 'Write your review'
                                        }}
                                    </h3>
                                    <p
                                        class="mt-1 text-sm text-slate-500 dark:text-slate-400"
                                    >
                                        Your name will appear with a verified
                                        purchase badge.
                                    </p>
                                    <p
                                        v-if="
                                            reviewState.review?.status ===
                                            'pending'
                                        "
                                        class="mt-2 text-xs font-bold text-amber-700 dark:text-amber-300"
                                    >
                                        Your review is awaiting moderation.
                                    </p>
                                    <p
                                        v-else-if="
                                            reviewState.review?.status ===
                                            'hidden'
                                        "
                                        class="mt-2 text-xs font-bold text-slate-600 dark:text-slate-300"
                                    >
                                        Your review is currently hidden.
                                        Updating it will return it to
                                        moderation.
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="block text-sm font-bold text-slate-700 dark:text-slate-200"
                                    >
                                        Rating
                                    </label>
                                    <div class="flex gap-1">
                                        <button
                                            v-for="number in 5"
                                            :key="number"
                                            type="button"
                                            class="text-[#ffb200]"
                                            :aria-label="`${number} star rating`"
                                            @click="reviewForm.rating = number"
                                        >
                                            <Star
                                                class="size-6"
                                                :class="
                                                    number <= reviewForm.rating
                                                        ? 'fill-current'
                                                        : 'text-slate-300 dark:text-slate-700'
                                                "
                                            />
                                        </button>
                                    </div>
                                    <p
                                        v-if="reviewForm.errors.rating"
                                        class="text-xs font-medium text-red-600"
                                    >
                                        {{ reviewForm.errors.rating }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <label
                                        for="review-title"
                                        class="block text-sm font-bold text-slate-700 dark:text-slate-200"
                                    >
                                        Review title
                                    </label>
                                    <input
                                        id="review-title"
                                        v-model="reviewForm.title"
                                        maxlength="255"
                                        placeholder="A short summary"
                                        class="h-11 w-full rounded-md border border-slate-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-slate-950"
                                    />
                                    <p
                                        v-if="reviewForm.errors.title"
                                        class="text-xs font-medium text-red-600"
                                    >
                                        {{ reviewForm.errors.title }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <label
                                        for="review-content"
                                        class="block text-sm font-bold text-slate-700 dark:text-slate-200"
                                    >
                                        Your experience
                                    </label>
                                    <textarea
                                        id="review-content"
                                        v-model="reviewForm.content"
                                        rows="5"
                                        maxlength="5000"
                                        required
                                        class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-slate-950"
                                    />
                                    <p
                                        v-if="reviewForm.errors.content"
                                        class="text-xs font-medium text-red-600"
                                    >
                                        {{ reviewForm.errors.content }}
                                    </p>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="reviewForm.processing"
                                    class="inline-flex h-11 items-center justify-center rounded-md bg-[#4fb250] px-5 text-sm font-extrabold text-white transition hover:bg-[#459d46] disabled:opacity-60"
                                >
                                    {{
                                        reviewForm.processing
                                            ? 'Saving…'
                                            : reviewState.review
                                              ? 'Update review'
                                              : 'Publish review'
                                    }}
                                </button>
                            </form>

                            <div
                                v-else-if="!user"
                                class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm font-medium text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                            >
                                Purchased this product?
                                <Link
                                    :href="reviewState.login_url"
                                    class="font-extrabold text-[#357e37] hover:underline dark:text-[#84d780]"
                                >
                                    Sign in to write a review.
                                </Link>
                            </div>

                            <div
                                v-else
                                class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm font-medium text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                            >
                                Reviews can be written by verified customers
                                after purchasing this product.
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <article
                                    v-for="review in product.reviews ?? []"
                                    :key="
                                        review.id ??
                                        `${review.name}-${review.reviewed_at}`
                                    "
                                    class="rounded-xl border border-slate-200 p-5 dark:border-white/10"
                                >
                                    <div class="flex gap-1 text-[#ffb200]">
                                        <Star
                                            v-for="number in 5"
                                            :key="number"
                                            class="size-4"
                                            :class="
                                                number <= review.rating
                                                    ? 'fill-current'
                                                    : 'text-slate-200 dark:text-slate-700'
                                            "
                                        />
                                    </div>
                                    <h3
                                        class="mt-4 font-extrabold text-slate-900 dark:text-white"
                                    >
                                        {{
                                            review.title ||
                                            'Verified customer review'
                                        }}
                                    </h3>
                                    <p
                                        class="mt-3 text-sm leading-6 font-medium text-slate-600 dark:text-slate-300"
                                    >
                                        {{ review.content }}
                                    </p>
                                    <div
                                        class="mt-5 border-t border-slate-100 pt-4 dark:border-white/10"
                                    >
                                        <p
                                            class="text-xs font-extrabold text-slate-700 dark:text-slate-200"
                                        >
                                            {{ review.name }}
                                        </p>
                                        <p
                                            v-if="review.verified_purchase"
                                            class="mt-1 text-[11px] font-bold text-[#357e37] dark:text-[#84d780]"
                                        >
                                            Verified purchase
                                        </p>
                                        <time
                                            v-if="review.reviewed_at"
                                            class="mt-1 block text-xs text-slate-400"
                                            >{{
                                                formatDate(review.reviewed_at)
                                            }}</time
                                        >
                                    </div>
                                </article>
                                <p
                                    v-if="(product.reviews ?? []).length === 0"
                                    class="text-sm font-medium text-slate-500 dark:text-slate-400"
                                >
                                    No customer reviews yet.
                                </p>
                            </div>
                        </TabsContent>

                        <!-- Documentation Tab -->
                        <TabsContent
                            value="documentation"
                            class="p-6 sm:p-8 lg:p-10"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-5"
                            >
                                <h2
                                    class="text-xl font-extrabold tracking-tight text-slate-800 sm:text-2xl dark:text-white"
                                >
                                    Documentation
                                </h2>
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
                                            ? 'noreferrer'
                                            : undefined
                                    "
                                    class="inline-flex h-10 items-center gap-2 rounded-md border border-slate-200 px-4 text-sm font-bold text-slate-600 transition hover:border-[#4fb250] hover:text-[#4fb250] dark:border-white/10 dark:text-slate-300"
                                >
                                    <FileText class="size-4" /> Open complete
                                    docs <ExternalLink class="size-3.5" />
                                </a>
                            </div>
                            <div
                                v-if="product.documentation_content"
                                class="mt-6 rounded-xl border-l-4 border-[#4fb250] bg-[#f7f9fa] p-5 text-sm leading-7 font-medium whitespace-pre-line text-slate-600 sm:p-7 dark:bg-slate-950/50 dark:text-slate-300"
                            >
                                {{ product.documentation_content }}
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Documentation Button (Mobile only) -->
                <div class="sm:hidden">
                    <a
                        v-if="documentationHref"
                        :href="documentationHref"
                        :target="documentationIsExternal ? '_blank' : undefined"
                        :rel="
                            documentationIsExternal ? 'noreferrer' : undefined
                        "
                        class="flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-[#5cb85c] px-4 text-sm font-bold text-[#5cb85c] transition hover:bg-[#5cb85c]/5"
                    >
                        <FileText class="size-4" /> Documentation
                    </a>
                </div>
            </div>
        </main>

        <!-- See Also Section -->
        <section
            v-if="props.relatedProducts?.length"
            aria-labelledby="see-also-heading"
            class="mx-auto mt-12 max-w-6xl px-4 pb-12 sm:px-6 lg:px-8"
        >
            <h2
                id="see-also-heading"
                class="text-2xl font-extrabold tracking-tight text-slate-800 sm:text-3xl dark:text-white"
            >
                See Also
            </h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <RelatedProductCard
                    v-for="related in props.relatedProducts"
                    :key="related.slug"
                    :product="related"
                />
            </div>
        </section>

        <!-- Call to Action Banner -->
        <section
            class="mt-12 bg-[linear-gradient(150deg,#123c9a_0%,#0e2f7c_100%)] text-white"
        >
            <div
                class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-12 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8"
            >
                <div>
                    <p
                        class="text-xs font-extrabold tracking-[0.2em] text-[#9ade2f] uppercase"
                    >
                        Ready to get started?
                    </p>
                    <h2
                        class="mt-2 max-w-3xl text-2xl font-extrabold sm:text-3xl"
                    >
                        Put {{ product.name }} to work for your business.
                    </h2>
                    <p class="mt-2 text-sm text-blue-100/80">
                        Professional setup assistance and product support are
                        available.
                    </p>
                </div>
                <button
                    v-if="selectedPrice"
                    type="button"
                    :disabled="buying"
                    class="inline-flex h-12 min-w-36 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#58c957] px-6 text-sm font-bold text-white shadow-lg shadow-black/10 transition hover:bg-[#45b944] disabled:opacity-60"
                    @click="buyNow"
                >
                    {{ buying ? 'Adding…' : 'Buy Now' }}
                    <ArrowRight v-if="!buying" class="size-4" />
                </button>
                <a
                    v-else-if="purchaseUrl()"
                    :href="purchaseUrl() || undefined"
                    target="_blank"
                    rel="noreferrer"
                    class="inline-flex h-12 min-w-36 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#58c957] px-6 text-sm font-bold text-white shadow-lg shadow-black/10 transition hover:bg-[#45b944]"
                >
                    Buy Now <ArrowRight class="size-4" />
                </a>
            </div>
        </section>

        <ProductGalleryLightbox
            v-model:open="lightboxOpen"
            :images="media"
            :start-index="lightboxIndex"
        />
    </div>
</template>
