<script setup lang="ts">
import { RefreshCw, Wrench } from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
defineProps<{ message?: string | null }>();
</script>
<template>
    <SeoHead
        title="Site maintenance"
        description="ASR Tech is temporarily unavailable for maintenance."
        :seo="{ robots: 'noindex,nofollow' }"
    />
    <section
        class="flex min-h-[65vh] items-center justify-center bg-muted/40 px-4 py-20"
    >
        <div class="w-full max-w-lg text-center">
            <span
                class="mx-auto flex size-16 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary"
                ><Wrench class="size-7"
            /></span>
            <p class="section-kicker mt-8">A little technical care</p>
            <h1 class="section-title mt-4">We'll be back.</h1>
            <p class="body-copy mt-5">
                {{
                    message ||
                    'ASR Tech is temporarily unavailable while maintenance is in progress. Please try again later.'
                }}
            </p>
            <a href="/" class="button-secondary mt-8"
                ><RefreshCw class="size-4" /> Try again</a
            >
        </div>
    </section>
</template>
