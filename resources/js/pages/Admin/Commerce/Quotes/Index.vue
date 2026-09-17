<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FileSignature, Plus, Send, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type QuoteItem = {
    product_id: number;
    product_name: string;
    quantity: number;
    unit_price: number;
    billing_cycle: string;
};

type Quote = {
    id: number;
    quote_number: string;
    client: { id: number; name: string; email: string } | null;
    status: string;
    currency: string;
    subtotal: number;
    tax_amount: number;
    total: number;
    valid_until: string | null;
    admin_note: string | null;
    items: QuoteItem[];
    order_id: number | null;
    created_at: string | null;
};

const props = defineProps<{
    quotes: {
        data: Quote[];
        links: { url: string | null; label: string; active: boolean }[];
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    statuses: string[];
    products: { id: number; name: string; price: number }[];
    clients: { id: number; name: string; email: string }[];
    currency: string;
}>();

const editorOpen = ref(false);
const editing = ref<Quote | null>(null);

const emptyItem = (): QuoteItem => ({
    product_id: props.products[0]?.id ?? 0,
    product_name: '',
    quantity: 1,
    unit_price: props.products[0]?.price ?? 0,
    billing_cycle: 'one_time',
});

const form = useForm({
    user_id: null as number | null,
    valid_until: '',
    admin_note: '',
    tax_rate: 0,
    items: [emptyItem()] as QuoteItem[],
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.items = [emptyItem()];
    editorOpen.value = true;
};

const openEdit = (quote: Quote) => {
    editing.value = quote;
    form.user_id = quote.client?.id ?? null;
    form.valid_until = quote.valid_until ?? '';
    form.admin_note = quote.admin_note ?? '';
    form.tax_rate =
        quote.subtotal > 0
            ? Math.round((quote.tax_amount / quote.subtotal) * 10000) / 100
            : 0;
    form.items = quote.items.map((item) => ({ ...item }));
    editorOpen.value = true;
};

const addItem = () => form.items.push(emptyItem());
const removeItem = (index: number) => form.items.splice(index, 1);

const onProductChange = (item: QuoteItem) => {
    const product = props.products.find((p) => p.id === item.product_id);

    if (product) {
        item.unit_price = product.price;
    }
};

const submit = () => {
    if (editing.value) {
        form.put(`/admin/quotes/${editing.value.id}`, {
            onSuccess: () => (editorOpen.value = false),
        });
    } else {
        form.post('/admin/quotes', {
            onSuccess: () => (editorOpen.value = false),
        });
    }
};

const send = (quote: Quote) =>
    useForm({}).post(`/admin/quotes/${quote.id}/send`);

const remove = (quote: Quote) => {
    if (!confirm(`Delete quote ${quote.quote_number}?`)) {
        return;
    }

    useForm({}).delete(`/admin/quotes/${quote.id}`);
};

const money = (currency: string, amount: number) =>
    `${currency} ${amount.toFixed(2)}`;

const statusClass = (status: string) =>
    ({
        draft: 'bg-muted text-muted-foreground',
        sent: 'bg-blue-500/10 text-blue-500',
        accepted: 'bg-emerald-500/10 text-emerald-500',
        converted: 'bg-emerald-500/10 text-emerald-600',
        declined: 'bg-red-500/10 text-red-500',
        expired: 'bg-amber-500/10 text-amber-500',
    })[status] ?? 'bg-muted text-muted-foreground';

const paginationLabel = (value: string) =>
    value.replace('&laquo;', '«').replace('&raquo;', '»').trim();

const inputClass = 'h-10 w-full rounded-md border bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="Quotes" />

    <div class="space-y-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Quotes</h1>
                <p class="text-sm text-muted-foreground">
                    Proforma quotes clients can review, accept, and pay.
                </p>
            </div>
            <Dialog v-model:open="editorOpen">
                <DialogTrigger as-child>
                    <Button @click="openCreate"
                        ><Plus class="size-4" /> New quote</Button
                    >
                </DialogTrigger>
                <DialogContent class="max-h-[90vh] max-w-3xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{{
                            editing
                                ? `Edit ${editing.quote_number}`
                                : 'New quote'
                        }}</DialogTitle>
                        <DialogDescription>
                            Pick a client and add line items. Totals are
                            calculated automatically.
                        </DialogDescription>
                    </DialogHeader>
                    <form class="space-y-5" @submit.prevent="submit">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="q-client">Client</Label>
                                <select
                                    id="q-client"
                                    v-model="form.user_id"
                                    :class="inputClass"
                                    required
                                >
                                    <option :value="null" disabled>
                                        Select a client
                                    </option>
                                    <option
                                        v-for="client in props.clients"
                                        :key="client.id"
                                        :value="client.id"
                                    >
                                        {{ client.name }} ({{ client.email }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.user_id" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="q-valid">Valid until</Label>
                                <Input
                                    id="q-valid"
                                    v-model="form.valid_until"
                                    type="date"
                                />
                                <InputError
                                    :message="form.errors.valid_until"
                                />
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <Label>Line items</Label>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="addItem"
                                >
                                    <Plus class="size-4" /> Add item
                                </Button>
                            </div>
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="grid grid-cols-[1fr_90px_110px_120px_36px] items-end gap-2 rounded-lg border p-3"
                            >
                                <div class="space-y-1">
                                    <Label class="text-xs">Product</Label>
                                    <select
                                        v-model="item.product_id"
                                        :class="inputClass"
                                        @change="onProductChange(item)"
                                    >
                                        <option
                                            v-for="product in props.products"
                                            :key="product.id"
                                            :value="product.id"
                                        >
                                            {{ product.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs">Qty</Label>
                                    <Input
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="1"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs">Unit price</Label>
                                    <Input
                                        v-model.number="item.unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs">Billing</Label>
                                    <select
                                        v-model="item.billing_cycle"
                                        :class="inputClass"
                                    >
                                        <option value="one_time">
                                            One-time
                                        </option>
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    :disabled="form.items.length <= 1"
                                    @click="removeItem(index)"
                                >
                                    <X class="size-4" />
                                </Button>
                            </div>
                            <InputError :message="form.errors.items" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="q-tax">Tax rate %</Label>
                                <Input
                                    id="q-tax"
                                    v-model.number="form.tax_rate"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="q-note"
                                    >Note (visible to client)</Label
                                >
                                <Input id="q-note" v-model="form.admin_note" />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="submit" :disabled="form.processing">
                                {{ editing ? 'Save changes' : 'Create quote' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="props.quotes.data.length === 0"
                    class="flex flex-col items-center gap-3 p-10 text-center text-sm text-muted-foreground"
                >
                    <FileSignature class="size-8 text-muted-foreground/50" />
                    No quotes yet.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3.5">Quote</th>
                                <th class="px-5 py-3.5">Client</th>
                                <th class="px-5 py-3.5">Items</th>
                                <th class="px-5 py-3.5">Total</th>
                                <th class="px-5 py-3.5">Valid until</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="quote in props.quotes.data"
                                :key="quote.id"
                                class="border-b last:border-0 hover:bg-muted/40"
                            >
                                <td
                                    class="px-5 py-4 font-mono text-xs font-semibold"
                                >
                                    {{ quote.quote_number }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium">
                                        {{ quote.client?.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ quote.client?.email }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    {{ quote.items.length }}
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    {{ money(quote.currency, quote.total) }}
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ quote.valid_until ?? '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                        :class="statusClass(quote.status)"
                                    >
                                        {{ quote.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            v-if="
                                                quote.status === 'draft' ||
                                                quote.status === 'sent'
                                            "
                                            size="sm"
                                            variant="outline"
                                            @click="openEdit(quote)"
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            v-if="
                                                quote.status === 'draft' ||
                                                quote.status === 'sent'
                                            "
                                            size="sm"
                                            @click="send(quote)"
                                        >
                                            <Send class="size-4" /> Send
                                        </Button>
                                        <Button
                                            v-if="quote.status !== 'converted'"
                                            size="sm"
                                            variant="destructive"
                                            @click="remove(quote)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div
            v-if="props.quotes.last_page > 1"
            class="flex items-center justify-between gap-4 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ props.quotes.from }}–{{ props.quotes.to }} of
                {{ props.quotes.total }}
            </p>
            <div class="flex gap-2">
                <template v-for="link in props.quotes.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        as-child
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                    >
                        <Link :href="link.url">{{
                            paginationLabel(link.label)
                        }}</Link>
                    </Button>
                    <Button v-else size="sm" variant="outline" disabled>
                        {{ paginationLabel(link.label) }}
                    </Button>
                </template>
            </div>
        </div>
    </div>
</template>
