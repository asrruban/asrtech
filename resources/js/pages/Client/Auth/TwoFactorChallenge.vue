<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
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

const form = useForm({ code: '' });

const submit = () =>
    form.post('/two-factor-challenge', {
        onFinish: () => form.reset('code'),
    });
</script>

<template>
    <Head title="Two-factor verification">
        <meta head-key="robots" name="robots" content="noindex,nofollow" />
    </Head>

    <AuthPanel
        title="A little extra protection."
        description="Use your authenticator app or a recovery code to continue to your account."
    >
        <Card
            class="rounded-2xl border-[var(--client-border)] bg-[var(--client-surface)] py-3 shadow-sm sm:py-5"
        >
            <CardHeader class="text-center">
                <CardTitle class="text-2xl">Verify it’s you</CardTitle>
                <CardDescription>
                    Enter the six-digit code from your authenticator app. You
                    can also use one unused recovery code.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="code">Authentication code</Label>
                        <div class="relative">
                            <KeyRound
                                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="code"
                                v-model="form.code"
                                :aria-invalid="Boolean(form.errors.code)"
                                :aria-describedby="
                                    form.errors.code
                                        ? 'form-code-error'
                                        : undefined
                                "
                                class="pl-9 font-mono tracking-widest"
                                inputmode="text"
                                autocomplete="one-time-code"
                                placeholder="000000 or recovery code"
                                autofocus
                                required
                            />
                        </div>
                        <InputError
                            id="form-code-error"
                            :message="form.errors.code"
                        />
                    </div>

                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? 'Verifying…'
                                : 'Verify and continue'
                        }}
                    </Button>
                </form>
            </CardContent>
        </Card>
    </AuthPanel>
</template>
