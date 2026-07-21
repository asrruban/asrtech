<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Lock, Mail, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface TemplateRow {
    id: number;
    name: string;
    slug: string;
    category: string;
    subject: string;
    enabled: boolean;
    is_system: boolean;
}

const props = defineProps<{
    templates: TemplateRow[];
    categories: Record<string, string>;
}>();

const page = usePage();
const deleteError = computed(() => page.props.errors.template);

const createForm = useForm({
    name: '',
    category: 'general',
    subject: '',
    body: '<p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#5b6472;">Hi {{client_name}},</p>\n<p style="margin:0;font-size:15px;line-height:1.7;color:#5b6472;">Write your message here…</p>',
    enabled: true,
});

const createTemplate = () =>
    createForm.post('/admin/settings/emailtemplates', {
        onSuccess: () => createForm.reset(),
    });

const removeTemplate = (template: TemplateRow) => {
    if (confirm(`Delete email template “${template.name}”?`)) {
        router.delete(`/admin/settings/emailtemplates/${template.id}`);
    }
};
</script>

<template>
    <Head title="Email templates" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Configuration</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                Email templates
            </h1>
            <p class="mt-1 text-muted-foreground">
                Manage the emails sent to clients. System templates are used
                by the application and can be edited or disabled; custom
                templates can also be deleted.
            </p>
        </div>

        <Card>
            <CardHeader><CardTitle>Add template</CardTitle></CardHeader>
            <CardContent>
                <form
                    class="grid gap-4 lg:grid-cols-[1fr_auto_1.5fr_auto]"
                    @submit.prevent="createTemplate"
                >
                    <div class="space-y-2">
                        <Label for="template-name">Name</Label>
                        <Input
                            id="template-name"
                            v-model="createForm.name"
                            required
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="template-category">Category</Label>
                        <select
                            id="template-category"
                            v-model="createForm.category"
                            class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option
                                v-for="(label, value) in props.categories"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.category" />
                    </div>
                    <div class="space-y-2">
                        <Label for="template-subject">Subject</Label>
                        <Input
                            id="template-subject"
                            v-model="createForm.subject"
                            required
                        />
                        <InputError :message="createForm.errors.subject" />
                    </div>
                    <div class="flex items-end pb-0.5">
                        <Button type="submit" :disabled="createForm.processing">
                            <Plus class="size-4" /> Add
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <InputError :message="deleteError" />

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="props.templates.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No email templates yet.
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th class="px-4 py-3">Template</th>
                            <th class="hidden px-4 py-3 md:table-cell">
                                Subject
                            </th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="template in props.templates"
                            :key="template.id"
                            class="border-b last:border-0 hover:bg-muted/40"
                        >
                            <td class="px-4 py-3">
                                <span class="flex items-center gap-2 font-medium">
                                    <Mail class="size-4 text-muted-foreground" />
                                    {{ template.name }}
                                    <span
                                        v-if="template.is_system"
                                        class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground"
                                        title="Used by the application — cannot be deleted"
                                    >
                                        <Lock class="size-3" /> System
                                    </span>
                                </span>
                            </td>
                            <td class="hidden max-w-64 truncate px-4 py-3 text-muted-foreground md:table-cell">
                                {{ template.subject }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ props.categories[template.category] ?? template.category }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="template.enabled"
                                    class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                >
                                    Enabled
                                </span>
                                <span
                                    v-else
                                    class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                                >
                                    Disabled
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        as-child
                                    >
                                        <Link
                                            :href="`/admin/settings/emailtemplates/${template.id}`"
                                        >
                                            <Pencil class="size-4" /> Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="!template.is_system"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="removeTemplate(template)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
