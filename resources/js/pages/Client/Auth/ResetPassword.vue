<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
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
import AuthPanel from '@/modules/client/components/AuthPanel.vue';

const props = defineProps<{ email: string; token: string }>();
const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: '',
});
const submit = () =>
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
</script>

<template>
    <Head title="Choose a new password"
        ><meta name="robots" content="noindex,nofollow" /><meta
            name="referrer"
            content="no-referrer"
    /></Head>
    <AuthPanel
        title="A fresh start, securely."
        description="Choose a strong, unique password. Your existing two-factor authentication stays enabled."
        :features="false"
    >
        <Card class="rounded-2xl border-[var(--client-border)] shadow-sm">
            <CardHeader
                ><CardTitle class="text-2xl">Choose a new password</CardTitle
                ><CardDescription
                    >Reset links expire after one hour and can only be used
                    once.</CardDescription
                ></CardHeader
            >
            <CardContent class="space-y-6">
                <form class="space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="reset-email">Email address</Label>
                        <Input
                            id="reset-email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            :aria-invalid="Boolean(form.errors.email)"
                            aria-describedby="reset-email-error"
                        />
                        <InputError
                            id="reset-email-error"
                            :message="form.errors.email"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="reset-password">New password</Label>
                        <Input
                            id="reset-password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            required
                            :aria-invalid="Boolean(form.errors.password)"
                            aria-describedby="reset-password-error reset-password-hint"
                        />
                        <p
                            id="reset-password-hint"
                            class="text-xs leading-5 text-muted-foreground"
                        >
                            Use at least 12 characters, with uppercase and
                            lowercase letters, a number, and a symbol.
                        </p>
                        <InputError
                            id="reset-password-error"
                            :message="form.errors.password"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="reset-confirmation"
                            >Confirm new password</Label
                        >
                        <Input
                            id="reset-confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            :aria-invalid="
                                Boolean(form.errors.password_confirmation)
                            "
                            aria-describedby="reset-confirmation-error"
                        />
                        <InputError
                            id="reset-confirmation-error"
                            :message="form.errors.password_confirmation"
                        />
                    </div>
                    <InputError :message="form.errors.token" />
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                        >{{
                            form.processing
                                ? 'Resetting password…'
                                : 'Reset password'
                        }}</Button
                    >
                </form>
                <Link
                    href="/forgot-password"
                    class="inline-block text-sm font-semibold text-[var(--client-accent)]"
                    >Request a new reset link</Link
                >
            </CardContent>
        </Card>
    </AuthPanel>
</template>
