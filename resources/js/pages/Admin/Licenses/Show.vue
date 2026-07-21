<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    Check,
    Copy,
    FileText,
    Globe,
    KeyRound,
    Pause,
    Play,
    RefreshCw,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    license: Record<string, any>;
    accessLogs: Record<string, any>[];
}>();

const copied = ref(false);

const copyKey = async () => {
    await navigator.clipboard.writeText(props.license.license_key);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const installationForm = useForm({
    action: 'update_installation',
    domain: props.license.domain ?? '',
    path: props.license.path ?? '',
    ip_address: props.license.ip_address ?? '',
});

const saveInstallation = () =>
    installationForm.patch(`/admin/licenses/${props.license.id}`, {
        preserveScroll: true,
    });

const acting = ref(false);

const licenseAction = (action: string) => {
    if (
        action === 'terminate' &&
        !confirm(
            `Terminate license ${props.license.license_key}? This cannot be undone.`,
        )
    ) {
        return;
    }

    router.patch(
        `/admin/licenses/${props.license.id}`,
        { action },
        {
            preserveScroll: true,
            onStart: () => (acting.value = true),
            onFinish: () => (acting.value = false),
        },
    );
};

const money = (currency: string, amount: string | number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));

const formatDateTime = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const statusClass = (status: string) =>
    ({
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        suspended:
            'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        expired:
            'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        terminated:
            'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
    })[status] ?? 'bg-muted text-muted-foreground';
</script>

