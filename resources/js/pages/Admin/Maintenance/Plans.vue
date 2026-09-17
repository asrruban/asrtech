<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { MaintenancePlan } from '@/types/maintenance';
defineProps<{
    plans: MaintenancePlan[];
    prices: { id: number; label: string }[];
}>();
const editing = ref<number | null>(null);
const form = useForm({
    name: '',
    slug: '',
    platform: 'wordpress',
    summary: '',
    scope: '',
    exclusions: '',
    support_arrangements: '',
    product_price_id: null as number | null,
    published: false,
});
const edit = (plan?: MaintenancePlan) => {
    editing.value = plan?.id ?? null;
    form.clearErrors();
    form.name = plan?.name ?? '';
    form.slug = plan?.slug ?? '';
    form.platform = plan?.platform ?? 'wordpress';
    form.summary = plan?.summary ?? '';
    form.scope = plan?.scope ?? '';
    form.exclusions = plan?.exclusions ?? '';
    form.support_arrangements = plan?.support_arrangements ?? '';
    form.product_price_id = plan?.product_price_id ?? null;
    form.published = plan?.published ?? false;
    document.getElementById('plan-name')?.focus();
};
const save = () =>
    editing.value
        ? form.patch(`/admin/maintenance/plans/${editing.value}`, {
              preserveScroll: true,
          })
        : form.post('/admin/maintenance/plans', {
              preserveScroll: true,
              onSuccess: () => edit(),
          });
