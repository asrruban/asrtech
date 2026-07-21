<script setup lang="ts">
import { Wrench } from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

defineProps<{ message?: string | null }>();
</script>

<template>
    <SeoHead title="Maintenance" />

    <section
        class="flex min-h-[70vh] items-center justify-center bg-muted/40 px-4 py-16"
    >
        <div class="w-full max-w-md text-center">
            <div class="mb-6 flex justify-center">
                <div
                    class="flex size-14 items-center justify-center rounded-2xl bg-[#26364d] text-white shadow-sm"
                >
                    <Wrench class="size-7" />
                </div>
            </div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                We'll be right back
            </h1>
            <p class="mt-3 leading-7 text-muted-foreground">
                {{
                    message ||
                    'The site is undergoing scheduled maintenance. Please check back shortly.'
                }}
            </p>
        </div>
    </section>
</template>
