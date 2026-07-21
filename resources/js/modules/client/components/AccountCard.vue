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
    <div class="flex flex-col overflow-hidden rounded-xl bg-card shadow-lg">
        <div
            class="bg-gradient-to-br from-[#45b6ee] to-[#2196d8] p-6 text-white"
        >
            <p
                class="text-xs font-bold tracking-widest text-white/75 uppercase"
            >
                Account
            </p>
            <div class="mt-2 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="truncate text-2xl font-bold">
                        {{ props.account.name }}
                    </p>
                    <p
                        v-for="line in props.account.address"
                        :key="line"
                        class="mt-0.5 truncate text-sm text-white/85"
                    >
                        {{ line }}
                    </p>
                    <p class="mt-0.5 truncate text-sm text-white/85">
                        {{ props.account.email }}
                    </p>
                </div>
                <img
                    v-if="user?.avatar"
                    :src="user.avatar"
                    alt=""
                    class="size-16 shrink-0 rounded-full border-2 border-white/50 object-cover"
                />
                <span
                    v-else
                    class="flex size-16 shrink-0 items-center justify-center rounded-full border-2 border-white/50 bg-white/20 text-xl font-bold"
                >
                    {{ initials }}
                </span>
            </div>
        </div>
        <div class="flex bg-[#1f89c9] text-white">
            <Link
                href="/client-area/account-details"
                class="inline-flex flex-1 items-center justify-center gap-1.5 px-4 py-3 text-sm font-bold transition hover:bg-white/10"
            >
                <Pencil class="size-3.5" /> Edit Details
            </Link>
            <button
                type="button"
                class="inline-flex flex-1 items-center justify-center gap-1.5 px-4 py-3 text-sm font-bold transition hover:bg-white/10"
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
                        : 'text-[#4fb250]'
                "
            >
                {{ moneyUsd(props.currency, props.totalDue) }}
            </p>
            <Link
                href="/client-area/invoices"
                class="mt-auto block rounded-md border px-4 py-2.5 text-center text-sm font-semibold text-muted-foreground transition hover:border-[#4fb250] hover:text-[#4fb250]"
            >
                {{
                    Number(props.totalDue) > 0
                        ? 'Pay All Invoices'
                        : 'View Invoices'
                }}
            </Link>
        </div>
    </div>
</template>
