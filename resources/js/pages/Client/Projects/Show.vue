<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AccountNav from '@/modules/client/components/AccountNav.vue';
import ProjectWorkspace from '@/modules/projects/components/ProjectWorkspace.vue';
import type { Project } from '@/modules/projects/types';
defineProps<{ project: Project }>();
</script>
<template>
    <Head :title="project.title" />
    <div class="site-container"><AccountNav /></div>
    <ProjectWorkspace :project="project" />
</template>
