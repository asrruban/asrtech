<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    Check,
    Clipboard,
    Clock3,
    CopyCheck,
    Fingerprint,
    RefreshCw,
    Server,
    ShieldCheck,
    Webhook,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{ event: Record<string, any> }>();
const replaying = ref(false);
const copied = ref(false);

const label = (value: string | null) =>
    value
        ? value
              .split(/[._-]/)
              .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
              .join(' ')
        : 'Unknown';

const dateTime = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              dateStyle: 'medium',
              timeStyle: 'long',
          }).format(new Date(value))
        : 'Not recorded';

const statusClass = (value: string) =>
    ({
        processed:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        processing:
            'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        pending:
            'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    })[value] ?? 'bg-muted text-muted-foreground';

const payload = computed(() => JSON.stringify(props.event.payload, null, 2));
const headers = computed(() => JSON.stringify(props.event.headers, null, 2));

const replay = () => {
    if (
        !confirm(
            'Replay this verified webhook payload? Gateway side effects are protected by the stored event ID.',
        )
    ) {
        return;
    }

    router.post(
        '/admin/payments/webhooks/' + props.event.id + '/replay',
        {},
        {
            preserveScroll: true,
            onStart: () => (replaying.value = true),
            onFinish: () => (replaying.value = false),
        },
    );
};

const copyId = async () => {
    try {
        await navigator.clipboard.writeText(props.event.external_id);
        copied.value = true;
        window.setTimeout(() => (copied.value = false), 1800);
    } catch {
        copied.value = false;
    }
};
</script>

<template>
    <Head :title="'Webhook ' + event.external_id" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
        >
            <div class="min-w-0">
                <Link
                    href="/admin/payments"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-primary"
                >
                    <ArrowLeft class="size-3.5" /> Payment reliability
                </Link>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        {{ label(event.event_type) }}
                    </h1>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-bold uppercase"
                        :class="statusClass(event.status)"
                    >
                        {{ label(event.status) }}
                    </span>
                </div>
                <button
                    type="button"
                    class="mt-2 inline-flex max-w-full items-center gap-2 font-mono text-xs text-muted-foreground hover:text-foreground"
                    @click="copyId"
                >
                    <code class="truncate">{{ event.external_id }}</code>
                    <Check v-if="copied" class="size-3.5 text-emerald-600" />
                    <Clipboard v-else class="size-3.5 shrink-0" />
                </button>
            </div>
            <Button
                v-if="event.can_replay"
                :disabled="replaying"
                @click="replay"
            >
                <RefreshCw
                    class="size-4"
                    :class="{ 'animate-spin': replaying }"
                />
                {{ replaying ? 'Replaying…' : 'Replay event' }}
            </Button>
        </div>

        <section
            v-if="event.last_error"
            class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-5 dark:border-red-500/20 dark:bg-red-500/10"
        >
            <AlertTriangle
                class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-300"
            />
            <div class="min-w-0">
                <h2 class="font-semibold text-red-950 dark:text-red-100">
                    Last processing error
                </h2>
                <p
                    class="mt-1 text-sm leading-6 break-words text-red-800 dark:text-red-200"
                >
                    {{ event.last_error }}
                </p>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardContent class="p-5">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300"
                        >
                            <Server class="size-5" />
                        </span>
                        <div>
                            <p class="text-xs text-muted-foreground">Gateway</p>
                            <p class="font-semibold">
                                {{ label(event.gateway) }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-5">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                        >
                            <ShieldCheck class="size-5" />
                        </span>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                HTTP response
                            </p>
                            <p class="font-semibold">
                                {{ event.response_code ?? 'None' }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-5">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300"
                        >
                            <RefreshCw class="size-5" />
                        </span>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Attempts
                            </p>
                            <p class="font-semibold">{{ event.attempts }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-5">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                        >
                            <CopyCheck class="size-5" />
                        </span>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Duplicates blocked
                            </p>
                            <p class="font-semibold">
                                {{ event.duplicate_count }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="min-w-0 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Webhook class="size-5" /> Stored payload
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="mb-3 text-sm text-muted-foreground">
                            Sensitive fields are redacted in this view. Replay
                            uses the original server-side payload.
                        </p>
                        <pre
                            class="max-h-[620px] overflow-auto rounded-xl bg-slate-950 p-4 text-[13px] leading-6 text-slate-200"
                        ><code>{{ payload }}</code></pre>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Fingerprint class="size-5" /> Request identity
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Payload SHA-256
                            </p>
                            <code
                                class="mt-1 block rounded-lg bg-muted px-3 py-2 text-xs break-all"
                            >
                                {{ event.payload_hash }}
                            </code>
                        </div>
                        <div>
                            <p class="mb-2 text-xs text-muted-foreground">
                                Safe request headers
                            </p>
                            <pre
                                class="max-h-80 overflow-auto rounded-xl bg-slate-950 p-4 text-xs leading-5 text-slate-200"
                            ><code>{{ headers }}</code></pre>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Clock3 class="size-5" /> Processing timeline
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ol class="space-y-5">
                            <li class="relative flex gap-3">
                                <span
                                    class="mt-1 size-2.5 shrink-0 rounded-full bg-blue-500 ring-4 ring-blue-100 dark:ring-blue-500/10"
                                />
                                <div>
                                    <p class="text-sm font-semibold">
                                        Received
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ dateTime(event.created_at) }}
                                    </p>
                                </div>
                            </li>
                            <li class="relative flex gap-3">
                                <span
                                    class="mt-1 size-2.5 shrink-0 rounded-full bg-violet-500 ring-4 ring-violet-100 dark:ring-violet-500/10"
                                />
                                <div>
                                    <p class="text-sm font-semibold">
                                        Processing started
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            dateTime(
                                                event.processing_started_at,
                                            )
                                        }}
                                    </p>
                                </div>
                            </li>
                            <li class="relative flex gap-3">
                                <span
                                    class="mt-1 size-2.5 shrink-0 rounded-full ring-4"
                                    :class="
                                        event.verified_at
                                            ? 'bg-emerald-500 ring-emerald-100 dark:ring-emerald-500/10'
                                            : 'bg-slate-300 ring-slate-100 dark:bg-slate-600 dark:ring-white/5'
                                    "
                                />
                                <div>
                                    <p class="text-sm font-semibold">
                                        Payload accepted
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ dateTime(event.verified_at) }}
                                    </p>
                                </div>
                            </li>
                            <li class="relative flex gap-3">
                                <span
                                    class="mt-1 size-2.5 shrink-0 rounded-full ring-4"
                                    :class="
                                        event.processed_at
                                            ? 'bg-emerald-500 ring-emerald-100 dark:ring-emerald-500/10'
                                            : 'bg-slate-300 ring-slate-100 dark:bg-slate-600 dark:ring-white/5'
                                    "
                                />
                                <div>
                                    <p class="text-sm font-semibold">
                                        Processing completed
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ dateTime(event.processed_at) }}
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Delivery details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="text-muted-foreground">
                                    Last received
                                </dt>
                                <dd class="mt-1 font-medium">
                                    {{ dateTime(event.last_received_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">
                                    Last updated
                                </dt>
                                <dd class="mt-1 font-medium">
                                    {{ dateTime(event.updated_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">
                                    Replay eligibility
                                </dt>
                                <dd class="mt-1 font-medium">
                                    {{
                                        event.can_replay
                                            ? 'Verified and replayable'
                                            : 'Not available'
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </section>
    </div>
</template>
