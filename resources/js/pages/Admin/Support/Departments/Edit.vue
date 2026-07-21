<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Inbox } from '@lucide/vue';
import DepartmentForm from '@/modules/admin/support/components/DepartmentForm.vue';

defineProps(['department', 'fields', 'admins', 'mailProviders', 'fieldTypes']);
</script>

<template>
    <Head :title="`Edit department — ${department.name}`" />

    <div class="w-full min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-7xl space-y-7">
            <div
                class="relative overflow-hidden rounded-3xl bg-[radial-gradient(circle_at_85%_20%,rgba(103,232,249,.24),transparent_28%),linear-gradient(135deg,#081d3e,#0a59b5)] px-6 py-8 text-white sm:px-9"
            >
                <div class="relative flex items-end justify-between gap-6">
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
                            Edit department
                        </h1>
                        <p class="mt-3 text-sm text-blue-100/75">
                            {{ department.name }}
                        </p>
                    </div>
                    <span
                        class="hidden rounded-2xl border border-white/10 bg-white/5 p-3 text-cyan-200 sm:block"
                    >
                        <Inbox class="size-6" />
                    </span>
                </div>
            </div>
            <DepartmentForm
                :department="department"
                :fields="fields"
                :admins="admins"
                :mail-providers="mailProviders"
                :field-types="fieldTypes"
            />
        </div>
    </div>
</template>
