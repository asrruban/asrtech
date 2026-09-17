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

defineProps<{ status: string | null }>();
const form = useForm({ email: '' });
const submit = () => form.post('/forgot-password');
</script>

<template>
    <SeoHead
        title="Reset your password"
        description="Recover access to your ASR Tech account."
        :seo="{ robots: 'noindex,nofollow' }"
    />
    <AuthPanel
        title="Let’s get you back in."
        description="Request a secure reset link for your ASR Tech account."
        :features="false"
    >
        <Card class="rounded-2xl border-[var(--client-border)] shadow-sm">
            <CardHeader
                ><CardTitle class="text-2xl">Forgot your password?</CardTitle
                ><CardDescription
                    >Enter the email address you use to sign
                    in.</CardDescription
                ></CardHeader
            >
            <CardContent class="space-y-6">
                <p
                    v-if="status"
                    role="status"
                    class="rounded-xl border border-[var(--client-border)] bg-[var(--client-surface-soft)] p-4 text-sm leading-6"
                >
                    {{ status }}
                </p>
                <form class="space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="recovery-email">Email address</Label>
                        <Input
                            id="recovery-email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            autofocus
                            required
                            :aria-invalid="Boolean(form.errors.email)"
                            :aria-describedby="
                                form.errors.email
                                    ? 'recovery-email-error'
                                    : undefined
                            "
                        />
                        <InputError
                            id="recovery-email-error"
                            :message="form.errors.email"
                        />
                    </div>
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                        >{{
                            form.processing
                                ? 'Requesting link…'
                                : 'Send reset link'
                        }}</Button
                    >
                </form>
                <Link
                    href="/login"
                    class="inline-block text-sm font-semibold text-[var(--client-accent)]"
                    >Back to sign in</Link
                >
            </CardContent>
        </Card>
    </AuthPanel>
</template>
