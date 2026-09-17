<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProjectWorkspace from '@/modules/projects/components/ProjectWorkspace.vue';
import type { Project } from '@/modules/projects/types';
defineProps<{ project: Project }>();
</script>
<template>
    <Head :title="project.title" /><ProjectWorkspace :project="project" admin />
</template>