<template>
    <Head :title="`License ${license.license_key}`" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <Link
                :href="`/admin/users/${license.user.id}`"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-primary"
            >
                <ArrowLeft class="size-3.5" /> Back to {{ license.user.name }}
            </Link>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1
                    class="font-mono text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    {{ license.license_key }}
                </h1>
                <span
                    class="rounded-full px-3 py-1 text-xs font-bold uppercase"
                    :class="statusClass(license.status)"
                >
                    {{ label(license.status) }}
                </span>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition hover:bg-muted"
                    @click="copyKey"
                >
                    <template v-if="copied">
                        <Check class="size-3.5 text-emerald-600" /> Copied
                    </template>
                    <template v-else>
                        <Copy class="size-3.5" /> Copy key
                    </template>
                </button>
            </div>
            <p class="mt-1 text-muted-foreground">
                {{ license.product.name }} · owned by
                {{ license.user.name }} ({{ license.user.email }})
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="min-w-0 space-y-6">
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <KeyRound class="size-5" />
                            <CardTitle>License overview</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl
                            class="grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2"
                        >
                            <div>
                                <dt class="text-muted-foreground">Product</dt>
                                <dd class="mt-0.5 font-semibold">
                                    {{ license.product.name }}
                                    <span
                                        v-if="license.product.version"
                                        class="font-normal text-muted-foreground"
                                    >
                                        v{{ license.product.version }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Owner</dt>
                                <dd class="mt-0.5 font-semibold">
                                    <Link
                                        :href="`/admin/users/${license.user.id}`"
                                        class="hover:text-primary"
                                    >
                                        {{ license.user.name }}
                                    </Link>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">
                                    Registered
                                </dt>
                                <dd class="mt-0.5 font-semibold">
                                    {{ formatDate(license.created_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Expires</dt>
                                <dd class="mt-0.5 font-semibold">
                                    {{
                                        license.expires_at
                                            ? formatDate(license.expires_at)
                                            : 'Never (lifetime)'
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Reissues</dt>
                                <dd class="mt-0.5 font-semibold">
                                    {{ license.reissue_count }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Order</dt>
                                <dd class="mt-0.5 font-mono text-xs font-semibold">
                                    {{ license.order.order_number }}
                                    <span
                                        class="ml-1 font-sans text-muted-foreground"
                                    >
                                        {{
                                            money(
                                                license.order.currency,
                                                license.order.amount,
                                            )
                                        }}
                                        ·
                                        {{ label(license.order.billing_cycle) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Invoice</dt>
                                <dd class="mt-0.5">
                                    <Link
                                        v-if="license.order.invoice"
                                        :href="`/admin/invoices/${license.order.invoice.id}`"
                                        class="inline-flex items-center gap-1.5 font-semibold text-primary hover:underline"
                                    >
                                        <FileText class="size-3.5" />
                                        {{
                                            license.order.invoice
                                                .invoice_number
                                        }}
                                    </Link>
                                    <span
                                        v-else
                                        class="text-muted-foreground"
                                        >—</span
                                    >
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <Globe class="size-5" />
                            <CardTitle>License validation</CardTitle>
                        </div>
                        <CardDescription>
                            Where this license may be used. Recorded on first
                            activation through the verify API; comma-separate
                            multiple values. A reissue clears all three.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            class="space-y-5"
                            @submit.prevent="saveInstallation"
                        >
                            <div class="space-y-2">
                                <Label for="license-domain"
                                    >Valid domains</Label
                                >
                                <textarea
                                    id="license-domain"
                                    v-model="installationForm.domain"
                                    rows="2"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 font-mono text-sm"
                                    placeholder="billing.clientsite.com,www.billing.clientsite.com"
                                    :disabled="license.status === 'terminated'"
                                />
                                <InputError
                                    :message="installationForm.errors.domain"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="license-ip">Valid IPs</Label>
                                <textarea
                                    id="license-ip"
                                    v-model="installationForm.ip_address"
                                    rows="2"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 font-mono text-sm"
                                    placeholder="203.0.113.10"
                                    :disabled="license.status === 'terminated'"
                                />
                                <InputError
                                    :message="
                                        installationForm.errors.ip_address
                                    "
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="license-path"
                                    >Valid directory</Label
                                >
                                <Input
                                    id="license-path"
                                    v-model="installationForm.path"
                                    class="font-mono"
                                    placeholder="/home/client/public_html/modules/addons/asrtech"
                                    :disabled="license.status === 'terminated'"
                                />
                                <InputError
                                    :message="installationForm.errors.path"
                                />
                            </div>
                            <Button
                                type="submit"
                                :disabled="
                                    installationForm.processing ||
                                    license.status === 'terminated'
                                "
                            >
                                {{
                                    installationForm.processing
                                        ? 'Saving…'
                                        : 'Save validation rules'
                                }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent access log</CardTitle>
                        <CardDescription>
                            License checks received from installations via the
                            verify API.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div
                            v-if="props.accessLogs.length === 0"
                            class="p-8 text-center text-sm text-muted-foreground"
                        >
                            No license checks received yet. Point your product
                            at
                            <code
                                class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs"
                                >POST /api/license/verify</code
                            >.
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table
                                class="w-full min-w-[760px] text-left text-sm"
                            >
                                <thead>
                                    <tr
                                        class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3">Domain</th>
                                        <th class="px-5 py-3">IP</th>
                                        <th class="px-5 py-3">Path</th>
                                        <th class="px-5 py-3">Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="log in props.accessLogs"
                                        :key="log.id"
                                        class="border-b last:border-b-0"
                                    >
                                        <td
                                            class="px-5 py-3 text-muted-foreground"
                                        >
                                            {{ formatDateTime(log.created_at) }}
                                        </td>
                                        <td class="px-5 py-3 font-medium">
                                            {{ log.domain ?? '—' }}
                                        </td>
                                        <td
                                            class="px-5 py-3 font-mono text-xs text-muted-foreground"
                                        >
                                            {{ log.ip_address ?? '—' }}
                                        </td>
                                        <td
                                            class="max-w-56 truncate px-5 py-3 font-mono text-xs text-muted-foreground"
                                        >
                                            {{ log.path ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-bold"
                                                :class="
                                                    log.result.startsWith(
                                                        'Valid',
                                                    )
                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                        : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300'
                                                "
                                            >
                                                {{ log.result }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Module commands</CardTitle>
                        <CardDescription>
                            Status changes apply immediately to the
                            customer's account and to verify API checks.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <template v-if="license.status !== 'terminated'">
                            <Button
                                v-if="license.status === 'active'"
                                variant="outline"
                                class="w-full justify-start"
                                :disabled="acting"
                                @click="licenseAction('suspend')"
                            >
                                <Pause class="size-4" /> Suspend
                            </Button>
                            <Button
                                v-else-if="license.status === 'suspended'"
                                variant="outline"
                                class="w-full justify-start"
                                :disabled="acting"
                                @click="licenseAction('unsuspend')"
                            >
                                <Play class="size-4" /> Unsuspend
                            </Button>
                            <Button
                                variant="outline"
                                class="w-full justify-start"
                                :disabled="acting"
                                @click="licenseAction('reissue')"
                            >
                                <RefreshCw class="size-4" /> Reissue license
                            </Button>
                            <Button
                                variant="outline"
                                class="w-full justify-start"
                                :disabled="
                                    acting || license.reissue_count === 0
                                "
                                @click="licenseAction('reset_reissues')"
                            >
                                <RefreshCw class="size-4" /> Reset reissues
                                ({{ license.reissue_count }})
                            </Button>
                            <Button
                                variant="destructive"
                                class="w-full justify-start"
                                :disabled="acting"
                                @click="licenseAction('terminate')"
                            >
                                <XCircle class="size-4" /> Terminate
                                permanently
                            </Button>
                        </template>
                        <p
                            v-else
                            class="rounded-md bg-muted px-3 py-2 text-sm text-muted-foreground"
                        >
                            This license is terminated and can no longer be
                            changed.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="flex items-start gap-3 p-5">
                        <BadgeCheck class="size-5 shrink-0 text-primary" />
                        <p class="text-xs leading-5 text-muted-foreground">
                            Suspended licenses fail product activation checks
                            but keep their installation details. Reissuing
                            keeps the status and clears the recorded website
                            so the customer can activate elsewhere.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
