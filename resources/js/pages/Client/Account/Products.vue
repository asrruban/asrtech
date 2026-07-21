<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, KeyRound, Package } from '@lucide/vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
import type { ServiceItem } from '@/modules/client/components/ServiceCard.vue';
import ServiceCard from '@/modules/client/components/ServiceCard.vue';

const props = defineProps<{
    services: ServiceItem[];
}>();
</script>

<template>
    <SeoHead
        title="My products & services"
        description="Your purchased products and license keys."
    />

    <ClientAreaHero
        title="Products/Services"
        subtitle="Everything you have purchased — active services are listed first."
    />

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2.5">
            <KeyRound class="size-5 text-[#4fb250]" />
            <h2 class="text-xl font-bold tracking-tight sm:text-2xl">
                Your products & services
            </h2>
        </div>

        <div v-if="props.services.length" class="mt-6 grid gap-5 lg:grid-cols-2">
            <ServiceCard
                v-for="service in props.services"
                :key="service.id"
                :service="service"
            />
        </div>
        <div
            v-else
            class="mt-6 rounded-2xl border border-dashed p-12 text-center"
        >
            <Package class="mx-auto size-10 text-muted-foreground/50" />
            <p class="mt-4 font-semibold">No products or services yet</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Buy a product and your license key will appear here instantly.
            </p>
            <Link
                href="/products"
                class="mt-5 inline-flex items-center gap-2 rounded-md bg-[#4fb250] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#439c45]"
            >
                Browse products <ArrowRight class="size-4" />
            </Link>
        </div>
    </section>
</template>
