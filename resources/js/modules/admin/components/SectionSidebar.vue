<script setup lang="ts">
defineProps(['title', 'items', 'modelValue']);
defineEmits(['update:modelValue']);
</script>

<template>
    <aside class="min-w-0 lg:sticky lg:top-6 lg:self-start">
        <div class="mb-3 px-2">
            <h2 class="text-sm font-semibold">{{ title }}</h2>
        </div>
        <nav
            class="flex gap-1 overflow-x-auto pb-2 lg:flex-col lg:overflow-visible lg:pb-0"
            :aria-label="`${title} sections`"
        >
            <button
                v-for="item in items"
                :key="item.value"
                type="button"
                class="shrink-0 rounded-md px-3 py-2 text-left text-sm font-medium transition-colors lg:w-full"
                :class="
                    modelValue === item.value
                        ? 'bg-muted text-foreground shadow-sm'
                        : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground'
                "
                :aria-current="modelValue === item.value ? 'page' : undefined"
                @click="$emit('update:modelValue', item.value)"
            >
                {{ item.label }}
            </button>
        </nav>
    </aside>
</template>
