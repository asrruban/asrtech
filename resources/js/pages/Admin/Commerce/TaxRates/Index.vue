<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Calculator, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import AdminLayout from '@/modules/admin/layouts/AdminLayout.vue';

interface TaxRate {
    id: number;
    name: string;
    country_code?: string | null;
    state?: string | null;
    rate: string;
    priority: number;
    active: boolean;
}
defineProps<{ taxRates: TaxRate[] }>();
const editing = ref<TaxRate | null>(null);
const form = useForm({
    name: '',
    country_code: '',
    state: '',
    rate: '',
    priority: '0',
    active: true,
});

const edit = (tax: TaxRate) => {
    editing.value = tax;
    form.defaults({
        name: tax.name,
        country_code: tax.country_code || '',
        state: tax.state || '',
        rate: tax.rate,
        priority: tax.priority.toString(),
        active: tax.active,
    });
    form.reset();
    form.clearErrors();
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
const cancel = () => {
    editing.value = null;
    form.defaults({
        name: '',
        country_code: '',
        state: '',
        rate: '',
        priority: '0',
        active: true,
    });
    form.reset();
    form.clearErrors();
};
const submit = () => {
    const options = { preserveScroll: true, onSuccess: cancel };

    if (editing.value) {
        form.put(`/admin/tax-rates/${editing.value.id}`, options);
    } else {
        form.post('/admin/tax-rates', options);
    }
};
const remove = (tax: TaxRate) => {
    if (confirm(`Delete tax rate ${tax.name}?`)) {
        router.delete(`/admin/tax-rates/${tax.id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 p-4 md:p-6">
            <header>
                <p
                    class="text-xs font-bold tracking-widest text-blue-600 uppercase"
                >
                    Commerce
                </p>
                <h1 class="mt-1 text-2xl font-bold">Tax rates</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Set one best-match rate by country and optional state. More
                    specific jurisdictions win.
                </p>
            </header>
            <form
                class="rounded-xl border bg-card p-5 shadow-sm"
                @submit.prevent="submit"
            >
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <Calculator class="size-4 text-blue-600" />
                        {{ editing ? 'Edit tax rate' : 'New tax rate' }}
                    </h2>
                    <button
                        v-if="editing"
                        type="button"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground"
                        @click="cancel"
                    >
                        <X class="size-4" /> Cancel
                    </button>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                    <label class="space-y-1 text-sm lg:col-span-2"
                        ><span>Name</span
                        ><input
                            v-model="form.name"
                            required
                            class="h-10 w-full rounded-md border bg-background px-3"
                        /><small class="text-destructive">{{
                            form.errors.name
                        }}</small></label
                    >
                    <label class="space-y-1 text-sm"
                        ><span>Country (ISO 2)</span
                        ><input
                            v-model="form.country_code"
                            maxlength="2"
                            placeholder="Global"
                            class="h-10 w-full rounded-md border bg-background px-3 uppercase"
                        /><small class="text-destructive">{{
                            form.errors.country_code
                        }}</small></label
                    >
                    <label class="space-y-1 text-sm"
                        ><span>State / region</span
                        ><input
                            v-model="form.state"
                            placeholder="Any"
                            class="h-10 w-full rounded-md border bg-background px-3"
                        /><small class="text-destructive">{{
                            form.errors.state
                        }}</small></label
                    >
                    <label class="space-y-1 text-sm"
                        ><span>Rate %</span
                        ><input
                            v-model="form.rate"
                            required
                            type="number"
                            min="0"
                            max="100"
                            step="0.0001"
                            class="h-10 w-full rounded-md border bg-background px-3"
                    /></label>
                    <label class="space-y-1 text-sm"
                        ><span>Priority</span
                        ><input
                            v-model="form.priority"
                            required
                            type="number"
                            min="0"
                            class="h-10 w-full rounded-md border bg-background px-3"
                    /></label>
                </div>
                <div class="mt-5 flex items-center gap-5">
                    <label class="flex items-center gap-2 text-sm"
                        ><input
                            v-model="form.active"
                            type="checkbox"
                            class="size-4"
                        />
                        Active</label
                    ><button
                        :disabled="form.processing"
                        class="inline-flex h-10 items-center gap-2 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        <Plus class="size-4" />
                        {{ editing ? 'Save changes' : 'Create tax rate' }}
                    </button>
                </div>
            </form>
            <section
                class="overflow-hidden rounded-xl border bg-card shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b bg-muted/40 text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="p-4">Name</th>
                                <th class="p-4">Jurisdiction</th>
                                <th class="p-4">Rate</th>
                                <th class="p-4">Priority</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="tax in taxRates" :key="tax.id">
                                <td class="p-4 font-semibold">
                                    {{ tax.name }}
                                    <span
                                        class="ml-2 rounded-full px-2 py-0.5 text-xs"
                                        :class="
                                            tax.active
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-slate-100 text-slate-500'
                                        "
                                        >{{
                                            tax.active ? 'Active' : 'Inactive'
                                        }}</span
                                    >
                                </td>
                                <td class="p-4">
                                    {{ tax.country_code || 'Global'
                                    }}<span v-if="tax.state">
                                        / {{ tax.state }}</span
                                    >
                                </td>
                                <td class="p-4 font-bold">
                                    {{
                                        Number(tax.rate)
                                            .toFixed(4)
                                            .replace(/0+$/, '')
                                            .replace(/\.$/, '')
                                    }}%
                                </td>
                                <td class="p-4">{{ tax.priority }}</td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            class="rounded-md border p-2 hover:bg-muted"
                                            @click="edit(tax)"
                                        >
                                            <Pencil class="size-4" /></button
                                        ><button
                                            class="rounded-md border p-2 text-destructive"
                                            @click="remove(tax)"
                                        >
                                            <Trash2 class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!taxRates.length">
                                <td
                                    colspan="5"
                                    class="p-10 text-center text-muted-foreground"
                                >
                                    No tax rates configured. Checkout will not
                                    add tax.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
