<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import FormFeedback from '@/modules/projects/components/FormFeedback.vue';
import { date, label } from '@/modules/projects/types';
const props = defineProps<{
    inquiry: {
        id: number;
        name: string;
        email: string;
        service: string;
        message: string;
        status: string;
        user_id: number | null;
        assigned_admin_id: number | null;
        internal_notes: string | null;
        follow_up_at: string | null;
        created_at: string;
        quote: { id: number; quote_number: string; status: string } | null;
        project: { id: number; title: string } | null;
    };
    clients: { id: number; name: string; email: string }[];
    admins: { id: number; name: string }[];
    statuses: string[];
    serviceNames: Record<string, string>;
    canManageBilling: boolean;
    products: { id: number; name: string; price: number | string }[];
    quotes: { id: number; quote_number: string; status: string }[];
    currency: string;
}>();
const form = useForm({
    status: props.inquiry.status,
    user_id: props.inquiry.user_id ?? '',
    assigned_admin_id: props.inquiry.assigned_admin_id ?? '',
    internal_notes: props.inquiry.internal_notes ?? '',
    follow_up_at: props.inquiry.follow_up_at?.slice(0, 10) ?? '',
});
watch(
    () => props.inquiry.status,
    (status) => {
        form.status = status;
    },
);
const quote = useForm({
    quote_id: '',
    product_id: '',
    unit_price: '',
    quantity: 1,
    billing_cycle: 'one_time',
    tax_rate: '',
    valid_until: '',
});
const project = useForm({
    user_id: props.inquiry.user_id ?? '',
    project_inquiry_id: props.inquiry.id,
    title: '',
    description: '',
    target_date: '',
});
function productChanged() {
    quote.unit_price = String(
        props.products.find((p) => p.id === Number(quote.product_id))?.price ??
            '',
    );
}
</script>
<template>
    <Head :title="`Inquiry #${inquiry.id}`" />
    <div class="inquiry-detail mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <Link href="/admin/inquiries" class="text-sm font-semibold text-primary"
            >← All inquiries</Link
        >
        <header class="my-7">
            <p
                class="text-xs font-semibold tracking-widest text-primary uppercase"
            >
                {{ serviceNames[inquiry.service] ?? inquiry.service }}
            </p>
            <h1 class="mt-3 text-3xl font-semibold">
                Inquiry from {{ inquiry.name }}
            </h1>
            <p class="mt-3 text-sm text-muted-foreground">
                #{{ inquiry.id }} · {{ inquiry.email }} · Received
                {{ date(inquiry.created_at) }}
            </p>
        </header>
        <div
            class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]"
        >
            <div class="min-w-0 space-y-6">
                <section class="rounded-xl border bg-card p-6">
                    <h2 class="text-xl font-semibold">Customer message</h2>
                    <p
                        class="mt-4 text-sm leading-7 break-words whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ inquiry.message }}
                    </p>
                </section>
                <section class="rounded-xl border bg-card p-6">
                    <h2 class="text-xl font-semibold">Follow-up & ownership</h2>
                    <form
                        class="mt-5 grid gap-4"
                        @submit.prevent="
                            form.patch(`/admin/inquiries/${inquiry.id}`, {
                                preserveScroll: true,
                            })
                        "
                    >
                        <label
                            >Status<select v-model="form.status">
                                <option
                                    v-for="status in statuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ label(status) }}
                                </option>
                            </select></label
                        ><label
                            >Assigned administrator<select
                                v-model="form.assigned_admin_id"
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="admin in admins"
                                    :key="admin.id"
                                    :value="admin.id"
                                >
                                    {{ admin.name }}
                                </option>
                            </select></label
                        ><label
                            >Follow-up date<input
                                v-model="form.follow_up_at"
                                type="date" /></label
                        ><label
                            >Link an existing client<select
                                v-model="form.user_id"
                                :disabled="!!inquiry.quote || !!inquiry.project"
                            >
                                <option value="">Not linked</option>
                                <option
                                    v-for="client in clients"
                                    :key="client.id"
                                    :value="client.id"
                                >
                                    {{ client.name }} — {{ client.email }}
                                </option>
                            </select></label
                        >
                        <p class="text-xs leading-6 text-muted-foreground">
                            Confirm the customer's identity before linking an
                            account. Matching an inquiry email alone does not
                            confirm account ownership. Save the linked client
                            before creating a quote or project.
                        </p>
                        <label
                            >Internal notes<textarea
                                v-model="form.internal_notes"
                                rows="6"
                                maxlength="10000"
                            />
                        </label>
                        <p class="text-xs text-muted-foreground">
                            Notes are visible to support administrators only.
                        </p>
                        <FormFeedback
                            :errors="form.errors"
                            :saved="form.recentlySuccessful"
                        /><button class="action" :disabled="form.processing">
                            {{ form.processing ? 'Saving…' : 'Save follow-up' }}
                        </button>
                    </form>
                </section>
            </div>
            <div class="min-w-0 space-y-6">
                <section class="rounded-xl border bg-card p-6">
                    <h2 class="text-xl font-semibold">Quote</h2>
                    <div v-if="inquiry.quote" class="mt-4">
                        <p class="font-semibold">
                            {{ inquiry.quote.quote_number }}
                        </p>
                        <p
                            class="mt-2 text-sm text-muted-foreground capitalize"
                        >
                            {{ label(inquiry.quote.status) }}
                        </p>
                        <Link
                            v-if="canManageBilling"
                            href="/admin/quotes"
                            class="mt-4 inline-block text-sm font-semibold text-primary"
                            >Manage quotes →</Link
                        >
                    </div>
                    <p
                        v-else-if="!canManageBilling"
                        class="mt-4 text-sm leading-7 text-muted-foreground"
                    >
                        A billing-authorized administrator can create or link a
                        quote after you save the client account.
                    </p>
                    <p
                        v-else-if="!inquiry.user_id"
                        class="mt-4 text-sm text-muted-foreground"
                    >
                        Link and save a client first.
                    </p>
                    <form
                        v-else
                        class="mt-5 grid gap-4"
                        @submit.prevent="
                            quote.post(`/admin/inquiries/${inquiry.id}/quote`, {
                                preserveScroll: true,
                            })
                        "
                    >
                        <label
                            >Use an existing quote<select
                                v-model="quote.quote_id"
                            >
                                <option value="">Create a draft quote</option>
                                <option
                                    v-for="item in quotes"
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.quote_number }} · {{ item.status }}
                                </option>
                            </select></label
                        ><template v-if="!quote.quote_id"
                            ><label
                                >Catalog product<select
                                    v-model="quote.product_id"
                                    required
                                    @change="productChanged"
                                >
                                    <option value="">Select a product</option>
                                    <option
                                        v-for="product in products"
                                        :key="product.id"
                                        :value="product.id"
                                    >
                                        {{ product.name }}
                                    </option>
                                </select></label
                            >
                            <p
                                v-if="!products.length"
                                class="text-sm text-muted-foreground"
                            >
                                Add the actual service or product to the catalog
                                before creating its quote.
                            </p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label
                                    >Unit price ({{ currency }})<input
                                        v-model="quote.unit_price"
                                        type="number"
                                        min="0"
                                        max="999999999"
                                        step="0.01"
                                        required /></label
                                ><label
                                    >Quantity<input
                                        v-model="quote.quantity"
                                        type="number"
                                        min="1"
                                        max="1000"
                                        required
                                /></label>
                            </div>
                            <label
                                >Billing cycle<select
                                    v-model="quote.billing_cycle"
                                >
                                    <option value="one_time">One time</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select></label
                            >
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label
                                    >Tax % (optional)<input
                                        v-model="quote.tax_rate"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01" /></label
                                ><label
                                    >Valid until (optional)<input
                                        v-model="quote.valid_until"
                                        type="date"
                                /></label>
                            </div>
                            <p class="text-xs leading-6 text-muted-foreground">
                                Confirm the price and tax before saving. This
                                creates a draft only. Review and send from
                                Quotes using the existing billing workflow.
                            </p></template
                        ><FormFeedback :errors="quote.errors" /><button
                            class="action"
                            :disabled="quote.processing"
                        >
                            {{
                                quote.processing
                                    ? 'Saving…'
                                    : quote.quote_id
                                      ? 'Link quote'
                                      : 'Create draft quote'
                            }}
                        </button>
                    </form>
                </section>
                <section class="rounded-xl border bg-card p-6">
                    <h2 class="text-xl font-semibold">Project workspace</h2>
                    <Link
                        v-if="inquiry.project"
                        :href="`/admin/projects/${inquiry.project.id}`"
                        class="mt-4 block text-sm font-semibold text-primary"
                        >{{ inquiry.project.title }} →</Link
                    >
                    <p
                        v-else-if="!inquiry.user_id"
                        class="mt-4 text-sm text-muted-foreground"
                    >
                        Link and save a client to create their workspace.
                    </p>
                    <form
                        v-else
                        class="mt-5 grid gap-4"
                        @submit.prevent="
                            project
                                .transform((data) => ({
                                    ...data,
                                    user_id: inquiry.user_id,
                                }))
                                .post('/admin/projects')
                        "
                    >
                        <p class="text-sm leading-6 text-muted-foreground">
                            The client will immediately see this workspace.
                            Creating it marks the inquiry won and does not issue
                            an invoice or charge.
                        </p>
                        <label
                            >Project title<input
                                v-model="project.title"
                                required
                                maxlength="180" /></label
                        ><label
                            >Agreed scope<textarea
                                v-model="project.description"
                                rows="5"
                                required
                                maxlength="10000"
                            /></label
                        ><label
                            >Target date (optional)<input
                                v-model="project.target_date"
                                type="date" /></label
                        ><FormFeedback :errors="project.errors" /><button
                            class="action"
                            :disabled="project.processing"
                        >
                            {{
                                project.processing
                                    ? 'Creating…'
                                    : 'Create client workspace'
                            }}
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</template>
<style scoped>
label {
    display: grid;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
}
input,
select,
textarea {
    width: 100%;
    min-width: 0;
    padding: 0.75rem;
    border: 1px solid var(--border);
    border-radius: 0.6rem;
    background: var(--background);
    font-weight: 400;
}
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}
.action {
    justify-self: start;
    background: var(--primary);
    color: var(--primary-foreground);
    padding: 0.75rem 1rem;
    border-radius: 0.6rem;
    font-size: 0.875rem;
    font-weight: 600;
}
.action:disabled {
    opacity: 0.6;
    cursor: wait;
}
</style>
