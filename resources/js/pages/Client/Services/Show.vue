<script setup lang="ts">
import ServiceDetail from '@/modules/client/components/ServiceDetail.vue';
defineProps<{ serviceSlug: string; seo: Record<string, unknown> }>();
</script>

<template>
    <ServiceDetail :service-slug="serviceSlug" :seo="seo" />
</template>
