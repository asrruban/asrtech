<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
defineProps(['pages']);
const removePage = (page) => {
    if (confirm(`Delete page “${page.title}”?`)) {
        router.delete(`/admin/pages/${page.id}`);
    }
};
</script>

<template>
    <Head title="Pages" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"
        >
            <div>
                <p class="text-sm font-medium text-primary">Content</p>
                <h1 class="text-3xl font-semibold tracking-tight">Pages</h1>
                <p class="mt-1 text-muted-foreground">
                    Manage public content, publishing, and page-level SEO.
                </p>
            </div>
            <Button as-child>
                <Link href="/admin/pages/create"
                    ><Plus class="size-4" /> Add page</Link
                >
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="pages.length === 0"
                    class="p-12 text-center text-sm text-muted-foreground"
                >
                    No pages yet. Add About, Services, Privacy, or other site
                    content.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="px-5 py-3 font-medium">Page</th>
                                <th class="px-5 py-3 font-medium">Template</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="page in pages" :key="page.id">
                                <td class="px-5 py-4">
                                    <p class="font-medium">{{ page.title }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        /pages/{{ page.slug }} · order
                                        {{ page.sort_order }}
                                    </p>
                                </td>
                                <td
                                    class="px-5 py-4 text-muted-foreground capitalize"
                                >
                                    {{ page.template }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            page.status
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{
                                            page.status ? 'Published' : 'Draft'
                                        }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            v-if="page.status"
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <a
                                                :href="`/pages/${page.slug}`"
                                                target="_blank"
                                                rel="noreferrer"
                                                ><ExternalLink class="size-4"
                                            /></a>
                                        </Button>
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                :href="`/admin/pages/${page.id}/edit`"
                                                ><Pencil class="size-4" />
                                                Edit</Link
                                            >
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="removePage(page)"
                                            ><Trash2 class="size-4"
                                        /></Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
