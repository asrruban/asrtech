<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { KeyRound, ShieldCheck } from '@lucide/vue';
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

const form = useForm({ code: '' });

const submit = () =>
    form.post('/admin/two-factor-challenge', {
        onFinish: () => form.reset('code'),
    });
</script>

<template>
    <Head title="Admin two-factor verification">
        <meta head-key="robots" name="robots" content="noindex,nofollow" />
    </Head>

    <main
        class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-950 px-4 py-12"
    >
        <div
            class="pointer-events-none absolute -top-40 -right-32 size-96 rounded-full bg-cyan-500/20 blur-3xl"
        />
        <div
            class="pointer-events-none absolute -bottom-40 -left-24 size-96 rounded-full bg-blue-600/20 blur-3xl"
        />

        <div class="relative w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <div
                    class="flex size-14 items-center justify-center rounded-2xl bg-cyan-400 text-slate-950 shadow-xl shadow-cyan-500/20"
                >
                    <ShieldCheck class="size-7" />
                </div>
            </div>

            <Card class="border-white/10 bg-white shadow-2xl">
                <CardHeader class="text-center">
                    <CardTitle class="text-2xl">Verify it’s you</CardTitle>
                    <CardDescription>
                        Enter the six-digit code from your authenticator app.
                        You can also use one unused recovery code.
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
                                    class="pl-9 font-mono tracking-widest"
                                    inputmode="text"
                                    autocomplete="one-time-code"
                                    placeholder="000000 or recovery code"
                                    autofocus
                                    required
                                />
                            </div>
                            <InputError :message="form.errors.code" />
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
        </div>
    </main>
</template>
