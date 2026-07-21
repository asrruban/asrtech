<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Tag, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import AdminLayout from '@/modules/admin/layouts/AdminLayout.vue';

interface Product {
    id: number;
    name: string;
}
interface Promotion {
    id: number;
    code: string;
    name: string;
    discount_type: string;
    value: string;
    currency?: string | null;
    minimum_subtotal?: string | null;
    maximum_discount?: string | null;
    usage_limit?: number | null;
    per_customer_limit?: number | null;
    scope: string;
    active: boolean;
    starts_at?: string | null;
    ends_at?: string | null;
    products: Product[];
    redemption_count: number;
}

defineProps<{
    promotions: Promotion[];
    products: Product[];
    discountTypes: string[];
    scopes: string[];
}>();

const editing = ref<Promotion | null>(null);
const form = useForm({
    code: '',
    name: '',
    discount_type: 'percentage',
    value: '',
    currency: 'USD',
    minimum_subtotal: '',
    maximum_discount: '',
    usage_limit: '',
    per_customer_limit: '1',
    scope: 'all',
    active: true,
    starts_at: '',
    ends_at: '',
    product_ids: [] as number[],
});

const dateValue = (value?: string | null) => (value ? value.slice(0, 16) : '');
const label = (value: string) =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

const edit = (promotion: Promotion) => {
    editing.value = promotion;
    form.clearErrors();
    form.defaults({
        code: promotion.code,
        name: promotion.name,
        discount_type: promotion.discount_type,
        value: promotion.value,
        currency: promotion.currency || 'USD',
        minimum_subtotal: promotion.minimum_subtotal || '',
        maximum_discount: promotion.maximum_discount || '',
        usage_limit: promotion.usage_limit?.toString() || '',
        per_customer_limit: promotion.per_customer_limit?.toString() || '',
        scope: promotion.scope,
        active: promotion.active,
        starts_at: dateValue(promotion.starts_at),
        ends_at: dateValue(promotion.ends_at),
        product_ids: promotion.products.map((product) => product.id),
    });
    form.reset();
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancel = () => {
    editing.value = null;
    form.defaults({
        code: '',
        name: '',
        discount_type: 'percentage',
        value: '',
        currency: 'USD',
        minimum_subtotal: '',
        maximum_discount: '',
        usage_limit: '',
        per_customer_limit: '1',
        scope: 'all',
        active: true,
        starts_at: '',
        ends_at: '',
        product_ids: [],
    });
    form.reset();
    form.clearErrors();
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: cancel };

    if (editing.value) {
        form.put(`/admin/promotions/${editing.value.id}`, options);
    } else {
        form.post('/admin/promotions', options);
    }
};

