<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Inbox, Sparkles } from '@lucide/vue';
import DepartmentForm from '@/modules/admin/support/components/DepartmentForm.vue';

defineProps(['admins', 'mailProviders']);
</script>

<template>
    <Head title="Add support department" />

    <div class="w-full min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-7xl space-y-7">
            <div
                class="relative overflow-hidden rounded-3xl bg-[radial-gradient(circle_at_85%_20%,rgba(103,232,249,.24),transparent_28%),linear-gradient(135deg,#081d3e,#0a59b5)] px-6 py-8 text-white sm:px-9"
            >
                <div
                    class="absolute inset-0 bg-[linear-gradient(to_right,#fff_1px,transparent_1px),linear-gradient(to_bottom,#fff_1px,transparent_1px)] bg-[size:44px_44px] opacity-[0.04]"
                ></div>
                <div
                    class="relative flex flex-col justify-between gap-7 sm:flex-row sm:items-end"
                >
                    <div>
                        <Link
                            href="/admin/support/departments"
                            class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-100 transition hover:text-white"
                        >
                            <ArrowLeft class="size-4" /> Support Ticket
                            Departments
                        </Link>
                        <p
                            class="mt-8 text-xs font-extrabold tracking-[0.18em] text-cyan-200 uppercase"
                        >
                            Support routing
                        </p>
                        <h1
                            class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl"
                        >
                            Create a support department
                        </h1>
                        <p
                            class="mt-3 max-w-2xl text-sm leading-6 text-blue-100/80"
                        >
                            Define the customer-facing queue, assign the right
                            team, and preview how the department will appear on
                            the public ticket page.
                        </p>
                    </div>
                    <div
                        class="hidden items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 sm:flex"
                    >
                        <span class="rounded-xl bg-white/10 p-2 text-cyan-200">
                            <Inbox class="size-5" />
                        </span>
                        <div>
                            <p class="text-xs font-extrabold">
                                Live storefront preview
                            </p>
                            <p class="mt-0.5 text-[11px] text-blue-100/60">
                                Updates as you configure
                            </p>
                        </div>
                        <Sparkles class="size-4 text-cyan-200" />
                    </div>
                </div>
            </div>
            <DepartmentForm :admins="admins" :mail-providers="mailProviders" />
        </div>
    </div>
</template>
