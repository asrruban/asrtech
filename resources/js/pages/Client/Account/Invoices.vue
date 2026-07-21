<script setup lang="ts">
import { ReceiptText } from '@lucide/vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import type { InvoiceItem } from '@/modules/client/components/InvoicesTable.vue';
import InvoicesTable from '@/modules/client/components/InvoicesTable.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

const props = defineProps<{
    invoices: InvoiceItem[];
}>();
</script>

<template>
    <SeoHead
        title="My invoices"
        description="Your invoice history with PDF downloads."
    />

    <ClientAreaHero
        title="Invoices"
        subtitle="Your billing history — download a PDF copy of any invoice."
    />

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2.5">
            <ReceiptText class="size-5 text-[#4fb250]" />
            <h2 class="text-xl font-bold tracking-tight sm:text-2xl">
                Your invoices
            </h2>
        </div>

        <InvoicesTable
            v-if="props.invoices.length"
            class="mt-6"
            :invoices="props.invoices"
        />
        <div
            v-else
            class="mt-6 rounded-2xl border border-dashed p-10 text-center text-sm text-muted-foreground"
        >
            No invoices yet.
        </div>
    </section>
</template>
