<script setup lang="ts">
import ProductCard from '@/modules/client/components/ProductCard.vue';
defineProps(['product']);
</script>

<template>
    <ProductCard :product="product" :heading-level="3" />
</template>
