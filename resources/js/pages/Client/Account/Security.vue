<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Copy,
    Check,
    Download,
    KeyRound,
    ShieldCheck,
    ShieldOff,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import AccountCard from '@/modules/client/components/AccountCard.vue';
import AccountSettingsTabs from '@/modules/client/components/AccountSettingsTabs.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

const props = defineProps<{
    account: { name: string; email: string; address: string[] };
    totalDue: string;
    currency: string;
    hasPassword: boolean;
    twoFactor: {
        enabled: boolean;
        confirmed_at: string | null;
        pending_secret: string | null;
        setup_uri: string | null;
        recovery_codes: string[] | null;
        recovery_codes_remaining: number;
    };
}>();

const setupForm = useForm({});
const confirmForm = useForm({ code: '' });
const disableForm = useForm({ current_password: '', code: '' });
const recoveryForm = useForm({ current_password: '', code: '' });

const copied = ref<string | null>(null);

const copy = async (value: string, key: string) => {
    await navigator.clipboard.writeText(value);
    copied.value = key;
    setTimeout(() => (copied.value = null), 2000);
};

const setup = () => setupForm.post('/client-area/security/two-factor/setup');

const confirm = () =>
    confirmForm.post('/client-area/security/two-factor/confirm', {
        preserveScroll: true,
        onSuccess: () => confirmForm.reset(),
    });

const disable = () =>
    disableForm.delete('/client-area/security/two-factor', {
        preserveScroll: true,
        onSuccess: () => disableForm.reset(),
    });

const regenerate = () =>
    recoveryForm.post('/client-area/security/two-factor/recovery-codes', {
        preserveScroll: true,
        onSuccess: () => recoveryForm.reset(),
    });

const inputClass = 'h-11 w-full rounded-md border bg-transparent px-3 text-sm';
const labelClass = 'mb-1.5 block text-[13px] font-medium text-muted-foreground';
</script>