</script>
<template>
    <Head title="Maintenance plans" />
    <div class="space-y-7 p-4 md:p-8">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <p
                    class="text-xs font-semibold tracking-widest text-primary uppercase"
                >
                    Service catalog
                </p>
                <h1 class="mt-2 text-3xl font-semibold">Maintenance plans</h1>
                <p class="mt-3 max-w-2xl text-sm text-muted-foreground">
                    Publish only agreed scope, support arrangements and prices.
                    Plans begin as drafts. Existing requests retain their
                    original scope.
                </p>
            </div>
            <Link href="/maintenance" class="button-secondary self-start"
                >View public page</Link
            >
        </div>
        <div class="grid items-start gap-8 xl:grid-cols-[1fr_1.2fr]">
            <section class="space-y-4">
                <h2 class="text-xl font-semibold">Current plans</h2>
                <p
                    v-if="!plans.length"
                    class="rounded-xl border bg-card p-6 text-sm text-muted-foreground"
                >
                    No plans yet. Add real service details using the form.
                </p>
                <article
                    v-for="plan in plans"
                    :key="plan.id"
                    class="rounded-xl border bg-card p-5"
                >
                    <div class="flex flex-wrap justify-between gap-3">
                        <h3 class="font-semibold">{{ plan.name }}</h3>
                        <span class="text-xs font-semibold">{{
                            plan.published ? 'Published' : 'Draft'
                        }}</span>
                    </div>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ plan.summary }}
                    </p>
                    <p class="mt-3 text-xs">
                        {{ plan.platform }} ·
                        {{
                            plan.billing
                                ? `${plan.billing.currency} ${plan.billing.amount} / ${plan.billing.cycle}`
                                : 'Custom quote'
                        }}
                    </p>
                    <p
                        v-if="plan.published && !plan.available"
                        class="mt-2 text-sm text-amber-700 dark:text-amber-300"
                    >
                        Linked billing is unavailable. New requests are blocked.
                    </p>
                    <button class="button-secondary mt-4" @click="edit(plan)">
                        Edit plan
                    </button>
                </article>
            </section>
            <form
                class="space-y-5 rounded-2xl border bg-card p-6"
                @submit.prevent="save"
            >
                <div class="flex justify-between gap-3">
                    <h2 class="text-xl font-semibold">
                        {{ editing ? 'Edit plan' : 'Create a plan' }}
                    </h2>
                    <button
                        v-if="editing"
                        type="button"
                        class="text-sm underline"
                        @click="edit()"
                    >
                        New plan
                    </button>
                </div>
                <div
                    v-if="Object.keys(form.errors).length"
                    role="alert"
                    class="space-y-1 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950/40 dark:text-red-200"
                >
                    <p v-for="(error, key) in form.errors" :key="key">
                        {{ error }}
                    </p>
                </div>
                <div>
                    <label for="plan-name" class="text-sm font-medium"
                        >Plan name</label
                    ><input
                        id="plan-name"
                        v-model="form.name"
                        required
                        maxlength="160"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                        :aria-invalid="Boolean(form.errors.name)"
                    />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="plan-slug" class="text-sm font-medium"
                            >URL slug</label
                        ><input
                            id="plan-slug"
                            v-model="form.slug"
                            required
                            pattern="[a-z0-9]+(-[a-z0-9]+)*"
                            maxlength="180"
                            class="mt-2 w-full rounded-lg border bg-background p-3"
                            :aria-invalid="Boolean(form.errors.slug)"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Lowercase words separated by hyphens.
                        </p>
                    </div>
                    <div>
                        <label for="plan-platform" class="text-sm font-medium"
                            >Platform</label
                        ><select
                            id="plan-platform"
                            v-model="form.platform"
                            class="mt-2 w-full rounded-lg border bg-background p-3"
                        >
                            <option value="wordpress">WordPress</option>
                            <option value="whmcs">WHMCS</option>
                            <option value="server">Server</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="plan-summary" class="text-sm font-medium"
                        >Short description</label
                    ><textarea
                        id="plan-summary"
                        v-model="form.summary"
                        required
                        maxlength="1000"
                        rows="3"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                        :aria-invalid="Boolean(form.errors.summary)"
                    />
                </div>
                <div>
                    <label for="plan-scope" class="text-sm font-medium"
                        >Included tasks and limits</label
                    ><textarea
                        id="plan-scope"
                        v-model="form.scope"
                        required
                        minlength="20"
                        maxlength="10000"
                        rows="6"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                        :aria-invalid="Boolean(form.errors.scope)"
                    />
                    <p class="mt-1 text-xs text-muted-foreground">
                        Use one task per line. State any frequency or usage
                        limits explicitly.
                    </p>
                </div>
                <div>
                    <label for="plan-exclusions" class="text-sm font-medium"
                        >Exclusions (optional)</label
                    ><textarea
                        id="plan-exclusions"
                        v-model="form.exclusions"
                        maxlength="10000"
                        rows="3"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                    />
                </div>
                <div>
                    <label for="plan-support" class="text-sm font-medium"
                        >Support arrangements (optional)</label
                    ><textarea
                        id="plan-support"
                        v-model="form.support_arrangements"
                        maxlength="5000"
                        rows="3"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                    />
                    <p class="mt-1 text-xs text-muted-foreground">
                        Only enter response commitments and coverage that the
                        business has approved.
                    </p>
                </div>
                <div>
                    <label for="plan-price" class="text-sm font-medium"
                        >Billing</label
                    ><select
                        id="plan-price"
                        v-model="form.product_price_id"
                        class="mt-2 w-full rounded-lg border bg-background p-3"
                        :aria-invalid="Boolean(form.errors.product_price_id)"
                    >
                        <option :value="null">
                            Custom quote after reviewing requirements
                        </option>
                        <option
                            v-for="price in prices"
                            :key="price.id"
                            :value="price.id"
                        >
                            {{ price.label }}
                        </option>
                    </select>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Fixed plans use an existing enabled monthly or yearly
                        product price. Manage those prices in the product
                        catalog.
                    </p>
                </div>
                <label class="flex items-center gap-3 text-sm"
                    ><input
                        v-model="form.published"
                        type="checkbox"
                        class="size-4"
                    />Publish on the website</label
                ><button :disabled="form.processing" class="button-primary">
                    {{
                        form.processing
                            ? 'Saving…'
                            : editing
                              ? 'Save plan'
                              : 'Create plan'
                    }}
                </button>
            </form>
        </div>
    </div>
</template>
