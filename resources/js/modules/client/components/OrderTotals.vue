<script setup lang="ts">
interface Summary {
    currency?: string | null;
    subtotal: string;
    setup_fee: string;
    discount_amount: string;
    tax_amount: string;
    total: string;
    promotion?: { code: string; name: string } | null;
    tax?: { name: string; rate: string | number } | null;
    tax_pending: boolean;
}
const props = defineProps<{ summary: Summary }>();
const money = (amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.summary.currency || 'USD',
        maximumFractionDigits: 2,
    }).format(Number(amount));
</script>

<template>
    <dl class="space-y-4 text-sm">
        <div class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Subtotal</dt>
            <dd class="font-medium">{{ money(summary.subtotal) }}</dd>
        </div>
        <div
            v-if="Number(summary.discount_amount) > 0"
            class="flex justify-between gap-4 text-primary"
        >
            <dt>
                Promotion<span v-if="summary.promotion">
                    ({{ summary.promotion.code }})</span
                >
            </dt>
            <dd class="font-medium">−{{ money(summary.discount_amount) }}</dd>
        </div>
        <div
            v-if="Number(summary.setup_fee) > 0"
            class="flex justify-between gap-4"
        >
            <dt class="text-muted-foreground">Setup fees</dt>
            <dd class="font-medium">{{ money(summary.setup_fee) }}</dd>
        </div>
        <div
            v-if="Number(summary.tax_amount) > 0"
            class="flex justify-between gap-4"
        >
            <dt class="text-muted-foreground">
                {{ summary.tax?.name || 'Tax' }}
            </dt>
            <dd class="font-medium">{{ money(summary.tax_amount) }}</dd>
        </div>
        <div
            v-else-if="summary.tax_pending"
            class="flex justify-between gap-4 text-xs text-muted-foreground"
        >
            <dt>Tax</dt>
            <dd>Calculated after sign in</dd>
        </div>
        <div
            class="flex justify-between gap-4 border-t border-border pt-5 text-xl font-semibold"
        >
            <dt>Total</dt>
            <dd>{{ money(summary.total) }}</dd>
        </div>
    </dl>
</template>
