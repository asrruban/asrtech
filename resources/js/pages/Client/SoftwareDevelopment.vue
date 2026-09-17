<script setup lang="ts">
import ServiceDetail from '@/modules/client/components/ServiceDetail.vue';
defineProps<{ seo: Record<string, unknown> }>();
</script>

<template>
    <ServiceDetail service-slug="web-development" :seo="seo" />
</template>
