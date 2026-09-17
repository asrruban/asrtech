<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, ShieldCheck } from '@lucide/vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
import type { MaintenancePlan } from '@/types/maintenance';
defineProps<{ plans: MaintenancePlan[] }>();
</script>
<template>
    <SeoHead
        title="Maintenance plans"
        description="Ongoing WordPress, WHMCS and server maintenance from ASR Tech. Review the scope, support arrangements and available billing options."
    />
    <section class="page-intro">
        <div class="site-container py-16 md:py-24">
            <p class="section-kicker">Keep things working</p>
            <h1 class="display-title mt-5 max-w-4xl">
                Technical care,<br />with a clear scope.
            </h1>
            <p class="body-copy mt-6 max-w-2xl text-lg">
                Ongoing help for WordPress, WHMCS and servers. Choose an
                available plan or tell us what you need maintained.
            </p>
        </div>
    </section>
    <section class="site-container py-14 md:py-20">
        <div
            v-if="plans.length"
            class="grid gap-6 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="plan in plans"
                :key="plan.id"
                class="surface-card flex flex-col p-7"
            >
                <p class="section-kicker">{{ plan.platform }}</p>
                <h2 class="mt-4 text-2xl font-semibold">{{ plan.name }}</h2>
                <p class="body-copy mt-4 grow">{{ plan.summary }}</p>
                <p class="mt-7 font-semibold">
                    {{
                        plan.billing
                            ? `${plan.billing.currency} ${plan.billing.amount} / ${plan.billing.cycle === 'monthly' ? 'month' : 'year'}`
                            : 'Custom quote'
                    }}
                </p>
                <p v-if="!plan.available" class="body-copy mt-2 text-sm">
                    Currently unavailable for new requests.
                </p>
                <Link
                    :href="`/maintenance/${plan.slug}`"
                    class="button-secondary mt-5"
                    >Review scope <ArrowUpRight class="size-4"
                /></Link>
            </article>
        </div>
        <div
            v-else
            class="surface-card grid gap-6 p-8 md:grid-cols-[auto_1fr] md:p-12"
        >
            <ShieldCheck class="size-12 text-[var(--client-accent)]" />
            <div>
                <h2 class="section-title">
                    Start with your maintenance needs.
                </h2>
                <p class="body-copy mt-4 max-w-2xl">
                    There are no published plans at the moment. We can discuss
                    WordPress management, WHMCS management or server maintenance
                    and define an appropriate scope.
                </p>
                <Link
                    href="/contact?service=wordpress-whmcs-management"
                    class="button-primary mt-6"
                    >Discuss maintenance <ArrowUpRight class="size-4"
                /></Link>
            </div>
        </div>
        <div class="mt-16 grid gap-8 md:grid-cols-3">
            <div>
                <p class="section-kicker">01 / Review</p>
                <h2 class="mt-3 text-xl font-semibold">
                    Know what is included.
                </h2>
                <p class="body-copy mt-3">
                    Check the tasks, exclusions and support arrangements before
                    requesting a plan.
                </p>
            </div>
            <div>
                <p class="section-kicker">02 / Discuss</p>
                <h2 class="mt-3 text-xl font-semibold">Share your setup.</h2>
                <p class="body-copy mt-3">
                    Tell us about your site or server. Your request is reviewed
                    before activation.
                </p>
            </div>
            <div>
                <p class="section-kicker">03 / Manage</p>
                <h2 class="mt-3 text-xl font-semibold">
                    Keep everything together.
                </h2>
                <p class="body-copy mt-3">
                    Follow the agreed scope, updates and billing from your
                    Client Area.
                </p>
            </div>
        </div>
    </section>
</template>