const remove = (promotion: Promotion) => {
    if (confirm(`Delete promotion ${promotion.code}?`)) {
        router.delete(`/admin/promotions/${promotion.id}`, {
            preserveScroll: true,
        });
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
                <h1 class="mt-1 text-2xl font-bold">Promotions</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Create controlled discounts with product, billing, date, and
                    usage restrictions.
                </p>
            </header>

            <form
                class="rounded-xl border bg-card p-5 shadow-sm"
                @submit.prevent="submit"
            >
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <Tag class="size-4 text-blue-600" />
                        {{ editing ? 'Edit promotion' : 'New promotion' }}
                    </h2>
                    <button
                        v-if="editing"
                        type="button"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                        @click="cancel"
                    >
                        <X class="size-4" /> Cancel
                    </button>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <label class="space-y-1 text-sm"
                        ><span>Code</span
                        ><input
                            v-model="form.code"
                            required
                            class="h-10 w-full rounded-md border bg-background px-3 font-mono uppercase"
                        /><small class="text-destructive">{{
                            form.errors.code
                        }}</small></label
                    >
                    <label class="space-y-1 text-sm md:col-span-2"
                        ><span>Internal name</span
                        ><input
                            v-model="form.name"
                            required
                            class="h-10 w-full rounded-md border bg-background px-3"
                        /><small class="text-destructive">{{
                            form.errors.name
                        }}</small></label
                    >
                    <label class="flex items-end gap-2 pb-2 text-sm"
                        ><input
                            v-model="form.active"
                            type="checkbox"
                            class="size-4"
                        />
                        Active</label
                    >
                    <label class="space-y-1 text-sm"
                        ><span>Discount type</span
                        ><select
                            v-model="form.discount_type"
                            class="h-10 w-full rounded-md border bg-background px-3"
                        >
                            <option
                                v-for="item in discountTypes"
                                :key="item"
                                :value="item"
                            >
                                {{ label(item) }}
                            </option>
                        </select></label
                    >
                    <label class="space-y-1 text-sm"
                        ><span>Value</span
                        ><input
                            v-model="form.value"
                            required
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="h-10 w-full rounded-md border bg-background px-3"
                        /><small class="text-destructive">{{
                            form.errors.value
                        }}</small></label
                    >
                    <label
                        v-if="form.discount_type === 'fixed'"
                        class="space-y-1 text-sm"
                        ><span>Currency</span
                        ><input
                            v-model="form.currency"
                            maxlength="3"
                            class="h-10 w-full rounded-md border bg-background px-3 uppercase"
                    /></label>
                    <label class="space-y-1 text-sm"
                        ><span>Applies to</span
                        ><select
                            v-model="form.scope"
                            class="h-10 w-full rounded-md border bg-background px-3"
                        >
                            <option
                                v-for="item in scopes"
                                :key="item"
                                :value="item"
                            >
                                {{ label(item) }}
                            </option>
                        </select></label
                    >
                    <label class="space-y-1 text-sm"
                        ><span>Minimum subtotal</span
                        ><input
                            v-model="form.minimum_subtotal"
                            type="number"
                            min="0"
                            step="0.01"
                            class="h-10 w-full rounded-md border bg-background px-3"
                    /></label>
                    <label class="space-y-1 text-sm"
                        ><span>Maximum discount</span
                        ><input
                            v-model="form.maximum_discount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="h-10 w-full rounded-md border bg-background px-3"
                    /></label>
                    <label class="space-y-1 text-sm"
                        ><span>Total usage limit</span
                        ><input
                            v-model="form.usage_limit"
                            type="number"
                            min="1"
                            class="h-10 w-full rounded-md border bg-background px-3"
                    /></label>
                    <label class="space-y-1 text-sm"
                        ><span>Per-customer limit</span
                        ><input
                            v-model="form.per_customer_limit"
                            type="number"
                            min="1"
                            class="h-10 w-full rounded-md border bg-background px-3"
                    /></label>
                    <label class="space-y-1 text-sm"
                        ><span>Starts at</span
                        ><input
                            v-model="form.starts_at"
                            type="datetime-local"
                            class="h-10 w-full rounded-md border bg-background px-3"
                    /></label>
                    <label class="space-y-1 text-sm"
                        ><span>Ends at</span
                        ><input
                            v-model="form.ends_at"
                            type="datetime-local"
                            class="h-10 w-full rounded-md border bg-background px-3"
                        /><small class="text-destructive">{{
                            form.errors.ends_at
                        }}</small></label
                    >
                </div>
                <fieldset class="mt-5 rounded-lg border p-4">
                    <legend class="px-2 text-sm font-medium">
                        Products
                        <span class="text-muted-foreground"
                            >(none selected = all products)</span
                        >
                    </legend>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="product in products"
                            :key="product.id"
                            class="flex items-center gap-2 text-sm"
                            ><input
                                v-model="form.product_ids"
                                type="checkbox"
                                :value="product.id"
                                class="size-4"
                            />
                            {{ product.name }}</label
                        >
                    </div>
                </fieldset>
                <button
                    :disabled="form.processing"
                    class="mt-5 inline-flex h-10 items-center gap-2 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    <Plus class="size-4" />
                    {{ editing ? 'Save changes' : 'Create promotion' }}
                </button>
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
                                <th class="p-4">Promotion</th>
                                <th class="p-4">Discount</th>
                                <th class="p-4">Eligibility</th>
                                <th class="p-4">Usage</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="promotion in promotions"
                                :key="promotion.id"
                            >
                                <td class="p-4">
                                    <div class="font-mono font-bold">
                                        {{ promotion.code }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ promotion.name }}
                                    </div>
                                    <span
                                        class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs"
                                        :class="
                                            promotion.active
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-slate-100 text-slate-500'
                                        "
                                        >{{
                                            promotion.active
                                                ? 'Active'
                                                : 'Inactive'
                                        }}</span
                                    >
                                </td>
                                <td class="p-4 font-semibold">
                                    {{
                                        promotion.discount_type === 'percentage'
                                            ? `${promotion.value}%`
                                            : `${promotion.currency} ${promotion.value}`
                                    }}
                                </td>
                                <td class="p-4">
                                    <div>{{ label(promotion.scope) }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            promotion.products.length
                                                ? `${promotion.products.length} selected product(s)`
                                                : 'All products'
                                        }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    {{ promotion.redemption_count
                                    }}<span
                                        v-if="promotion.usage_limit"
                                        class="text-muted-foreground"
                                    >
                                        / {{ promotion.usage_limit }}</span
                                    >
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            class="rounded-md border p-2 hover:bg-muted"
                                            title="Edit"
                                            @click="edit(promotion)"
                                        >
                                            <Pencil class="size-4" /></button
                                        ><button
                                            class="rounded-md border p-2 text-destructive hover:bg-destructive/10"
                                            title="Delete"
                                            @click="remove(promotion)"
                                        >
                                            <Trash2 class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!promotions.length">
                                <td
                                    colspan="5"
                                    class="p-10 text-center text-muted-foreground"
                                >
                                    No promotion codes yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
