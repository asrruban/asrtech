<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Check, Copy, Download, Package, RefreshCw, Wrench } from '@lucide/vue';
import { ref } from 'vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface ServiceDetail {
    id: number;
    license_key: string;
    status: string;
    expires_at: string | null;
    created_at: string | null;
    domain: string | null;
    path: string | null;
    ip_address: string | null;
    reissue_count: number;
    product: {
        name: string;
        slug: string;
        url: string;
        type: string;
        featured_image?: string | null;
        version: string | null;
        release_date: string | null;
        compatibility: string | null;
        php_compatibility: string | null;
        category: string | null;
        has_changelog: boolean;
    } | null;
    order: {
        order_number: string;
        currency: string;
        amount: string;
        billing_cycle: string;
        payment_method: string | null;
        created_at: string | null;
    };
    subscription: {
        id: number;
        status: string;
        current_period_end: string | null;
        cancel_at_period_end: boolean;
        url: string;
    } | null;
}

interface ServiceOffering {
    id: number;
    name: string;
    slug: string;
    url: string;
    short_description: string | null;
    featured_image: string | null;
}

interface ProductRelease {
    id: number;
    version: string;
    title: string | null;
    release_notes: string | null;
    original_filename: string;
    file_size: number;
    checksum_sha256: string;
    released_at: string;
    available_until: string | null;
    download_limit: number | null;
    downloads_used: number;
    downloads_remaining: number | null;
    can_download: boolean;
    blocked_reason: string | null;
    download_url: string;
}

const props = defineProps<{
    service: ServiceDetail;
    services: ServiceOffering[];
    releases: ProductRelease[];
}>();

const activeTab = ref<'information' | 'downloads'>('information');

