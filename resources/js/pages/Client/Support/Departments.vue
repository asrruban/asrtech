<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Clock3,
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
        title="Choose a Support Department"
        description="Choose the ASRTech support department best suited to your request."
        :seo="seo"
    />

    <section
        class="relative overflow-hidden bg-[radial-gradient(circle_at_75%_28%,rgba(84,200,255,0.26),transparent_28%),linear-gradient(135deg,#073b8f,#0876d9)] pb-48 text-white"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#fff_1px,transparent_1px),linear-gradient(to_bottom,#fff_1px,transparent_1px)] bg-[size:48px_48px] opacity-[0.055]"
        ></div>
        <div class="relative mx-auto max-w-6xl px-4 pt-16 sm:px-6 lg:px-8">
            <Link
                href="/support"
                class="inline-flex items-center gap-2 text-sm font-bold text-blue-100 transition hover:text-white"
            >
                <ArrowLeft class="size-4" /> Support center
            </Link>
            <div class="mt-12 max-w-2xl">
                <p
                    class="text-xs font-extrabold tracking-[0.2em] text-cyan-200 uppercase"
                >
                    Open a new ticket
                </p>
                <h1
                    class="mt-4 text-4xl font-extrabold tracking-tight sm:text-5xl"
                >
                    Choose a department
                </h1>
                <p class="mt-5 text-base leading-7 text-blue-100/85">
                    Pick the team that best matches your question. We will keep
                    the selected department ready when you open the ticket form.
                </p>
            </div>
        </div>
    </section>

    <section class="relative z-10 -mt-36 px-4 pb-24 sm:px-6 lg:px-8">
        <div
            class="mx-auto max-w-6xl rounded-3xl border bg-white p-5 shadow-2xl shadow-blue-950/15 sm:p-8 dark:bg-slate-900"
        >
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold text-blue-600">
                        Support routing
                    </p>
                    <h2 class="mt-1 text-2xl font-extrabold">
                        How can we help?
                    </h2>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{
                        user
                            ? 'Signed in and ready to submit'
                            : 'Sign in is required to submit'
                    }}
                </p>
            </div>

            <div v-if="departments.length" class="grid gap-4 md:grid-cols-2">
                <Link
                    v-for="(department, index) in departments"
                    :key="department.id"
                    :href="`/client-area/tickets/create?department=${department.id}`"
                    class="group flex min-h-44 items-start gap-5 rounded-2xl border border-slate-200 p-5 transition duration-300 hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl hover:shadow-blue-950/10 dark:border-white/10 dark:hover:border-blue-500/50"
                >
                    <span
                        class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500 transition group-hover:bg-emerald-500 group-hover:text-white dark:bg-emerald-500/10"
                    >
                        <component
                            :is="
                                departmentIcons[index % departmentIcons.length]
                            "
                            class="size-8 stroke-[1.6]"
                        />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-3">
                            <span class="text-lg font-extrabold">{{
                                department.name
                            }}</span>
                            <ArrowRight
                                class="size-4 shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-600"
                            />
                        </span>
                        <span
                            class="mt-2 block text-sm leading-6 text-muted-foreground"
                        >
                            {{
                                department.description ||
                                'Send your request to this support team and we will follow up through the client area.'
                            }}
                        </span>
                        <span
                            v-if="department.clients_only"
                            class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-blue-600"
                        >
                            <ShieldCheck class="size-3.5" /> Existing clients
                        </span>
                    </span>
                </Link>
            </div>

            <div
                v-else
                class="rounded-2xl bg-slate-50 px-6 py-14 text-center dark:bg-white/[0.03]"
            >
                <LifeBuoy class="mx-auto size-10 text-slate-400" />
                <h2 class="mt-4 text-lg font-extrabold">
                    No public departments are available
                </h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                    Please contact us by email while the support desk is being
                    configured.
                </p>
                <a
                    v-if="site.supportEmail"
                    :href="`mailto:${site.supportEmail}`"
                    class="mt-5 inline-flex font-bold text-blue-600 hover:underline"
                >
                    {{ site.supportEmail }}
                </a>
            </div>
        </div>

        <div class="mx-auto mt-10 grid max-w-5xl gap-4 sm:grid-cols-3">
            <div
                class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 dark:bg-white/[0.03]"
            >
                <ShieldCheck class="size-5 text-emerald-500" />
                <span class="text-sm font-semibold"
                    >Private ticket history</span
                >
            </div>
            <div
                class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 dark:bg-white/[0.03]"
            >
                <Clock3 class="size-5 text-blue-500" />
                <span class="text-sm font-semibold"
                    >Clear response tracking</span
                >
            </div>
            <div
                class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 dark:bg-white/[0.03]"
            >
                <LifeBuoy class="size-5 text-violet-500" />
                <span class="text-sm font-semibold">Specialist routing</span>
            </div>
        </div>
    </section>
</template>
