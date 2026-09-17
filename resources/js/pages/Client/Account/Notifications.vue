<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    BellOff,
    CheckCheck,
    Info,
    CheckCircle2,
    AlertTriangle,
    XCircle,
} from '@lucide/vue';
import AccountCard from '@/modules/client/components/AccountCard.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

type NotificationItem = {
    id: string;
    title: string;
    message: string;
    url: string | null;
    level: 'info' | 'success' | 'warning' | 'danger';
    read: boolean;
    created_at: string | null;
};

const props = defineProps<{
    account: { name: string; email: string; address: string[] };
    totalDue: string;
    currency: string;
    notifications: {
        data: NotificationItem[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const icons = {
    info: Info,
    success: CheckCircle2,
    warning: AlertTriangle,
    danger: XCircle,
};

const iconClasses = {
    info: 'bg-[#087f75]/10 text-[var(--client-accent)]',
    success: 'bg-emerald-500/10 text-emerald-500',
    warning: 'bg-amber-500/10 text-amber-500',
    danger: 'bg-red-500/10 text-red-500',
};

const open = (notification: NotificationItem) =>
    router.post(`/client-area/notifications/${notification.id}/read`);

const readAll = () => router.post('/client-area/notifications/read-all');

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleString() : '';

const paginationLabel = (value: string) =>
    value.replace('&laquo;', '«').replace('&raquo;', '»').trim();
</script>

<template>
    <SeoHead title="Notifications" description="Your account notifications." />

    <ClientAreaHero title="Notifications" overlap />

    <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
        <div
            class="-mt-24 grid items-start gap-6 lg:grid-cols-[360px_minmax(0,1fr)]"
        >
            <AccountCard
                :account="props.account"
                :total-due="props.totalDue"
                :currency="props.currency"
            />

            <div class="rounded-xl border bg-card">
                <div
                    class="flex items-center justify-between border-b px-6 py-4"
                >
                    <h2 class="text-lg font-bold">Notifications</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary transition hover:opacity-80"
                        @click="readAll"
                    >
                        <CheckCheck class="size-4" />
                        Mark all as read
                    </button>
                </div>

                <div
                    v-if="props.notifications.data.length === 0"
                    class="flex flex-col items-center gap-3 px-6 py-16 text-center"
                >
                    <BellOff class="size-10 text-muted-foreground/50" />
                    <p class="text-sm text-muted-foreground">
                        You have no notifications yet.
                    </p>
                </div>

                <ul v-else class="divide-y">
                    <li
                        v-for="notification in props.notifications.data"
                        :key="notification.id"
                    >
                        <button
                            type="button"
                            class="flex w-full items-start gap-4 px-6 py-4 text-left transition hover:bg-muted/40"
                            :class="notification.read ? 'opacity-60' : ''"
                            @click="open(notification)"
                        >
                            <span
                                class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg"
                                :class="
                                    iconClasses[notification.level] ??
                                    iconClasses.info
                                "
                            >
                                <component
                                    :is="
                                        icons[notification.level] ?? icons.info
                                    "
                                    class="size-4"
                                />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span
                                        class="truncate text-sm font-semibold"
                                    >
                                        {{ notification.title }}
                                    </span>
                                    <span
                                        v-if="!notification.read"
                                        class="size-2 shrink-0 rounded-full bg-primary"
                                    />
                                </span>
                                <span
                                    class="mt-0.5 block text-sm text-muted-foreground"
                                >
                                    {{ notification.message }}
                                </span>
                                <span
                                    class="mt-1 block text-xs text-muted-foreground/70"
                                >
                                    {{ formatDate(notification.created_at) }}
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>

                <div
                    v-if="props.notifications.links.length > 3"
                    class="flex flex-wrap gap-1 border-t px-6 py-4"
                >
                    <Link
                        v-for="link in props.notifications.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        class="rounded-md border px-3 py-1.5 text-sm"
                        :class="[
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : '',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        >{{ paginationLabel(link.label) }}</Link
                    >
                </div>
            </div>
        </div>
    </section>
</template>
