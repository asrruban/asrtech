<script setup lang="ts">
import AccountNav from '@/modules/client/components/AccountNav.vue';

defineProps<{
    title?: string;
    subtitle?: string;
    overlap?: boolean;
}>();
</script>

<template>
    <section
        class="bg-gradient-to-b from-[#2178d4] via-[#1c69bf] to-[#1257a3] text-white"
        :class="overlap ? 'pb-32' : 'pb-12'"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <AccountNav />
            <slot>
                <h1
                    class="mt-10 text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="mt-3 max-w-2xl text-white/80">
                    {{ subtitle }}
                </p>
            </slot>
        </div>
    </section>
</template>