const copied = ref(false);
const copyKey = async () => {
    await navigator.clipboard.writeText(props.service.license_key);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const reissuing = ref(false);
const reissue = () => {
    if (
        !confirm(
            'Reissue this license? The recorded website is cleared so you can activate it on a new installation.',
        )
    ) {
        return;
    }

    router.post(
        `/client-area/licenses/${props.service.id}/reissue`,
        {},
        {
            preserveScroll: true,
            onStart: () => (reissuing.value = true),
            onFinish: () => (reissuing.value = false),
        },
    );
};

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

// WHMCS wording: yearly licenses are "Annual", one-time are "Lifetime".
const licenseType = (cycle: string) =>
    ({ yearly: 'Annual', monthly: 'Monthly', one_time: 'Lifetime' })[cycle] ??
    label(cycle);

const cycleLabel = (cycle: string) =>
    ({ yearly: 'Annually', monthly: 'Monthly', one_time: 'One Time' })[cycle] ??
    label(cycle);

const statusClass = (status: string) =>
    ({
        active: 'bg-[#5cb85c] text-white',
        suspended: 'bg-amber-500 text-white',
        expired: 'bg-red-500 text-white',
        terminated: 'bg-red-500 text-white',
    })[status] ?? 'bg-slate-400 text-white';

const moneyUsd = (currency: string, amount: string) =>
    `${new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount))} ${currency}`;

const fileSize = (bytes: number) => {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

const formatDate = (date: string | null) =>
    date
        ? new Intl.DateTimeFormat('en', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(new Date(date))
        : '—';

const dtClass =
    'text-xs font-bold tracking-wider text-muted-foreground uppercase';
</script>

<template>
    <SeoHead
        :title="props.service.product?.name ?? 'Product details'"
        description="Manage your product license."
    />

    <ClientAreaHero>
        <div class="mt-10 flex flex-col gap-6 sm:flex-row sm:items-start">
            <img
                v-if="props.service.product?.featured_image"
                :src="props.service.product.featured_image"
                alt=""
                class="size-24 shrink-0 rounded-2xl border border-white/20 object-cover shadow-lg"
            />
            <span
                v-else
                class="flex size-24 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#45b6ee] to-[#2196d8] shadow-lg"
            >
                <Package class="size-11 text-white" />
            </span>

            <div class="min-w-0">
                <p class="text-sm font-semibold text-white/75">
                    {{ props.service.product?.category ?? 'Products' }}
                    <span class="mx-1 text-white/40">/</span>
                    {{ label(props.service.product?.type ?? '') }}
                </p>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        {{ props.service.product?.name }}
                    </h1>
                    <span
                        class="rounded px-2 py-0.5 text-xs font-bold tracking-wide uppercase"
                        :class="statusClass(props.service.status)"
                    >
                        {{ label(props.service.status) }}
                    </span>
                </div>
                <p
                    class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm text-white/85"
                >
                    <span>
                        Next Due Date:
                        <strong>
                            {{
                                props.service.expires_at
                                    ? formatDate(props.service.expires_at)
                                    : 'Never'
                            }}
                        </strong>
                    </span>
                    <span>
                        Recurring Amount:
                        <strong>
                            {{
                                moneyUsd(
                                    props.service.order.currency,
                                    props.service.order.amount,
                                )
                            }}
                        </strong>
                    </span>
                </p>

                <div class="mt-5 flex flex-wrap gap-2.5">
                    <Link
                        v-if="props.service.product"
                        :href="props.service.product.url"
                        class="rounded-md bg-[#5cb85c] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4cae4c]"
                    >
                        View Product Page
                    </Link>
                    <Link
                        href="/client-area/tickets/create"
                        class="rounded-md border border-white/40 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/10"
                    >
                        Get Support
                    </Link>
                    <Link
                        href="/client-area/tickets/create"
                        class="rounded-md border border-white/40 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/10"
                    >
                        Request Cancellation
                    </Link>
                </div>
            </div>
        </div>

        <div class="mt-10 -mb-px flex gap-1">
            <button
                v-for="tab in [
                    { key: 'information' as const, label: 'Information' },
                    { key: 'downloads' as const, label: 'Downloads' },
                ]"
                :key="tab.key"
                type="button"
                class="border-b-[3px] px-4 py-3 text-sm font-bold tracking-wide transition"
                :class="
                    activeTab === tab.key
                        ? 'border-[#7ed957] text-white'
                        : 'border-transparent text-white/70 hover:text-white'
                "
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>
    </ClientAreaHero>

    <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
        <div
            v-show="activeTab === 'information'"
            class="mt-8 grid overflow-hidden rounded-xl bg-card shadow-lg lg:grid-cols-[minmax(0,1fr)_340px]"
        >
            <div class="p-6 sm:p-8">
                <h2 class="font-bold tracking-tight">License Information</h2>

                <dl class="mt-6 space-y-6">
                    <div>
                        <dt :class="dtClass">License Type</dt>
                        <dd class="mt-1.5 text-sm font-medium">
                            {{ licenseType(props.service.order.billing_cycle) }}
                        </dd>
                    </div>
                    <div>
                        <dt :class="dtClass">License Key</dt>
                        <dd class="mt-2 flex flex-wrap items-center gap-2.5">
                            <code
                                class="rounded bg-muted/60 px-2.5 py-1.5 font-mono text-sm font-semibold break-all"
                            >
                                {{ props.service.license_key }}
                            </code>
                            <span
                                class="rounded px-2 py-0.5 text-[11px] font-bold tracking-wide uppercase"
                                :class="statusClass(props.service.status)"
                            >
                                {{ label(props.service.status) }}
                            </span>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition hover:border-[#4fb250] hover:text-[#4fb250]"
                                @click="copyKey"
                            >
                                <template v-if="copied">
                                    <Check class="size-3.5 text-[#4fb250]" />
                                    Copied
                                </template>
                                <template v-else>
                                    <Copy class="size-3.5" /> Copy License
                                </template>
                            </button>
                        </dd>
                        <dd class="mt-2.5">
                            <button
                                v-if="props.service.status !== 'terminated'"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition hover:border-[#4fb250] hover:text-[#4fb250] disabled:opacity-60"
                                :disabled="reissuing"
                                @click="reissue"
                            >
                                <RefreshCw class="size-3.5" />
                                {{
                                    reissuing ? 'Reissuing…' : 'Reissue License'
                                }}
                            </button>
                        </dd>
                    </div>
                    <div>
                        <dt :class="dtClass">Valid Domains</dt>
                        <dd class="mt-1.5 text-sm font-medium">
                            {{ props.service.domain ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt :class="dtClass">Valid IP Addresses</dt>
                        <dd class="mt-1.5 text-sm font-medium">
                            {{ props.service.ip_address ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt :class="dtClass">Valid Directory</dt>
                        <dd class="mt-1.5 text-sm font-medium break-all">
                            {{ props.service.path ?? '—' }}
                        </dd>
                    </div>
                </dl>

                <h2 class="mt-10 border-t pt-8 font-bold tracking-tight">
                    Billing Information
                </h2>

                <dl
                    class="mt-6 grid gap-x-8 gap-y-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div>
                        <dt :class="dtClass">Registration Date:</dt>
                        <dd class="mt-1.5 text-sm font-medium">
                            {{ formatDate(props.service.order.created_at) }}
                        </dd>
                    </div>
                    <div>
                        <dt :class="dtClass">Product Status:</dt>
                        <dd class="mt-1.5 text-sm font-medium">
                            {{ label(props.service.status) }}
                        </dd>
                    </div>
                    <div>
                        <dt :class="dtClass">Billing Cycle:</dt>
                        <dd class="mt-1.5 text-sm font-medium">
                            {{ cycleLabel(props.service.order.billing_cycle) }}
                        </dd>
                    </div>
                    <div>
                        <dt :class="dtClass">Next Due Date:</dt>
                        <dd class="mt-1.5 text-sm font-medium">
                            {{
                                props.service.expires_at
                                    ? formatDate(props.service.expires_at)
                                    : 'Never'
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt :class="dtClass">Recurring Amount:</dt>
                        <dd class="mt-1.5 text-sm font-medium">
                            {{
                                moneyUsd(
                                    props.service.order.currency,
                                    props.service.order.amount,
                                )
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt :class="dtClass">Payment Method:</dt>
                        <dd class="mt-1.5 text-sm font-medium">
                            {{
                                props.service.order.payment_method
                                    ? label(props.service.order.payment_method)
                                    : '—'
                            }}
                        </dd>
                    </div>
                </dl>

                <div
                    v-if="props.service.subscription"
                    class="mt-7 flex flex-col justify-between gap-4 rounded-xl border border-primary/15 bg-primary/5 p-5 sm:flex-row sm:items-center"
                >
                    <div>
                        <p class="text-sm font-bold">Recurring subscription</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ label(props.service.subscription.status) }} ·
                            <template
                                v-if="
                                    props.service.subscription
                                        .current_period_end
                                "
                            >
                                {{
                                    props.service.subscription
                                        .cancel_at_period_end
                                        ? 'Access ends'
                                        : 'Renews'
                                }}
                                {{
                                    formatDate(
                                        props.service.subscription
                                            .current_period_end,
                                    )
                                }}
                            </template>
                        </p>
                    </div>
                    <Link
                        :href="props.service.subscription.url"
                        class="inline-flex shrink-0 items-center justify-center rounded-md border bg-card px-4 py-2 text-sm font-semibold hover:bg-muted"
                    >
                        Manage subscription
                    </Link>
                </div>
            </div>

            <div
                class="border-t bg-muted/40 p-6 text-center sm:p-8 lg:border-t-0 lg:border-l"
            >
                <h2 class="font-bold tracking-tight">Latest Version</h2>

                <p
                    class="mt-6 text-5xl font-bold tracking-tight text-[#4fb250]"
                >
                    {{
                        props.service.product?.version
                            ? `v${props.service.product.version}`
                            : '—'
                    }}
                </p>

                <dl class="mt-6 space-y-1.5 text-sm">
                    <div v-if="props.service.product?.release_date">
                        <dt class="inline text-muted-foreground">
                            Release Date:
                        </dt>
                        <dd class="inline font-medium">
                            {{ formatDate(props.service.product.release_date) }}
                        </dd>
                    </div>
                    <div v-if="props.service.product?.compatibility">
                        <dt class="inline text-muted-foreground">
                            WHMCS Compatibility:
                        </dt>
                        <dd class="inline font-medium">
                            {{ props.service.product.compatibility }}
                        </dd>
                    </div>
                    <div v-if="props.service.product?.php_compatibility">
                        <dt class="inline text-muted-foreground">
                            PHP Compatibility:
                        </dt>
                        <dd class="inline font-medium">
                            {{ props.service.product.php_compatibility }}
                        </dd>
                    </div>
                </dl>

                <Link
                    v-if="props.service.product"
                    :href="props.service.product.url"
                    class="mt-7 inline-flex items-center gap-2 rounded-md bg-[#5cb85c] px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4cae4c]"
                >
                    <Download class="size-4" /> View Product Page
                </Link>

                <p v-if="props.service.product?.has_changelog" class="mt-4">
                    <Link
                        :href="props.service.product.url"
                        class="text-sm font-medium text-muted-foreground underline-offset-2 hover:text-foreground hover:underline"
                    >
                        View Changelog
                    </Link>
                </p>
            </div>
        </div>

        <div v-show="activeTab === 'downloads'" class="mt-8 space-y-5">
            <div class="rounded-xl bg-card p-6 shadow-lg sm:p-8">
                <p class="text-sm font-semibold text-primary">
                    Secure product delivery
                </p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">
                    Product releases
                </h2>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                    Packages are delivered privately and are available only to
                    the owner of an active license. Every download is recorded
                    for account security.
                </p>
            </div>

            <article
                v-for="release in props.releases"
                :key="release.id"
                class="overflow-hidden rounded-xl bg-card shadow-lg"
            >
                <div
                    class="flex flex-col justify-between gap-5 border-b border-border p-6 sm:flex-row sm:items-start sm:p-8"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary"
                            >
                                Version {{ release.version }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                Released
                                {{ formatDate(release.released_at) }}
                            </span>
                        </div>
                        <h3
                            v-if="release.title"
                            class="mt-3 text-xl font-bold tracking-tight"
                        >
                            {{ release.title }}
                        </h3>
                        <p
                            v-if="release.release_notes"
                            class="mt-3 text-sm leading-6 whitespace-pre-line text-muted-foreground"
                        >
                            {{ release.release_notes }}
                        </p>
                    </div>

                    <a
                        v-if="release.can_download"
                        :href="release.download_url"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md bg-[#5cb85c] px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#4cae4c]"
                    >
                        <Download class="size-4" /> Download package
                    </a>
                    <span
                        v-else
                        class="inline-flex shrink-0 items-center justify-center rounded-md bg-muted px-5 py-3 text-sm font-semibold text-muted-foreground"
                    >
                        {{ release.blocked_reason ?? 'Download unavailable' }}
                    </span>
                </div>

                <dl class="grid gap-px bg-border sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-card p-5">
                        <dt class="text-xs font-semibold text-muted-foreground">
                            File
                        </dt>
                        <dd class="mt-1 text-sm font-medium break-all">
                            {{ release.original_filename }}
                        </dd>
                    </div>
                    <div class="bg-card p-5">
                        <dt class="text-xs font-semibold text-muted-foreground">
                            Package size
                        </dt>
                        <dd class="mt-1 text-sm font-medium">
                            {{ fileSize(release.file_size) }}
                        </dd>
                    </div>
                    <div class="bg-card p-5">
                        <dt class="text-xs font-semibold text-muted-foreground">
                            Downloads
                        </dt>
                        <dd class="mt-1 text-sm font-medium">
                            {{ release.downloads_used }} used
                            <template
                                v-if="release.downloads_remaining !== null"
                            >
                                · {{ release.downloads_remaining }} remaining
                            </template>
                        </dd>
                    </div>
                    <div class="bg-card p-5">
                        <dt class="text-xs font-semibold text-muted-foreground">
                            Available until
                        </dt>
                        <dd class="mt-1 text-sm font-medium">
                            {{
                                release.available_until
                                    ? formatDate(release.available_until)
                                    : 'No expiry'
                            }}
                        </dd>
                    </div>
                </dl>
                <div class="border-t border-border px-6 py-4 sm:px-8">
                    <p class="text-xs font-semibold text-muted-foreground">
                        SHA-256 checksum
                    </p>
                    <code
                        class="mt-1 block font-mono text-[11px] break-all text-foreground/70"
                        >{{ release.checksum_sha256 }}</code
                    >
                </div>
            </article>

            <div
                v-if="props.releases.length === 0"
                class="rounded-xl bg-card p-10 text-center shadow-lg"
            >
                <span
                    class="mx-auto flex size-20 items-center justify-center rounded-full border-2 border-muted text-muted-foreground/40"
                >
                    <Download class="size-9" />
                </span>
                <p class="mt-5 font-semibold">No downloads available yet</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Published product releases for your license will appear
                    here.
                </p>
            </div>
        </div>

        <template v-if="props.services.length">
            <h2 class="mt-12 text-xl font-bold tracking-tight">
                Available Services
            </h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <Link
                    v-for="offering in props.services"
                    :key="offering.id"
                    :href="offering.url"
                    class="flex items-stretch gap-0 overflow-hidden rounded-xl bg-card shadow-lg transition hover:shadow-xl"
                >
                    <img
                        v-if="offering.featured_image"
                        :src="offering.featured_image"
                        alt=""
                        class="w-28 shrink-0 object-cover"
                    />
                    <span
                        v-else
                        class="flex w-28 shrink-0 items-center justify-center bg-gradient-to-br from-[#8e6ee6] to-[#6a48c9] text-white"
                    >
                        <Wrench class="size-9" />
                    </span>
                    <span class="min-w-0 p-5">
                        <span class="block font-bold tracking-tight">
                            {{ offering.name }}
                        </span>
                        <span
                            v-if="offering.short_description"
                            class="mt-1 block text-sm text-muted-foreground"
                        >
                            {{ offering.short_description }}
                        </span>
                    </span>
                </Link>
            </div>
        </template>
    </section>
</template>
