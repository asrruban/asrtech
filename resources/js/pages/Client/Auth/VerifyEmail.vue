<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
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
import SeoHead from '@/modules/client/components/SeoHead.vue';

defineProps<{ email?: string | null; ttlMinutes?: number }>();

const form = useForm({ code: '' });
const resendForm = useForm({});

const submit = () => {
    form.post('/verify-email', {
        onError: () => form.reset('code'),
    });
};

const resend = () => {
    resendForm.post('/verify-email/resend');
};
</script>

<template>
    <SeoHead title="Verify your email" />

    <AuthPanel
        title="One more step."
        description="Confirm your email address to continue to your ASR Tech account."
    >
        <Card
            class="rounded-2xl border-[var(--client-border)] bg-[var(--client-surface)] py-3 shadow-sm sm:py-5"
        >
            <CardHeader class="text-center">
                <CardTitle class="text-2xl">Check your inbox</CardTitle>
                <CardDescription>
                    We sent a 6-digit verification code to
                    <strong class="text-foreground">{{ email }}</strong
                    >. Enter it below to activate your account.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="code">Verification code</Label>
                        <Input
                            id="code"
                            v-model="form.code"
                            :aria-invalid="Boolean(form.errors.code)"
                            :aria-describedby="
                                form.errors.code ? 'form-code-error' : undefined
                            "
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            pattern="[0-9]*"
                            autocomplete="one-time-code"
                            placeholder="••••••"
                            class="text-center text-2xl font-bold tracking-[0.5em]"
                            autofocus
                            required
                        />
                        <InputError
                            id="form-code-error"
                            :message="form.errors.code"
                        />
                    </div>

                    <Button
                        type="submit"
                        class="w-full bg-[#087f75] hover:bg-[#06655e]"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Verifying…' : 'Verify email' }}
                    </Button>
                </form>

                <div
                    class="mt-5 flex items-center justify-between text-sm text-muted-foreground"
                >
                    <button
                        type="button"
                        class="font-semibold text-[var(--client-accent)] hover:text-[var(--client-accent-dark)] disabled:opacity-60"
                        :disabled="resendForm.processing"
                        @click="resend"
                    >
                        {{ resendForm.processing ? 'Sending…' : 'Resend code' }}
                    </button>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="hover:text-foreground"
                    >
                        Sign out
                    </Link>
                </div>

                <p class="mt-4 text-xs text-muted-foreground">
                    The code expires in {{ ttlMinutes ?? 10 }} minutes. Check
                    your spam folder if you can't find the email.
                </p>
            </CardContent>
        </Card>
    </AuthPanel>
</template>
