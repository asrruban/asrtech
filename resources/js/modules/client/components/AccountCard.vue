<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Pencil } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    account: {
        name: string;
        email: string;
        address: string[];
    };
    totalDue: string;
    currency: string;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const initials = computed(() =>
    props.account.name
        .split(' ')
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

const logout = () => router.post('/logout');

const moneyUsd = (currency: string, amount: string | number) =>
    `${new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount))} ${currency}`;
</script>

<template>
    <div
        class="flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm"
    >
        <div
            class="bg-[var(--client-accent-soft)] p-6 text-[var(--client-ink)]"
        >
            <p
                class="text-xs font-bold tracking-widest text-[var(--client-muted)] uppercase"
            >
                Account
            </p>
            <div class="mt-2 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-2xl font-bold break-words">
                        {{ props.account.name }}
                    </p>
                    <p
                        v-for="line in props.account.address"
                        :key="line"
                        class="mt-0.5 text-sm break-words text-[var(--client-muted)]"
                    >
                        {{ line }}
                    </p>
                    <p
                        class="mt-0.5 text-sm break-words text-[var(--client-muted)]"
                    >
                        {{ props.account.email }}
                    </p>
                </div>
                <img
                    v-if="user?.avatar"
                    :src="user.avatar"
                    alt=""
                    class="size-16 shrink-0 rounded-full border-2 border-[var(--client-border)] object-cover"
                />
                <span
                    v-else
                    class="flex size-16 shrink-0 items-center justify-center rounded-full border-2 border-[var(--client-border)] bg-[var(--client-surface)] text-xl font-bold"
                >
                    {{ initials }}
                </span>
            </div>
        </div>
        <div
            class="flex border-y bg-[var(--client-surface)] text-[var(--client-muted)]"
        >
            <Link
                href="/client-area/account-details"
                class="inline-flex flex-1 items-center justify-center gap-1.5 px-4 py-3 text-sm font-bold transition hover:bg-[var(--client-surface-soft)]"
            >
                <Pencil class="size-3.5" /> Edit Details
            </Link>
            <button
                type="button"
                class="inline-flex flex-1 items-center justify-center gap-1.5 px-4 py-3 text-sm font-bold transition hover:bg-[var(--client-surface-soft)]"
                @click="logout"
            >
                <LogOut class="size-3.5" /> Log Out
            </button>
        </div>
        <div class="flex flex-1 flex-col p-6">
            <p
                class="text-xs font-bold tracking-widest text-muted-foreground uppercase"
            >
                Total Amount Due
            </p>
            <p
                class="mt-2 mb-5 text-3xl font-bold sm:text-4xl"
                :class="
                    Number(props.totalDue) > 0
                        ? 'text-red-500'
                        : 'text-[var(--client-accent)]'
                "
            >
                {{ moneyUsd(props.currency, props.totalDue) }}
            </p>
            <Link
                href="/client-area/invoices"
                class="mt-auto block rounded-md border px-4 py-2.5 text-center text-sm font-semibold text-muted-foreground transition hover:border-[#087f75] hover:text-[var(--client-accent)]"
            >
                {{
                    Number(props.totalDue) > 0
                        ? 'View Invoices'
                        : 'View Invoices'
                }}
            </Link>
        </div>
    </div>
</template>
