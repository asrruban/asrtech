<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Check,
    Clipboard,
    Fingerprint,
    LockKeyhole,
    RefreshCw,
    ShieldCheck,
    Smartphone,
    UserCog,
    Users,
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

defineProps<{
    twoFactor: Record<string, any>;
    canManageAdmins: boolean;
    roleOptions: Record<string, any>[];
    admins: Record<string, any>[];
    auditLogs: Record<string, any> | null;
}>();

const setupForm = useForm<{ two_factor?: string }>({});
const confirmForm = useForm({ code: '' });
const disableForm = useForm({ current_password: '', code: '' });
const recoveryForm = useForm({ current_password: '', code: '' });
const copied = ref<string | null>(null);
const updatingAdmin = ref<number | null>(null);

const setup = () => setupForm.post('/admin/security/two-factor/setup');
const confirmSetup = () =>
    confirmForm.post('/admin/security/two-factor/confirm', {
        onFinish: () => confirmForm.reset('code'),
    });
const disable = () => {
    if (
        confirm(
            'Disable two-factor authentication for your administrator account?',
        )
    ) {
        disableForm.delete('/admin/security/two-factor', {
            onFinish: () => disableForm.reset(),
        });
    }
};
const regenerateRecoveryCodes = () =>
    recoveryForm.post('/admin/security/two-factor/recovery-codes', {
        onFinish: () => recoveryForm.reset(),
    });

const updateRole = (admin: Record<string, any>, event: Event) => {
    const role = (event.target as HTMLSelectElement).value;

    if (!confirm('Change the role for ' + admin.name + '?')) {
        (event.target as HTMLSelectElement).value = admin.role;

        return;
    }

    router.patch(
        '/admin/security/admins/' + admin.id + '/role',
        { role },
        {
            preserveScroll: true,
            onStart: () => (updatingAdmin.value = admin.id),
            onFinish: () => (updatingAdmin.value = null),
        },
    );
};

const copy = async (value: string, key: string) => {
    try {
        await navigator.clipboard.writeText(value);
        copied.value = key;
        window.setTimeout(() => {
            if (copied.value === key) {
                copied.value = null;
            }
        }, 1800);
    } catch {
        copied.value = null;
    }
};

const dateTime = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'Never';

const label = (value: string) =>
    value
        .replace(/^admin\./, '')
        .split(/[._-]/)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

