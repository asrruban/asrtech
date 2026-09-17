<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
</script>
<template>
    <SeoHead
        title="Appearance settings"
        description="Choose your account display preference."
    />
    <ClientAreaHero
        title="Appearance"
        subtitle="Make your workspace comfortable to use."
    />
    <section class="site-container py-12">
        <div
            class="max-w-2xl rounded-2xl border bg-[var(--client-surface)] p-6 sm:p-9"
        >
            <h2 class="text-xl font-semibold">Display preference</h2>
            <p class="mt-3 mb-6 text-sm leading-7 text-[var(--client-muted)]">
                Choose light, dark, or your device setting. Your preference is
                saved on this device.
            </p>
            <AppearanceTabs />
        </div>
    </section>
</template>
