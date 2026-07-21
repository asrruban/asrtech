<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    EyeOff,
    LifeBuoy,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface DepartmentRow {
    id: number;
    name: string;
    description: string | null;
    email: string | null;
    hidden: boolean;
    sort_order: number;
    admins_count: number;
    fields_count: number;
}

const props = defineProps<{
    departments: DepartmentRow[];
}>();

const move = (department: DepartmentRow, direction: 'up' | 'down') =>
    router.post(
        `/admin/support/departments/${department.id}/move`,
        { direction },
        { preserveScroll: true },
    );

const remove = (department: DepartmentRow) => {
    if (confirm(`Delete support department “${department.name}”?`)) {
        router.delete(`/admin/support/departments/${department.id}`);
    }
};
</script>

<template>
    <Head title="Support ticket departments" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary">Support</p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Support Ticket Departments
                </h1>
                <p class="mt-1 text-muted-foreground">
                    Departments group incoming tickets, control who handles
                    them, and can import tickets from a mailbox.
                </p>
            </div>
            <Button as-child>
                <Link href="/admin/support/departments/create">
                    <Plus class="size-4" /> Add New Department
                </Link>
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="props.departments.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No support departments yet — add your first one.
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground"
                        >
                            <th class="px-4 py-3">Department</th>
                            <th class="hidden px-4 py-3 md:table-cell">
                                Email Address
                            </th>
                            <th class="hidden px-4 py-3 lg:table-cell">
                                Assigned Admins
                            </th>
                            <th class="hidden px-4 py-3 lg:table-cell">
                                Custom Fields
                            </th>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(department, index) in props.departments"
                            :key="department.id"
                            class="border-b last:border-0 hover:bg-muted/40"
                        >
                            <td class="px-4 py-3">
                                <span
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <LifeBuoy
                                        class="size-4 text-muted-foreground"
                                    />
                                    {{ department.name }}
                                    <span
                                        v-if="department.hidden"
                                        class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground"
                                        title="Hidden from clients"
                                    >
                                        <EyeOff class="size-3" /> Hidden
                                    </span>
                                </span>
                                <p
                                    v-if="department.description"
                                    class="mt-0.5 max-w-md truncate text-xs text-muted-foreground"
                                >
                                    {{ department.description }}
                                </p>
                            </td>
                            <td
                                class="hidden px-4 py-3 text-muted-foreground md:table-cell"
                            >
                                {{ department.email || '—' }}
                            </td>
                            <td
                                class="hidden px-4 py-3 text-muted-foreground lg:table-cell"
                            >
                                {{ department.admins_count }}
                            </td>
                            <td
                                class="hidden px-4 py-3 text-muted-foreground lg:table-cell"
                            >
                                {{ department.fields_count }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        :disabled="index === 0"
                                        title="Move up"
                                        @click="move(department, 'up')"
                                    >
                                        <ArrowUp class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        :disabled="
                                            index ===
                                            props.departments.length - 1
                                        "
                                        title="Move down"
                                        @click="move(department, 'down')"
                                    >
                                        <ArrowDown class="size-4" />
                                    </Button>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link
                                            :href="`/admin/support/departments/${department.id}/edit`"
                                        >
                                            <Pencil class="size-4" /> Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="remove(department)"
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
