<script setup lang="ts">
import AccountNav from '@/modules/client/components/AccountNav.vue';

defineProps<{ title?: string; subtitle?: string; overlap?: boolean }>();
</script>

<template>
    <section
        class="border-b border-[var(--client-border)] bg-[var(--client-surface-soft)] text-[var(--client-ink)]"
        :class="overlap ? 'pb-32' : 'pb-10 sm:pb-14'"
    >
        <div class="site-container">
            <AccountNav />
            <div class="pt-9 sm:pt-12">
                <p class="section-kicker mb-3">Your ASR Tech account</p>
                <slot>
                    <h1
                        class="text-3xl font-semibold tracking-tight sm:text-4xl"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="subtitle"
                        class="mt-3 max-w-2xl text-sm leading-7 text-[var(--client-muted)] sm:text-base"
                    >
                        {{ subtitle }}
                    </p>
                </slot>
            </div>
        </div>
    </section>
</template>
