<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const providers = computed<string[]>(
    () => (page.props.socialProviders as string[]) ?? [],
);

const labels: Record<string, string> = {
    google: 'Google',
    github: 'GitHub',
};
</script>

<template>
    <div v-if="providers.length" class="space-y-3">
        <div class="flex items-center gap-3">
            <span class="h-px flex-1 bg-border" />
            <span class="text-xs font-semibold text-muted-foreground uppercase">
                or continue with
            </span>
            <span class="h-px flex-1 bg-border" />
        </div>

        <div class="grid gap-2" :class="providers.length > 1 ? 'grid-cols-2' : ''">
            <a
                v-for="provider in providers"
                :key="provider"
                :href="`/auth/${provider}/redirect`"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-md border text-sm font-semibold transition hover:bg-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#4fb250]"
            >
                <svg
                    v-if="provider === 'google'"
                    class="size-4"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1Z"
                    />
                    <path
                        fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23Z"
                    />
                    <path
                        fill="#FBBC05"
                        d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84Z"
                    />
                    <path
                        fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15A11 11 0 0 0 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52Z"
                    />
                </svg>
                <svg
                    v-else-if="provider === 'github'"
                    class="size-4 fill-current"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        d="M12 .5A11.5 11.5 0 0 0 .5 12.28c0 5.2 3.3 9.6 7.86 11.16.58.11.79-.26.79-.57v-2c-3.2.71-3.87-1.58-3.87-1.58-.53-1.37-1.28-1.74-1.28-1.74-1.05-.73.08-.72.08-.72 1.15.08 1.76 1.22 1.76 1.22 1.03 1.8 2.7 1.28 3.36.98.1-.77.4-1.28.73-1.58-2.55-.3-5.23-1.31-5.23-5.82 0-1.29.45-2.34 1.18-3.16-.12-.3-.51-1.5.11-3.12 0 0 .97-.32 3.17 1.2a10.7 10.7 0 0 1 5.78 0c2.2-1.52 3.16-1.2 3.16-1.2.63 1.62.24 2.82.12 3.12.74.82 1.18 1.87 1.18 3.16 0 4.52-2.69 5.51-5.25 5.8.41.37.78 1.08.78 2.18v3.24c0 .31.2.69.8.57A11.78 11.78 0 0 0 23.5 12.3 11.5 11.5 0 0 0 12 .5Z"
                    />
                </svg>
                {{ labels[provider] ?? provider }}
            </a>
        </div>
    </div>
</template>