const roleClass = (role: string) =>
    ({
        super_admin:
            'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
        billing:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        support:
            'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        catalog:
            'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    })[role] ?? 'bg-muted text-muted-foreground';

const initials = (name: string) =>
    name
        .split(' ')
        .map((part) => part.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();

const paginationLabel = (value: string) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <Head title="Admin security" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <section
            class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-2xl shadow-slate-200 sm:px-8 lg:px-10 dark:shadow-none"
        >
            <div
                class="pointer-events-none absolute -top-28 -right-16 size-80 rounded-full bg-violet-500/20 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-32 left-1/3 size-72 rounded-full bg-cyan-500/15 blur-3xl"
            />
            <div
                class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
            >
                <div>
                    <div
                        class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-violet-200"
                    >
                        <ShieldCheck class="size-3.5" />
                        Identity and access
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        Admin security
                    </h1>
                    <p
                        class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base"
                    >
                        Protect administrator sign-in, assign least-privilege
                        roles, and review every sensitive control-panel change.
                    </p>
                </div>
                <div
                    class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-5 py-4"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl"
                        :class="
                            twoFactor.enabled
                                ? 'bg-emerald-400/15 text-emerald-300'
                                : 'bg-amber-400/15 text-amber-200'
                        "
                    >
                        <Fingerprint class="size-5" />
                    </span>
                    <div>
                        <p class="text-xs text-slate-400">
                            Your two-factor status
                        </p>
                        <p class="font-semibold">
                            {{
                                twoFactor.enabled
                                    ? 'Protected'
                                    : 'Setup required'
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="twoFactor.recovery_codes?.length"
            class="rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6 dark:border-amber-500/20 dark:bg-amber-500/10"
        >
            <div class="flex items-start gap-3">
                <AlertTriangle
                    class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-300"
                />
                <div class="min-w-0 flex-1">
                    <h2
                        class="font-semibold text-amber-950 dark:text-amber-100"
                    >
                        Save your recovery codes now
                    </h2>
                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                        Each code works once. This list will not be shown again
                        after you leave or reload this page.
                    </p>
                    <div
                        class="mt-4 grid gap-2 rounded-xl bg-white/70 p-4 font-mono text-sm sm:grid-cols-2 dark:bg-slate-950/40"
                    >
                        <div
                            v-for="code in twoFactor.recovery_codes"
                            :key="code"
                            class="rounded-lg border border-amber-200 px-3 py-2 dark:border-amber-500/20"
                        >
                            {{ code }}
                        </div>
                    </div>
                    <Button
                        class="mt-4"
                        size="sm"
                        variant="outline"
                        @click="
                            copy(
                                twoFactor.recovery_codes.join('\n'),
                                'recovery-codes',
                            )
                        "
                    >
                        <Check
                            v-if="copied === 'recovery-codes'"
                            class="size-4 text-emerald-600"
                        />
                        <Clipboard v-else class="size-4" />
                        {{
                            copied === 'recovery-codes'
                                ? 'Copied'
                                : 'Copy all codes'
                        }}
                    </Button>
                </div>
            </div>
        </section>

        <section
            class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(340px,0.85fr)]"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300"
                        >
                            <Smartphone class="size-5" />
                        </span>
                        <div>
                            <CardTitle>Two-factor authentication</CardTitle>
                            <CardDescription>
                                Require a time-based code after every password
                                sign-in.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="twoFactor.enabled" class="space-y-6">
                        <div
                            class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                        >
                            <ShieldCheck
                                class="size-5 text-emerald-600 dark:text-emerald-300"
                            />
                            <div>
                                <p
                                    class="font-semibold text-emerald-900 dark:text-emerald-100"
                                >
                                    Two-factor authentication is active
                                </p>
                                <p
                                    class="text-xs text-emerald-700 dark:text-emerald-300"
                                >
                                    Enabled
                                    {{ dateTime(twoFactor.confirmed_at) }} ·
                                    {{ twoFactor.recovery_codes_remaining }}
                                    recovery codes remaining
                                </p>
                            </div>
                        </div>

                        <form
                            class="grid gap-4 sm:grid-cols-2"
                            @submit.prevent="regenerateRecoveryCodes"
                        >
                            <div class="space-y-2">
                                <Label for="recovery-password">
                                    Current password
                                </Label>
                                <Input
                                    id="recovery-password"
                                    v-model="recoveryForm.current_password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                />
                                <InputError
                                    :message="
                                        recoveryForm.errors.current_password
                                    "
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="recovery-code">
                                    Authenticator code
                                </Label>
                                <Input
                                    id="recovery-code"
                                    v-model="recoveryForm.code"
                                    class="font-mono tracking-widest"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    required
                                />
                                <InputError
                                    :message="recoveryForm.errors.code"
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <Button
                                    type="submit"
                                    variant="outline"
                                    :disabled="recoveryForm.processing"
                                >
                                    <RefreshCw class="size-4" />
                                    Generate new recovery codes
                                </Button>
                            </div>
                        </form>
                    </div>

                    <div v-else-if="twoFactor.pending_secret" class="space-y-6">
                        <ol class="space-y-5">
                            <li class="flex gap-3">
                                <span
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-950 text-xs font-bold text-white dark:bg-white dark:text-slate-950"
                                >
                                    1
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold">
                                        Add the account to your authenticator
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        Enter this secret manually in Google
                                        Authenticator, 1Password, Authy, or any
                                        TOTP-compatible app.
                                    </p>
                                    <div
                                        class="mt-3 flex items-center gap-2 rounded-xl bg-muted p-3"
                                    >
                                        <code
                                            class="min-w-0 flex-1 font-mono text-sm font-semibold tracking-wider break-all"
                                        >
                                            {{ twoFactor.pending_secret }}
                                        </code>
                                        <button
                                            type="button"
                                            class="shrink-0 rounded-lg border bg-background p-2 hover:bg-accent"
                                            aria-label="Copy setup secret"
                                            @click="
                                                copy(
                                                    twoFactor.pending_secret,
                                                    'secret',
                                                )
                                            "
                                        >
                                            <Check
                                                v-if="copied === 'secret'"
                                                class="size-4 text-emerald-600"
                                            />
                                            <Clipboard v-else class="size-4" />
                                        </button>
                                    </div>
                                    <details class="mt-3 text-sm">
                                        <summary
                                            class="cursor-pointer font-medium text-primary"
                                        >
                                            Show full setup URI
                                        </summary>
                                        <code
                                            class="mt-2 block rounded-lg bg-muted p-3 text-xs break-all"
                                        >
                                            {{ twoFactor.setup_uri }}
                                        </code>
                                    </details>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-950 text-xs font-bold text-white dark:bg-white dark:text-slate-950"
                                >
                                    2
                                </span>
                                <form
                                    class="min-w-0 flex-1"
                                    @submit.prevent="confirmSetup"
                                >
                                    <p class="font-semibold">
                                        Confirm a six-digit code
                                    </p>
                                    <div class="mt-3 flex max-w-md gap-2">
                                        <Input
                                            v-model="confirmForm.code"
                                            class="font-mono tracking-widest"
                                            inputmode="numeric"
                                            autocomplete="one-time-code"
                                            maxlength="6"
                                            placeholder="000000"
                                            required
                                        />
                                        <Button
                                            type="submit"
                                            :disabled="confirmForm.processing"
                                        >
                                            Confirm
                                        </Button>
                                    </div>
                                    <InputError
                                        class="mt-2"
                                        :message="confirmForm.errors.code"
                                    />
                                </form>
                            </li>
                        </ol>
                    </div>

                    <div v-else class="py-4 text-center">
                        <div
                            class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-white/10"
                        >
                            <LockKeyhole class="size-6 text-slate-500" />
                        </div>
                        <h3 class="mt-4 font-semibold">
                            Add a second sign-in factor
                        </h3>
                        <p
                            class="mx-auto mt-1 max-w-md text-sm leading-6 text-muted-foreground"
                        >
                            Passwords can be stolen. A rotating authenticator
                            code blocks sign-in without your trusted device.
                        </p>
                        <Button
                            class="mt-5"
                            :disabled="setupForm.processing"
                            @click="setup"
                        >
                            <Smartphone class="size-4" />
                            Start authenticator setup
                        </Button>
                        <InputError
                            class="mt-2"
                            :message="setupForm.errors.two_factor"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <LockKeyhole class="size-5" />
                        Emergency controls
                    </CardTitle>
                    <CardDescription>
                        Sensitive changes require your password and a fresh
                        authenticator or recovery code.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        v-if="twoFactor.enabled"
                        class="space-y-4"
                        @submit.prevent="disable"
                    >
                        <div class="space-y-2">
                            <Label for="disable-password">
                                Current password
                            </Label>
                            <Input
                                id="disable-password"
                                v-model="disableForm.current_password"
                                type="password"
                                autocomplete="current-password"
                                required
                            />
                            <InputError
                                :message="disableForm.errors.current_password"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="disable-code">
                                Authenticator or recovery code
                            </Label>
                            <Input
                                id="disable-code"
                                v-model="disableForm.code"
                                class="font-mono"
                                autocomplete="one-time-code"
                                required
                            />
                            <InputError :message="disableForm.errors.code" />
                        </div>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="disableForm.processing"
                        >
                            Disable two-factor authentication
                        </Button>
                    </form>
                    <div
                        v-else
                        class="rounded-xl border border-dashed p-5 text-center text-sm text-muted-foreground"
                    >
                        Emergency controls are available after two-factor
                        authentication is enabled.
                    </div>
                </CardContent>
            </Card>
        </section>

        <section
            v-if="canManageAdmins"
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 px-5 py-5 sm:px-6 dark:border-white/10"
            >
                <div>
                    <h2 class="flex items-center gap-2 font-semibold">
                        <Users class="size-5" /> Administrator roles
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Least-privilege access for billing, support, catalog,
                        and platform administrators.
                    </p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-5 py-3.5">Administrator</th>
                            <th class="px-5 py-3.5">Role</th>
                            <th class="px-5 py-3.5">Two-factor</th>
                            <th class="px-5 py-3.5">Last sign-in</th>
                            <th class="px-5 py-3.5">Last IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="admin in admins"
                            :key="admin.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex size-9 items-center justify-center rounded-xl bg-slate-100 text-xs font-bold dark:bg-white/10"
                                    >
                                        {{ initials(admin.name) }}
                                    </span>
                                    <div>
                                        <p class="font-semibold">
                                            {{ admin.name }}
                                            <span
                                                v-if="admin.is_current"
                                                class="text-xs font-normal text-primary"
                                            >
                                                (you)
                                            </span>
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ admin.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <select
                                    v-if="canManageAdmins && !admin.is_current"
                                    :value="admin.role"
                                    class="h-9 rounded-md border bg-transparent px-3 text-sm"
                                    :disabled="updatingAdmin === admin.id"
                                    @change="updateRole(admin, $event)"
                                >
                                    <option
                                        v-for="role in roleOptions"
                                        :key="role.value"
                                        :value="role.value"
                                    >
                                        {{ role.label }}
                                    </option>
                                </select>
                                <span
                                    v-else
                                    class="rounded-full px-2.5 py-1 text-xs font-bold"
                                    :class="roleClass(admin.role)"
                                >
                                    {{ admin.role_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold"
                                    :class="
                                        admin.two_factor_enabled
                                            ? 'text-emerald-600'
                                            : 'text-amber-600'
                                    "
                                >
                                    <ShieldCheck class="size-3.5" />
                                    {{
                                        admin.two_factor_enabled
                                            ? 'Protected'
                                            : 'Not enabled'
                                    }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ dateTime(admin.last_login_at) }}
                            </td>
                            <td
                                class="px-5 py-4 font-mono text-xs text-muted-foreground"
                            >
                                {{ admin.last_login_ip ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            v-if="canManageAdmins && auditLogs"
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900"
        >
            <div
                class="border-b border-slate-100 px-5 py-5 sm:px-6 dark:border-white/10"
            >
                <h2 class="flex items-center gap-2 font-semibold">
                    <UserCog class="size-5" /> Administrator audit trail
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Successful admin changes are recorded with actor, target,
                    request metadata, IP address, and time.
                </p>
            </div>

            <div
                v-if="auditLogs.data.length === 0"
                class="p-12 text-center text-sm text-muted-foreground"
            >
                No administrator changes have been recorded yet.
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-5 py-3.5">Administrator</th>
                            <th class="px-5 py-3.5">Action</th>
                            <th class="px-5 py-3.5">Subject</th>
                            <th class="px-5 py-3.5">IP address</th>
                            <th class="px-5 py-3.5">Time</th>
                            <th class="px-5 py-3.5">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="log in auditLogs.data"
                            :key="log.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-5 py-4">
                                <p class="font-semibold">
                                    {{ log.admin?.name ?? 'Deleted admin' }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ log.admin?.email ?? 'Account removed' }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium">
                                    {{ log.description }}
                                </p>
                                <code class="text-xs text-muted-foreground">
                                    {{ log.action }}
                                </code>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{
                                    log.subject_type
                                        ? label(log.subject_type) +
                                          ' #' +
                                          log.subject_id
                                        : '—'
                                }}
                            </td>
                            <td class="px-5 py-4 font-mono text-xs">
                                {{ log.ip_address ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ dateTime(log.created_at) }}
                            </td>
                            <td class="px-5 py-4">
                                <details
                                    v-if="
                                        log.metadata &&
                                        Object.keys(log.metadata).length
                                    "
                                >
                                    <summary
                                        class="cursor-pointer text-xs font-semibold text-primary"
                                    >
                                        View metadata
                                    </summary>
                                    <pre
                                        class="mt-2 max-w-md overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-200"
                                    ><code>{{ JSON.stringify(log.metadata, null, 2) }}</code></pre>
                                </details>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="auditLogs.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-4 border-t px-5 py-4 text-sm"
            >
                <p class="text-muted-foreground">
                    Showing {{ auditLogs.from }}–{{ auditLogs.to }} of
                    {{ auditLogs.total }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <template v-for="link in auditLogs.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            as-child
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                        >
                            <Link :href="link.url">
                                {{ paginationLabel(link.label) || 'Page' }}
                            </Link>
                        </Button>
                    </template>
                </div>
            </div>
        </section>
    </div>
</template>
