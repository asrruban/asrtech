<script setup lang="ts">
import { Blocks, Code2, LayoutTemplate, Puzzle } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    name: string;
    type?: string;
    image?: string | null;
    eager?: boolean;
}>();
const icon = computed(() => {
    if (props.type?.includes('template')) {
        return LayoutTemplate;
    }

    if (props.type?.includes('wordpress')) {
        return Puzzle;
    }

    if (props.type?.includes('development')) {
        return Code2;
    }

    return Blocks;
});
</script>

<template>
    <div class="product-visual">
        <img
            v-if="image"
            :src="image"
            :alt="name"
            :loading="eager ? 'eager' : 'lazy'"
            decoding="async"
            class="absolute inset-0 size-full object-contain p-5"
        />
        <div v-else class="product-diagram" aria-hidden="true">
            <div class="diagram-line line-one"></div>
            <div class="diagram-line line-two"></div>
            <div class="diagram-node node-one"></div>
            <div class="diagram-node node-two"></div>
            <div class="diagram-center">
                <component :is="icon" class="size-10 stroke-[1.25]" />
            </div>
            <div class="diagram-node node-three"></div>
            <div class="diagram-node node-four"></div>
        </div>
    </div>
</template>

<style scoped>
.product-visual {
    position: relative;
    display: grid;
    aspect-ratio: 16 / 10;
    place-items: center;
    overflow: hidden;
    background: #eef4f2;
}
.product-diagram {
    position: relative;
    width: 220px;
    height: 160px;
}
.diagram-center {
    position: absolute;
    inset: 40px 70px;
    z-index: 1;
    display: grid;
    place-items: center;
    border: 1px solid #b8d2cb;
    border-radius: 20px;
    background: #fff;
    color: #087f75;
    box-shadow: 0 8px 20px #173c3110;
}
.diagram-line {
    position: absolute;
    inset: 42px 24px;
    border: 1px solid #bed4ce;
    border-radius: 12px;
}
.line-two {
    inset: 65px 0;
    border-radius: 0;
    border-right: 0;
    border-left: 0;
}
.diagram-node {
    position: absolute;
    width: 14px;
    height: 14px;
    border: 1px solid #8eb5ab;
    border-radius: 4px;
    background: #f8fbfa;
}
.node-one {
    top: 35px;
    left: 17px;
}
.node-two {
    top: 35px;
    right: 17px;
}
.node-three {
    bottom: 35px;
    left: 17px;
}
.node-four {
    bottom: 35px;
    right: 17px;
}
</style>
