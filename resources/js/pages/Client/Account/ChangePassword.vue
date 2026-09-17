<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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
}>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () =>
    form.patch('/client-area/change-password', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });

const inputClass = 'h-11 w-full rounded-md border bg-transparent px-3 text-sm';
const labelClass = 'mb-1.5 block text-[13px] font-medium text-muted-foreground';
</script>

<template>
    <SeoHead title="Change password" description="Change your password." />

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

                <form
                    class="rounded-xl border bg-card p-6 shadow-sm sm:p-8"
                    @submit.prevent="submit"
                >
                    <h2 class="font-bold tracking-tight">Change Password</h2>

                    <p
                        v-if="!props.hasPassword"
                        class="mt-3 rounded-md bg-blue-50 px-4 py-3 text-sm text-[var(--client-accent-dark)] dark:bg-[#087f75]/10 dark:text-emerald-200"
                    >
                        You signed up with a social account — set a password
                        below to also sign in with email and password.
                    </p>

                    <div class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                        <div v-if="props.hasPassword" class="sm:col-span-2">
                            <label :class="labelClass" for="current-password">
                                Current Password (required)
                            </label>
                            <input
                                id="current-password"
                                v-model="form.current_password"
                                :aria-invalid="
                                    Boolean(form.errors.current_password)
                                "
                                :aria-describedby="
                                    form.errors.current_password
                                        ? 'form-current_password-error'
                                        : undefined
                                "
                                type="password"
                                autocomplete="current-password"
                                class="sm:max-w-[calc(50%-0.75rem)]"
                                :class="inputClass"
                            />
                            <InputError
                                id="form-current_password-error"
                                :message="form.errors.current_password"
                            />
                        </div>
                        <div>
                            <label :class="labelClass" for="new-password">
                                New Password (required)
                            </label>
                            <input
                                id="new-password"
                                v-model="form.password"
                                :aria-invalid="Boolean(form.errors.password)"
                                :aria-describedby="
                                    form.errors.password
                                        ? 'form-password-error'
                                        : undefined
                                "
                                type="password"
                                autocomplete="new-password"
                                :class="inputClass"
                            />
                            <InputError
                                id="form-password-error"
                                :message="form.errors.password"
                            />
                        </div>
                        <div>
                            <label :class="labelClass" for="confirm-password">
                                Confirm New Password (required)
                            </label>
                            <input
                                id="confirm-password"
                                v-model="form.password_confirmation"
                                :aria-invalid="
                                    Boolean(form.errors.password_confirmation)
                                "
                                :aria-describedby="
                                    form.errors.password_confirmation
                                        ? 'form-password_confirmation-error'
                                        : undefined
                                "
                                type="password"
                                autocomplete="new-password"
                                :class="inputClass"
                            />
                            <InputError
                                id="form-password_confirmation-error"
                                :message="form.errors.password_confirmation"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-8 w-full rounded-md bg-[#087f75] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#06655e] disabled:opacity-60"
                    >
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </button>
                </form>
            </div>
        </div>
    </section>
</template>
