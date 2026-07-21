<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { BadgeCheck, Check, Copy, Globe, RefreshCw, Settings2 } from '@lucide/vue';
import { ref } from 'vue';

export interface ServiceItem {
    id: number;
    license_key: string;
    status: string;
    expires_at?: string | null;
    created_at: string;
    domain?: string | null;
    path?: string | null;
    ip_address?: string | null;
    product: {
        name: string;
        slug: string;
        type: string;
        featured_image?: string | null;
    };
    order_number: string;
}

const props = defineProps<{
    service: ServiceItem;
}>();

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

const statusClass = (status: string) =>
    ({
        active: 'bg-[#eff9ef] text-[#357e37] dark:bg-[#4fb250]/10 dark:text-[#84d780]',
        suspended:
            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        expired:
            'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        terminated:
            'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
    })[status] ?? 'bg-slate-100 text-slate-600';

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));
</script>

<template>
    <article class="rounded-2xl border bg-card p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <Link
                    :href="`/client-area/product/${props.service.id}`"
                    class="font-bold tracking-tight hover:text-[#4fb250]"
                >
                    {{ props.service.product.name }}
                </Link>
                <p class="mt-1 text-xs text-muted-foreground">
                    Order {{ props.service.order_number }} ·
                    {{ formatDate(props.service.created_at) }}
                </p>
            </div>
            <span
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"
                :class="statusClass(props.service.status)"
            >
                <BadgeCheck class="size-3.5" />
                {{ label(props.service.status) }}
            </span>
        </div>

        <div
            class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-dashed bg-muted/40 px-4 py-3"
        >
            <code class="truncate font-mono text-sm font-semibold">
                {{ props.service.license_key }}
            </code>
            <button
                type="button"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition hover:border-[#4fb250] hover:text-[#4fb250] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#4fb250]"
                @click="copyKey"
            >
                <template v-if="copied">
                    <Check class="size-3.5 text-[#4fb250]" /> Copied
                </template>
                <template v-else><Copy class="size-3.5" /> Copy</template>
            </button>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 text-xs text-muted-foreground">
                <p>
                    <template v-if="props.service.expires_at">
                        Valid until {{ formatDate(props.service.expires_at) }}
                    </template>
                    <template v-else>Lifetime license — never expires</template>
                </p>
                <p
                    v-if="props.service.domain"
                    class="mt-1 flex items-center gap-1.5"
                >
                    <Globe class="size-3.5 shrink-0" />
                    <span class="truncate">{{ props.service.domain }}</span>
                </p>
                <p v-else class="mt-1">Not activated on a website yet</p>
            </div>
            <div class="flex shrink-0 gap-2">
                <button
                    v-if="props.service.status !== 'terminated'"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition hover:border-[#4fb250] hover:text-[#4fb250] disabled:opacity-60"
                    :disabled="reissuing"
                    @click="reissue"
                >
                    <RefreshCw class="size-3.5" />
                    {{ reissuing ? 'Reissuing…' : 'Reissue' }}
                </button>
                <Link
                    :href="`/client-area/product/${props.service.id}`"
                    class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition hover:border-[#4fb250] hover:text-[#4fb250]"
                >
                    <Settings2 class="size-3.5" /> Manage
                </Link>
            </div>
        </div>
    </article>
</template>
