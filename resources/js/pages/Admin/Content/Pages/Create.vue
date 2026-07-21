<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageForm from '@/modules/admin/pages/components/PageForm.vue';
</script>

<template>
    <Head title="Create page" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Content</p>
            <h1 class="text-3xl font-semibold tracking-tight">Create page</h1>
        </div>
        <PageForm />
    </div>
</template>
