<script setup lang="ts">
import type { MaintenancePlan } from '@/types/maintenance';
defineProps<{ plan: MaintenancePlan }>();
</script>
<template>
    <div class="space-y-7">
        <div>
            <p class="section-kicker">{{ plan.platform }} maintenance</p>
            <h2 class="section-title mt-3">{{ plan.name }}</h2>
            <p class="body-copy mt-4">{{ plan.summary }}</p>
        </div>
        <section>
            <h3 class="text-lg font-semibold">Included scope</h3>
            <p class="body-copy mt-3 whitespace-pre-line">{{ plan.scope }}</p>
        </section>
        <section v-if="plan.exclusions">
            <h3 class="text-lg font-semibold">Outside this scope</h3>
            <p class="body-copy mt-3 whitespace-pre-line">
                {{ plan.exclusions }}
            </p>
        </section>
        <section>
            <h3 class="text-lg font-semibold">Support arrangements</h3>
            <p class="body-copy mt-3 whitespace-pre-line">
                {{
                    plan.support_arrangements ||
                    'Support arrangements will be discussed when we review your requirements.'
                }}
            </p>
        </section>
        <section
            class="rounded-2xl border border-[var(--client-border)] bg-[var(--client-surface-soft)] p-5"
        >
            <h3 class="font-semibold">Billing</h3>
            <template v-if="plan.billing"
                ><p class="mt-2 text-2xl font-semibold">
                    {{ plan.billing.currency }} {{ plan.billing.amount }}
                    <span class="text-sm font-normal"
                        >/
                        {{
                            plan.billing.cycle === 'monthly' ? 'month' : 'year'
                        }}</span
                    >
                </p>
                <p
                    v-if="Number(plan.billing.setup_fee) > 0"
                    class="body-copy mt-2"
                >
                    Setup fee: {{ plan.billing.currency }}
                    {{ plan.billing.setup_fee }}
                </p>
                <p class="body-copy mt-2 text-sm">
                    Any applicable taxes or discounts appear at checkout.
                    Billing and renewal controls stay in your Client Area.
                </p></template
            >
            <p v-else class="body-copy mt-2">
                Custom quote. We agree the scope and price before work begins.
            </p>
        </section>
    </div>
</template>
