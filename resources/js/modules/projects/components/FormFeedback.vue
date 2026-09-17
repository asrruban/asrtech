<script setup lang="ts">
defineProps<{ errors: object; saved?: boolean }>();
</script>
<template>
    <div
        v-if="Object.keys(errors).length"
        role="alert"
        class="my-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-400/30 dark:bg-red-950/40 dark:text-red-200"
    >
        <p v-for="(error, key) in errors" :key="key">{{ error }}</p>
    </div>
    <p
        v-if="saved"
        role="status"
        class="my-3 text-sm font-medium text-emerald-700 dark:text-emerald-300"
    >
        Saved successfully.
    </p>
</template>
