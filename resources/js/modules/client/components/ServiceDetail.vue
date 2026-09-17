<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, ArrowUpRight, Check } from '@lucide/vue';
import { computed } from 'vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
import { services } from '@/modules/client/data/services';
const props = defineProps<{
    serviceSlug: string;
    seo: Record<string, unknown>;
}>();
const service = computed(() =>
    services.find((item) => item.slug === props.serviceSlug)!,
);
const related = computed(() =>
    services
        .filter(
            (item) =>
                item.slug !== props.serviceSlug &&
                item.category === service.value.category,
        )
        .slice(0, 2),
);
</script>
<template>
    <SeoHead :seo="seo" />
    <section class="page-intro border-b border-border/70">
        <div class="site-container py-12 md:py-20">
            <Link
                href="/services"
                class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground"
                ><ArrowLeft class="size-4" /> All services</Link
            >
            <div
                class="mt-12 grid gap-10 lg:grid-cols-[1.25fr_0.75fr] lg:items-end"
            >
                <div>
                    <p class="section-kicker">
                        {{ service.category }} / {{ service.number }}
                    </p>
                    <h1 class="display-title mt-5">{{ service.title }}</h1>
                </div>
                <div>
                    <p class="body-copy">{{ service.shortDescription }}</p>
                    <Link
                        :href="`/contact?service=${service.slug}`"
                        class="button-primary mt-7"
                        >Discuss Your Project <ArrowUpRight class="size-4"
                    /></Link>
                </div>
            </div>
        </div>
    </section>
    <section
        class="site-container grid gap-10 py-16 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20 lg:py-24"
    >
        <div>
            <p class="section-kicker">Who it is for</p>
            <h2 class="section-title mt-4">
                A practical fit for your next step.
            </h2>
            <p class="body-copy mt-6">{{ service.audience }}</p>
            <p class="mt-6 text-base leading-7 text-muted-foreground">
                {{ service.outcome }}
            </p>
        </div>
        <div class="surface-card p-7 md:p-10">
            <p class="section-kicker">Typical scope</p>
            <h2 class="mt-4 text-2xl font-semibold">What we can work on</h2>
            <ul class="mt-6 divide-y divide-border">
                <li
                    v-for="item in service.scope"
                    :key="item"
                    class="flex gap-4 py-5"
                >
                    <Check class="mt-0.5 size-5 shrink-0 text-primary" /><span
                        class="text-base leading-6 text-muted-foreground"
                        >{{ item }}</span
                    >
                </li>
            </ul>
            <p
                class="mt-5 border-t border-border pt-6 text-sm leading-6 text-muted-foreground"
            >
                The exact scope, price, and any ongoing arrangements are agreed
                for your project.
            </p>
        </div>
    </section>
    <section class="border-y border-border/70 bg-card">
        <div class="site-container grid gap-8 py-14 md:grid-cols-[1fr_1.4fr]">
            <div>
                <p class="section-kicker">Getting started</p>
                <h2 class="section-title mt-4">A useful first conversation.</h2>
            </div>
            <div class="grid gap-7 sm:grid-cols-2">
                <div>
                    <span class="font-mono text-sm text-primary"
                        >01 / THE CONTEXT</span
                    >
                    <h3 class="mt-3 font-semibold">Share the essentials</h3>
                    <p class="mt-2 text-sm leading-7 text-muted-foreground">
                        Tell us about your business, your existing system, and
                        what you want to achieve. Include relevant versions and
                        examples.
                    </p>
                </div>
                <div>
                    <span class="font-mono text-sm text-primary"
                        >02 / THE NEXT STEP</span
                    >
                    <h3 class="mt-3 font-semibold">Agree the work</h3>
                    <p class="mt-2 text-sm leading-7 text-muted-foreground">
                        Discuss requirements and questions, then agree the scope
                        and commercial details before proceeding.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <section class="site-container py-14 md:py-20">
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div>
                <p class="section-kicker">Make a start</p>
                <h2 class="section-title mt-4">
                    What would you like to improve?
                </h2>
            </div>
            <Link
                :href="`/contact?service=${service.slug}`"
                class="button-primary"
                >Request
                {{
                    service.category === 'Maintenance'
                        ? 'help'
                        : 'a project discussion'
                }}
                <ArrowRight class="size-4"
            /></Link>
        </div>
        <div v-if="related.length" class="mt-12 grid gap-4 sm:grid-cols-2">
            <Link
                v-for="item in related"
                :key="item.id"
                :href="`/services/${item.slug}`"
                class="surface-card flex items-center justify-between gap-5 p-6 font-semibold"
                >{{ item.title
                }}<ArrowUpRight class="size-5 shrink-0 text-primary"
            /></Link>
        </div>
        <p class="mt-10 text-sm text-muted-foreground">
            Already a customer?
            <Link
                href="/client-area/tickets"
                class="font-semibold text-primary underline underline-offset-4"
                >Open your support tickets</Link
            >
            to keep track of a product issue.
        </p>
    </section>
</template>
