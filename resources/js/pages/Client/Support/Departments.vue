<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Handshake,
    LifeBuoy,
    MessageSquare,
    PackageOpen,
    ShieldCheck,
    Sparkles,
} from '@lucide/vue';
import { computed } from 'vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface Department {
    id: number;
    name: string;
    description: string | null;
    clients_only: boolean;
}

defineProps<{
    departments: Department[];
    seo: Record<string, unknown>;
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user);
const site = computed(() => page.props.site);
const departmentIcons = [MessageSquare, PackageOpen, Handshake, Sparkles];
</script>

<template>
    <SeoHead
        title="Choose a support department"
        description="Choose a department for your ASR Tech support request."
        :seo="seo"
    />
    <section class="border-b bg-[var(--client-surface-soft)] py-12 sm:py-16">
        <div class="site-container">
            <Link
                href="/support"
                class="inline-flex items-center gap-2 text-sm text-[var(--client-muted)]"
                ><ArrowLeft class="size-4" /> Support center</Link
            >
            <p class="section-kicker mt-8">Open a ticket</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">
                How can we help?
            </h1>
            <p class="body-copy mt-5 max-w-2xl">
                Choose the department that best fits your request. You can
                review the details before submitting your ticket.
            </p>
        </div>
    </section>
    <section class="site-container py-12 sm:py-16">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold">Support departments</h2>
            <p class="text-sm text-[var(--client-muted)]">
                {{
                    user
                        ? 'Your conversations are saved in your client area.'
                        : 'Sign in to submit and track your request.'
                }}
            </p>
        </div>
        <div v-if="departments.length" class="grid gap-5 md:grid-cols-2">
            <Link
                v-for="(department, index) in departments"
                :key="department.id"
                :href="`/client-area/tickets/create?department=${department.id}`"
                class="group flex items-start gap-5 rounded-2xl border bg-[var(--client-surface)] p-6 transition hover:border-[#087f75] sm:p-8"
            >
                <span
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[var(--client-accent-soft)] text-[var(--client-accent)]"
                    ><component
                        :is="departmentIcons[index % departmentIcons.length]"
                        aria-hidden="true"
                        class="size-6"
                /></span>
                <div class="min-w-0 flex-1">
                    <h3
                        class="flex items-start justify-between gap-3 text-lg font-semibold"
                    >
                        {{ department.name
                        }}<ArrowRight
                            class="mt-1 size-4 shrink-0 text-[var(--client-accent)]"
                        />
                    </h3>
                    <p
                        class="mt-3 text-sm leading-7 text-[var(--client-muted)]"
                    >
                        {{
                            department.description ||
                            'Describe your request and follow the conversation in your client area.'
                        }}
                    </p>
                    <span
                        v-if="department.clients_only"
                        class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-[var(--client-accent-dark)]"
                        ><ShieldCheck class="size-3.5" /> Existing clients</span
                    >
                </div>
            </Link>
        </div>
        <div
            v-else
            class="rounded-2xl border bg-[var(--client-surface)] px-6 py-14 text-center"
        >
            <LifeBuoy class="mx-auto size-10 text-[var(--client-accent)]" />
            <h2 class="mt-5 text-xl font-semibold">
                No departments are available yet
            </h2>
            <p
                class="mx-auto mt-3 max-w-md text-sm leading-7 text-[var(--client-muted)]"
            >
                Use our contact form to tell us what you need help with.
            </p>
            <Link href="/contact" class="button-primary mt-6"
                >Contact ASR Tech</Link
            ><a
                v-if="site.supportEmail"
                :href="`mailto:${site.supportEmail}`"
                class="mt-4 block text-sm text-[var(--client-accent)]"
                >{{ site.supportEmail }}</a
            >
        </div>
    </section>
</template>