<template>
    <SeoHead title="Security" description="Manage two-factor authentication." />

    <ClientAreaHero title="My Account" overlap />

    <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
        <div
            class="-mt-24 grid items-start gap-6 lg:grid-cols-[360px_minmax(0,1fr)]"
        >
            <AccountCard
                :account="props.account"
                :total-due="props.totalDue"
                :currency="props.currency"
            />

            <div>
                <AccountSettingsTabs />

                <div class="mt-6 space-y-6 rounded-xl border bg-card p-6">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl"
                            :class="
                                props.twoFactor.enabled
                                    ? 'bg-emerald-500/10 text-emerald-500'
                                    : 'bg-amber-500/10 text-amber-500'
                            "
                        >
                            <ShieldCheck
                                v-if="props.twoFactor.enabled"
                                class="size-5"
                            />
                            <ShieldOff v-else class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold">
                                Two-factor authentication
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                <template v-if="props.twoFactor.enabled">
                                    Enabled{{
                                        props.twoFactor.confirmed_at
                                            ? ` on ${new Date(props.twoFactor.confirmed_at).toLocaleDateString()}`
                                            : ''
                                    }}. Your account is protected with an
                                    authenticator app.
                                </template>
                                <template v-else>
                                    Add an extra layer of security. After
                                    enabling, signing in requires a code from
                                    your authenticator app.
                                </template>
                            </p>
                        </div>
                    </div>

                    <!-- Recovery codes flash (shown once) -->
                    <div
                        v-if="props.twoFactor.recovery_codes"
                        class="rounded-lg border border-amber-500/30 bg-amber-500/5 p-4"
                    >
                        <p class="text-sm font-semibold text-amber-600">
                            Save these recovery codes now — they are shown only
                            once. Each can be used a single time if you lose
                            your authenticator.
                        </p>
                        <div
                            class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4"
                        >
                            <code
                                v-for="code in props.twoFactor.recovery_codes"
                                :key="code"
                                class="rounded-md border bg-background px-2 py-1.5 text-center"
                                >{{ code }}</code
                            >
                        </div>
                    </div>

                    <!-- Not enabled, no pending setup -->
                    <div
                        v-if="
                            !props.twoFactor.enabled &&
                            !props.twoFactor.pending_secret
                        "
                    >
                        <button
                            type="button"
                            class="inline-flex h-11 items-center rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-50"
                            :disabled="setupForm.processing"
                            @click="setup"
                        >
                            Enable two-factor authentication
                        </button>
                    </div>

                    <!-- Pending setup: show secret + confirm -->
                    <div
                        v-else-if="
                            !props.twoFactor.enabled &&
                            props.twoFactor.pending_secret
                        "
                        class="space-y-6"
                    >
                        <div class="rounded-lg border p-4">
                            <p class="text-sm font-medium">
                                1. Add this account to your authenticator app
                                (Google Authenticator, 1Password, Authy…)
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <code
                                    class="flex-1 rounded-md border bg-muted/50 px-3 py-2 font-mono text-sm tracking-wider break-all"
                                    >{{ props.twoFactor.pending_secret }}</code
                                >
                                <button
                                    type="button"
                                    class="inline-flex size-9 shrink-0 items-center justify-center rounded-md border transition hover:bg-muted"
                                    aria-label="Copy setup secret"
                                    @click="
                                        copy(
                                            props.twoFactor.pending_secret!,
                                            'secret',
                                        )
                                    "
                                >
                                    <Check
                                        v-if="copied === 'secret'"
                                        class="size-4 text-emerald-500"
                                    />
                                    <Copy v-else class="size-4" />
                                </button>
                            </div>
                            <p
                                class="mt-2 text-xs break-all text-muted-foreground"
                            >
                                {{ props.twoFactor.setup_uri }}
                            </p>
                        </div>

                        <form class="space-y-3" @submit.prevent="confirm">
                            <label :class="labelClass" for="confirm-code">
                                2. Enter the 6-digit code to confirm
                            </label>
                            <input
                                id="confirm-code"
                                v-model="confirmForm.code"
                                :aria-invalid="Boolean(confirmForm.errors.code)"
                                :aria-describedby="
                                    confirmForm.errors.code
                                        ? 'confirmForm-code-error'
                                        : undefined
                                "
                                :class="inputClass"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                placeholder="000000"
                                required
                            />
                            <InputError
                                id="confirmForm-code-error"
                                :message="confirmForm.errors.code"
                            />
                            <button
                                type="submit"
                                class="inline-flex h-11 items-center rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-50"
                                :disabled="confirmForm.processing"
                            >
                                Confirm and enable
                            </button>
                        </form>
                    </div>

                    <!-- Enabled: regenerate codes + disable -->
                    <div v-else class="grid gap-6 md:grid-cols-2">
                        <form
                            class="space-y-3 rounded-lg border p-4"
                            @submit.prevent="regenerate"
                        >
                            <div class="flex items-center gap-2">
                                <KeyRound
                                    class="size-4 text-muted-foreground"
                                />
                                <h3 class="text-sm font-semibold">
                                    Recovery codes
                                </h3>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ props.twoFactor.recovery_codes_remaining }}
                                unused codes remaining.
                            </p>
                            <div v-if="props.hasPassword">
                                <label
                                    :class="labelClass"
                                    for="recovery-password"
                                    >Current password</label
                                >
                                <input
                                    id="recovery-password"
                                    v-model="recoveryForm.current_password"
                                    :aria-invalid="
                                        Boolean(
                                            recoveryForm.errors
                                                .current_password,
                                        )
                                    "
                                    :aria-describedby="
                                        recoveryForm.errors.current_password
                                            ? 'recoveryForm-current_password-error'
                                            : undefined
                                    "
                                    type="password"
                                    :class="inputClass"
                                    autocomplete="current-password"
                                />
                                <InputError
                                    id="recoveryForm-current_password-error"
                                    :message="
                                        recoveryForm.errors.current_password
                                    "
                                />
                            </div>
                            <div>
                                <label :class="labelClass" for="recovery-code"
                                    >Authentication code</label
                                >
                                <input
                                    id="recovery-code"
                                    v-model="recoveryForm.code"
                                    :aria-invalid="
                                        Boolean(recoveryForm.errors.code)
                                    "
                                    :aria-describedby="
                                        recoveryForm.errors.code
                                            ? 'recoveryForm-code-error'
                                            : undefined
                                    "
                                    :class="inputClass"
                                    inputmode="numeric"
                                    placeholder="000000"
                                />
                                <InputError
                                    id="recoveryForm-code-error"
                                    :message="recoveryForm.errors.code"
                                />
                            </div>
                            <button
                                type="submit"
                                class="inline-flex h-10 items-center rounded-md border px-4 text-sm font-semibold transition hover:bg-muted disabled:opacity-50"
                                :disabled="recoveryForm.processing"
                            >
                                Generate new recovery codes
                            </button>
                        </form>

                        <form
                            class="space-y-3 rounded-lg border border-destructive/30 p-4"
                            @submit.prevent="disable"
                        >
                            <div class="flex items-center gap-2">
                                <ShieldOff class="size-4 text-destructive" />
                                <h3
                                    class="text-sm font-semibold text-destructive"
                                >
                                    Disable two-factor
                                </h3>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Your account will only be protected by your
                                password.
                            </p>
                            <div v-if="props.hasPassword">
                                <label
                                    :class="labelClass"
                                    for="disable-password"
                                    >Current password</label
                                >
                                <input
                                    id="disable-password"
                                    v-model="disableForm.current_password"
                                    :aria-invalid="
                                        Boolean(
                                            disableForm.errors.current_password,
                                        )
                                    "
                                    :aria-describedby="
                                        disableForm.errors.current_password
                                            ? 'disableForm-current_password-error'
                                            : undefined
                                    "
                                    type="password"
                                    :class="inputClass"
                                    autocomplete="current-password"
                                />
                                <InputError
                                    id="disableForm-current_password-error"
                                    :message="
                                        disableForm.errors.current_password
                                    "
                                />
                            </div>
                            <div>
                                <label :class="labelClass" for="disable-code"
                                    >Authentication or recovery code</label
                                >
                                <input
                                    id="disable-code"
                                    v-model="disableForm.code"
                                    :aria-invalid="
                                        Boolean(disableForm.errors.code)
                                    "
                                    :aria-describedby="
                                        disableForm.errors.code
                                            ? 'disableForm-code-error'
                                            : undefined
                                    "
                                    :class="inputClass"
                                    placeholder="000000 or recovery code"
                                />
                                <InputError
                                    id="disableForm-code-error"
                                    :message="disableForm.errors.code"
                                />
                            </div>
                            <button
                                type="submit"
                                class="inline-flex h-10 items-center rounded-md bg-destructive px-4 text-sm font-semibold text-destructive-foreground transition hover:opacity-90 disabled:opacity-50"
                                :disabled="disableForm.processing"
                            >
                                Disable two-factor authentication
                            </button>
                        </form>
                    </div>

                    <!-- Privacy / GDPR -->
                    <div class="rounded-lg border p-4">
                        <h3 class="text-sm font-semibold">Your data</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Download a copy of everything we store about your
                            account (profile, orders, licenses, tickets, and
                            more) as a JSON file.
                        </p>
                        <a
                            href="/client-area/data-export"
                            class="mt-3 inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-semibold transition hover:bg-muted"
                        >
                            <Download class="size-4" />
                            Export my data
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
