<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { MailCheck } from '@lucide/vue';
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

    <section
        class="flex min-h-[70vh] items-center justify-center bg-muted/40 px-4 py-16"
    >
        <div class="w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <div
                    class="flex size-12 items-center justify-center rounded-xl bg-[#4fb250] text-white shadow-sm"
                >
                    <MailCheck class="size-6" />
                </div>
            </div>

            <Card>
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
                            <InputError :message="form.errors.code" />
                        </div>

                        <Button
                            type="submit"
                            class="w-full bg-[#4fb250] hover:bg-[#439c45]"
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
                            class="font-semibold text-[#4fb250] hover:text-[#439c45] disabled:opacity-60"
                            :disabled="resendForm.processing"
                            @click="resend"
                        >
                            {{
                                resendForm.processing
                                    ? 'Sending…'
                                    : 'Resend code'
                            }}
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
                        The code expires in {{ ttlMinutes ?? 10 }} minutes.
                        Check your spam folder if you can't find the email.
                    </p>
                </CardContent>
            </Card>
        </div>
    </section>
</template>
