<script setup lang="ts">
import { ChevronLeft, ChevronRight, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';

interface LightboxImage {
    url: string;
    alt_text?: string | null;
}

const props = defineProps<{
    images: LightboxImage[];
    open: boolean;
    startIndex?: number;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const current = ref(props.startIndex ?? 0);

watch(
    () => props.open,
    (open) => {
        if (open) {
            current.value = Math.min(
                props.startIndex ?? 0,
                Math.max(props.images.length - 1, 0),
            );
        }
    },
);

const previous = () =>
    (current.value =
        (current.value - 1 + props.images.length) % props.images.length);
const next = () => (current.value = (current.value + 1) % props.images.length);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="max-w-[calc(100%-1.5rem)] gap-3 border-none bg-transparent p-0 shadow-none sm:max-w-4xl lg:max-w-6xl"
            :show-close-button="false"
            @keydown.left.prevent="previous"
            @keydown.right.prevent="next"
        >
            <DialogTitle class="sr-only">
                {{ images[current]?.alt_text || 'Product screenshot' }}
            </DialogTitle>

            <div class="relative">
                <img
                    v-if="images[current]"
                    :src="images[current].url"
                    :alt="images[current].alt_text || 'Product screenshot'"
                    class="mx-auto max-h-[75vh] w-auto rounded-lg bg-white object-contain shadow-2xl"
                />

                <button
                    type="button"
                    class="absolute -top-3 -right-3 flex size-9 items-center justify-center rounded-full bg-white text-slate-700 shadow-lg transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    aria-label="Close gallery"
                    @click="emit('update:open', false)"
                >
                    <X class="size-4" />
                </button>

                <template v-if="images.length > 1">
                    <button
                        type="button"
                        class="absolute top-1/2 left-2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-lg transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:-left-5"
                        aria-label="Previous image"
                        @click="previous"
                    >
                        <ChevronLeft class="size-5" />
                    </button>
                    <button
                        type="button"
                        class="absolute top-1/2 right-2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-lg transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:-right-5"
                        aria-label="Next image"
                        @click="next"
                    >
                        <ChevronRight class="size-5" />
                    </button>
                </template>
            </div>

            <p class="text-center text-xs font-semibold text-white/90">
                <span v-if="images[current]?.alt_text">
                    {{ images[current].alt_text }} ·
                </span>
                {{ current + 1 }} / {{ images.length }}
            </p>

            <div
                v-if="images.length > 1"
                class="mx-auto flex max-w-full [scrollbar-width:thin] gap-2 overflow-x-auto pb-1"
            >
                <button
                    v-for="(image, index) in images"
                    :key="image.url"
                    type="button"
                    class="shrink-0 overflow-hidden rounded-md border-2 bg-white transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    :class="
                        current === index
                            ? 'border-[#4fb250] opacity-100'
                            : 'border-transparent opacity-50 hover:opacity-100'
                    "
                    :aria-label="`View image ${index + 1}`"
                    @click="current = index"
                >
                    <img
                        :src="image.url"
                        :alt="image.alt_text || ''"
                        class="h-12 w-18 object-cover"
                    />
                </button>
            </div>
        </DialogContent>
    </Dialog>
</template>
